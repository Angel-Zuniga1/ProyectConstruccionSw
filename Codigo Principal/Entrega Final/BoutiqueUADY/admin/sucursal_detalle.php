<?php
/* Solo el principal: inventario y ventas de UNA sucursal, solo consulta (no modifica nada). */
require 'admin_comun.php';
exigirAdmin('principal');

$id = (int)($_GET['id'] ?? 0);
$nombre = nombreSucursal($conexion, $id);
if ($nombre === '') { irA('sucursales.php', null, 'Esa sucursal no existe.'); }

$r = resumenVentas($conexion, $id);
$inventario = inventarioDe($conexion, $id);
$valorInventario = 0;
foreach ($inventario as $p) { foreach ($p['tallas'] as $t) { $valorInventario += $t['existencias'] * $p['precio']; } }

$stmt = $conexion->prepare(
    "SELECT p.id_pedido, p.fecha, p.total, p.estado, CONCAT(u.nombre, ' ', u.apellidos) AS cliente
     FROM pedidos p JOIN usuarios u ON u.id_usuario = p.id_usuario
     WHERE p.id_sucursal = ? ORDER BY p.id_pedido DESC LIMIT 100");
$stmt->bind_param("i", $id);
$stmt->execute();
$pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Productos mas vendidos (sin cancelados)
$stmt = $conexion->prepare(
    "SELECT pr.nombre, SUM(d.cantidad) unidades, SUM(d.cantidad * d.precio_unitario) importe
     FROM detalle_pedido d JOIN pedidos p ON p.id_pedido = d.id_pedido JOIN productos pr ON pr.id_producto = d.id_producto
     WHERE p.id_sucursal = ? AND p.estado <> 'cancelado' GROUP BY pr.id_producto ORDER BY unidades DESC LIMIT 10");
$stmt->bind_param("i", $id);
$stmt->execute();
$masVendidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

cabeceraAdmin('Sucursal ' . $nombre, 'sucursales.php');
avisos();
?>
<p class="aparte"><a href="sucursales.php">&larr; Volver a sucursales</a> · Vista de consulta: los cambios los hace el administrador de la sucursal.</p>
<div class="tarjetas-resumen">
  <div class="resumen"><b>$<?php echo number_format($r['ventas'], 2); ?></b><span>Ventas (no canceladas)</span></div>
  <div class="resumen"><b><?php echo $r['pedidos']; ?></b><span>Pedidos en total</span></div>
  <div class="resumen"><b><?php echo $r['por_estado']['pendiente']; ?></b><span>Pendientes</span></div>
  <div class="resumen"><b><?php echo count($inventario); ?></b><span>Productos en inventario</span></div>
  <div class="resumen"><b>$<?php echo number_format($valorInventario, 2); ?></b><span>Valor del inventario</span></div>
</div>

<section class="bloque">
  <h2>Ventas por estado</h2>
  <table class="tabla-admin">
    <thead><tr><?php foreach (ESTADOS_PEDIDO as $e): ?><th><?php echo ucfirst($e); ?></th><?php endforeach; ?></tr></thead>
    <tbody><tr><?php foreach (ESTADOS_PEDIDO as $e): ?><td><?php echo $r['por_estado'][$e]; ?></td><?php endforeach; ?></tr></tbody>
  </table>
</section>

<section class="bloque">
  <h2>Productos más vendidos</h2>
  <?php if (!$masVendidos): ?><p>Aún no hay ventas.</p><?php else: ?>
  <table class="tabla-admin">
    <thead><tr><th>Producto</th><th>Unidades</th><th>Importe</th></tr></thead>
    <tbody><?php foreach ($masVendidos as $m): ?><tr><td><?php echo h($m['nombre']); ?></td><td><?php echo (int)$m['unidades']; ?></td><td>$<?php echo number_format((float)$m['importe'], 2); ?></td></tr><?php endforeach; ?></tbody>
  </table>
  <?php endif; ?>
</section>

<section class="bloque">
  <h2>Inventario de <?php echo h($nombre); ?></h2>
  <div class="tabla-scroll">
  <table class="tabla-admin">
    <thead><tr><th>Producto</th><th>Precio</th><th>Talla</th><th>Existencias</th></tr></thead>
    <tbody>
    <?php foreach ($inventario as $p): foreach ($p['tallas'] as $k => $t): ?>
      <tr class="<?php echo $p['activo'] ? '' : 'inactivo'; ?> <?php echo (int)$t['existencias'] === 0 ? 'agotado' : ''; ?>">
        <?php if ($k === 0): ?><td rowspan="<?php echo count($p['tallas']); ?>"><?php echo h($p['nombre']); ?></td><td rowspan="<?php echo count($p['tallas']); ?>">$<?php echo number_format((float)$p['precio'], 2); ?></td><?php endif; ?>
        <td><?php echo $t['talla'] === '' ? 'Única' : h($t['talla']); ?></td>
        <td><?php echo (int)$t['existencias']; ?></td>
      </tr>
    <?php endforeach; endforeach; ?>
    <?php if (!$inventario): ?><tr><td colspan="4">Esta sucursal aún no tiene inventario.</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</section>

<section class="bloque">
  <h2>Últimos pedidos</h2>
  <div class="tabla-scroll">
  <table class="tabla-admin">
    <thead><tr><th>#</th><th>Fecha</th><th>Cliente</th><th>Total</th><th>Estado</th></tr></thead>
    <tbody>
    <?php foreach ($pedidos as $p): ?>
      <tr><td><?php echo (int)$p['id_pedido']; ?></td><td><?php echo h(date('d/m/Y H:i', strtotime($p['fecha']))); ?></td><td><?php echo h($p['cliente']); ?></td><td>$<?php echo number_format((float)$p['total'], 2); ?></td><td><?php echo h($p['estado']); ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$pedidos): ?><tr><td colspan="5">Sin pedidos.</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</section>
<?php pieAdmin(); ?>
