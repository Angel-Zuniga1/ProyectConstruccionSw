<?php
/* RF-11 (administrador de sucursal): ventas/pedidos de SU sucursal y cambio de estado
   (pendiente, listo para recoger, entregado, cancelado). */
require 'admin_comun.php';
exigirAdmin('sucursal');

$idSuc = sucursalActual();            // siempre la sucursal asignada
$estadoFiltro = $_GET['estado'] ?? '';
$volver = 'pedidos.php' . ($estadoFiltro !== '' ? '?estado=' . rawurlencode($estadoFiltro) : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'estado') {
    exigirCsrf();
    $idPedido = (int)($_POST['id_pedido'] ?? 0);
    $nuevo = $_POST['estado'] ?? '';
    if (!in_array($nuevo, ESTADOS_PEDIDO, true)) { irA($volver, null, 'Estado no válido.'); }

    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare("SELECT id_sucursal, estado FROM pedidos WHERE id_pedido = ? FOR UPDATE");
        $stmt->bind_param("i", $idPedido);
        $stmt->execute();
        $pedido = $stmt->get_result()->fetch_assoc();
        if (!$pedido) { throw new RuntimeException('El pedido no existe.'); }
        if ((int)$pedido['id_sucursal'] !== $idSuc) {
            throw new RuntimeException('Ese pedido es de otra sucursal.');
        }
        $actual = $pedido['estado'];
        if ($actual === 'entregado' && $nuevo !== 'entregado') { throw new RuntimeException('Un pedido entregado ya no cambia de estado.'); }

        // Al cancelar se devuelven las existencias; si se reactiva un cancelado se vuelven a descontar
        if ($nuevo === 'cancelado' && $actual !== 'cancelado') {
            $conexion->query("UPDATE inventario i JOIN detalle_pedido d ON d.id_producto = i.id_producto AND d.talla = i.talla
                              SET i.existencias = i.existencias + d.cantidad
                              WHERE d.id_pedido = $idPedido AND i.id_sucursal = " . (int)$pedido['id_sucursal']);
        } elseif ($actual === 'cancelado' && $nuevo !== 'cancelado') {
            $conexion->query("UPDATE inventario i JOIN detalle_pedido d ON d.id_producto = i.id_producto AND d.talla = i.talla
                              SET i.existencias = GREATEST(0, i.existencias - d.cantidad)
                              WHERE d.id_pedido = $idPedido AND i.id_sucursal = " . (int)$pedido['id_sucursal']);
        }
        $stmt = $conexion->prepare("UPDATE pedidos SET estado = ? WHERE id_pedido = ?");
        $stmt->bind_param("si", $nuevo, $idPedido);
        $stmt->execute();
        $conexion->commit();
        irA($volver, "Pedido #$idPedido ahora está: $nuevo.");
    } catch (RuntimeException $e) {
        $conexion->rollback();
        irA($volver, null, $e->getMessage());
    } catch (mysqli_sql_exception $e) {
        $conexion->rollback();
        irA($volver, null, 'No se pudo cambiar el estado.');
    }
}

// Listado
$where = [];
$tipos = ''; $params = [];
$where[] = "p.id_sucursal = ?"; $tipos .= 'i'; $params[] = $idSuc;
if (in_array($estadoFiltro, ESTADOS_PEDIDO, true)) { $where[] = "p.estado = ?"; $tipos .= 's'; $params[] = $estadoFiltro; }
$sql = "SELECT p.id_pedido, p.fecha, p.total, p.estado, s.nombre AS sucursal,
               CONCAT(u.nombre, ' ', u.apellidos) AS cliente, u.correo, u.telefono
        FROM pedidos p JOIN usuarios u ON u.id_usuario = p.id_usuario
        JOIN sucursales s ON s.id_sucursal = p.id_sucursal "
     . ($where ? "WHERE " . implode(" AND ", $where) . " " : "") . "ORDER BY p.id_pedido DESC";
$stmt = $conexion->prepare($sql);
if ($params) { $stmt->bind_param($tipos, ...$params); }
$stmt->execute();
$pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Renglones de todos los pedidos listados
$detalles = [];
if ($pedidos) {
    $ids = implode(',', array_map(fn($p) => (int)$p['id_pedido'], $pedidos));
    $res = $conexion->query("SELECT d.id_pedido, pr.nombre, d.talla, d.cantidad, d.precio_unitario
                             FROM detalle_pedido d JOIN productos pr ON pr.id_producto = d.id_producto
                             WHERE d.id_pedido IN ($ids) ORDER BY pr.nombre");
    while ($d = $res->fetch_assoc()) { $detalles[$d['id_pedido']][] = $d; }
}

$r = resumenVentas($conexion, $idSuc);
cabeceraAdmin('Pedidos y ventas · ' . h($_SESSION['admin_sucursal_nombre']), 'pedidos.php');
avisos();
?>
<div class="tarjetas-resumen">
  <div class="resumen"><b>$<?php echo number_format($r['ventas'], 2); ?></b><span>Ventas (no canceladas)</span></div>
  <?php foreach (ESTADOS_PEDIDO as $e): ?><a class="resumen" href="pedidos.php?estado=<?php echo rawurlencode($e); ?>"><b><?php echo $r['por_estado'][$e]; ?></b><span><?php echo ucfirst($e); ?></span></a><?php endforeach; ?>
</div>
<form method="get" class="form-admin inline-form">
  <label>Estado
    <select name="estado" onchange="this.form.submit()">
      <option value="">Todos</option>
      <?php foreach (ESTADOS_PEDIDO as $e): ?><option value="<?php echo $e; ?>" <?php echo $e === $estadoFiltro ? 'selected' : ''; ?>><?php echo ucfirst($e); ?></option><?php endforeach; ?>
    </select>
  </label>
</form>

<section class="bloque">
  <h2><?php echo count($pedidos); ?> pedido(s)</h2>
  <?php if (!$pedidos): ?><p>No hay pedidos con ese filtro.</p><?php endif; ?>
  <?php foreach ($pedidos as $p): ?>
    <article class="pedido-admin estado-<?php echo str_replace(' ', '-', $p['estado']); ?>">
      <header>
        <div>
          <b>Pedido #<?php echo (int)$p['id_pedido']; ?></b> · <?php echo h(date('d/m/Y H:i', strtotime($p['fecha']))); ?>
<br>
          <small><?php echo h($p['cliente']); ?> · <?php echo h($p['correo']); ?><?php echo $p['telefono'] ? ' · ' . h($p['telefono']) : ''; ?></small>
        </div>
        <div class="total">$<?php echo number_format((float)$p['total'], 2); ?></div>
      </header>
      <ul class="renglones">
        <?php foreach ($detalles[$p['id_pedido']] ?? [] as $d): ?>
          <li><?php echo (int)$d['cantidad']; ?> × <?php echo h($d['nombre']); ?><?php echo $d['talla'] !== '' ? ' (talla ' . h($d['talla']) . ')' : ''; ?> — $<?php echo number_format($d['cantidad'] * $d['precio_unitario'], 2); ?></li>
        <?php endforeach; ?>
      </ul>
      <form method="post" class="inline-form" data-confirmar="¿Cambiar el estado del pedido #<?php echo (int)$p['id_pedido']; ?>? Si lo cancelas, las existencias regresan al inventario.">
        <?php echo campoCsrf(); ?>
        <input type="hidden" name="accion" value="estado">
        <input type="hidden" name="id_pedido" value="<?php echo (int)$p['id_pedido']; ?>">
        <label>Estado
          <select name="estado" <?php echo $p['estado'] === 'entregado' ? 'disabled' : ''; ?>>
            <?php foreach (ESTADOS_PEDIDO as $e): ?><option value="<?php echo $e; ?>" <?php echo $e === $p['estado'] ? 'selected' : ''; ?>><?php echo ucfirst($e); ?></option><?php endforeach; ?>
          </select>
        </label>
        <?php if ($p['estado'] !== 'entregado'): ?><button class="boton chico" type="submit">Cambiar estado</button><?php else: ?><span class="chip">Entregado</span><?php endif; ?>
      </form>
    </article>
  <?php endforeach; ?>
</section>
<?php pieAdmin(); ?>
