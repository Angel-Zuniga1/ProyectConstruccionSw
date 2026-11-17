<?php
/* RF-10 (administrador de sucursal): existencias por talla de SU sucursal. */
require 'admin_comun.php';
exigirAdmin('sucursal');

$idSuc = sucursalActual();      // siempre la sucursal asignada al administrador
$volver = 'inventario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'guardar') {
            // Guarda todas las cantidades de la tabla en una transaccion
            $conexion->begin_transaction();
            $stmt = $conexion->prepare("UPDATE inventario SET existencias = ? WHERE id_producto = ? AND id_sucursal = ? AND talla = ?");
            foreach ($_POST['existencias'] ?? [] as $idProducto => $tallas) {
                foreach ($tallas as $talla => $cantidad) {
                    $cantidad = max(0, (int)$cantidad);
                    $idProducto = (int)$idProducto;
                    $talla = (string)$talla;
                    $stmt->bind_param("iiis", $cantidad, $idProducto, $idSuc, $talla);
                    $stmt->execute();
                }
            }
            $conexion->commit();
            irA($volver, 'Existencias actualizadas.');
        } elseif ($accion === 'agregar') {
            // Da de alta un producto/talla que aun no existe en esta sucursal
            $idProducto = (int)($_POST['id_producto'] ?? 0);
            $talla = $_POST['talla'] ?? '';
            $cantidad = max(0, (int)($_POST['cantidad'] ?? 0));
            if ($idProducto <= 0 || !in_array($talla, ['', 'XS', 'S', 'M', 'L', 'XL', '2XL'], true)) {
                throw new RuntimeException('Elige un producto y una talla válidos.');
            }
            $stmt = $conexion->prepare("INSERT INTO inventario (id_producto, id_sucursal, talla, existencias) VALUES (?, ?, ?, ?)
                                        ON DUPLICATE KEY UPDATE existencias = VALUES(existencias)");
            $stmt->bind_param("iisi", $idProducto, $idSuc, $talla, $cantidad);
            $stmt->execute();
            irA($volver, 'Talla agregada al inventario de tu sucursal.');
        }
    } catch (RuntimeException $e) {
        irA($volver, null, $e->getMessage());
    } catch (mysqli_sql_exception $e) {
        $conexion->rollback();
        irA($volver, null, 'No se pudo actualizar el inventario.');
    }
}

// Inventario de la sucursal agrupado por producto
$stmt = $conexion->prepare(
    "SELECT p.id_producto, p.nombre, p.activo, i.talla, i.existencias
     FROM inventario i JOIN productos p ON p.id_producto = i.id_producto
     WHERE i.id_sucursal = ?
     ORDER BY p.nombre, FIELD(i.talla, '', 'XS', 'S', 'M', 'L', 'XL', '2XL')");
$stmt->bind_param("i", $idSuc);
$stmt->execute();
$filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$porProducto = [];
foreach ($filas as $f) { $porProducto[$f['id_producto']]['nombre'] = $f['nombre']; $porProducto[$f['id_producto']]['activo'] = $f['activo']; $porProducto[$f['id_producto']]['tallas'][] = $f; }

$stmt = $conexion->prepare("SELECT DISTINCT p.id_producto, p.nombre FROM productos p JOIN inventario i ON i.id_producto = p.id_producto WHERE i.id_sucursal = ? AND p.activo = 1 ORDER BY p.nombre");
$stmt->bind_param("i", $idSuc);
$stmt->execute();
$productosActivos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

cabeceraAdmin('Inventario · ' . h($_SESSION['admin_sucursal_nombre']), 'inventario.php');
avisos();
?>

<section class="bloque">
  <h2>Existencias por producto y talla</h2>
  <p class="aparte">Cambia las cantidades y pulsa <b>Guardar existencias</b>. Las tallas en 0 aparecen como agotadas en la tienda.</p>
  <form method="post" data-confirmar="¿Guardar las existencias capturadas?">
    <?php echo campoCsrf(); ?>
    <input type="hidden" name="accion" value="guardar">
    <div class="tabla-scroll">
    <table class="tabla-admin">
      <thead><tr><th>Producto</th><th>Talla</th><th>Existencias</th></tr></thead>
      <tbody>
      <?php foreach ($porProducto as $idP => $prod): ?>
        <?php foreach ($prod['tallas'] as $k => $t): ?>
          <tr class="<?php echo $prod['activo'] ? '' : 'inactivo'; ?> <?php echo (int)$t['existencias'] === 0 ? 'agotado' : ''; ?>">
            <?php if ($k === 0): ?><td id="p<?php echo (int)$idP; ?>" rowspan="<?php echo count($prod['tallas']); ?>"><?php echo h($prod['nombre']); ?><?php echo $prod['activo'] ? '' : ' <small>(desactivado)</small>'; ?></td><?php endif; ?>
            <td><?php echo $t['talla'] === '' ? 'Única' : h($t['talla']); ?></td>
            <td><input class="numero" type="number" min="0" name="existencias[<?php echo (int)$idP; ?>][<?php echo h($t['talla']); ?>]" value="<?php echo (int)$t['existencias']; ?>"></td>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <?php if (count($porProducto) === 0): ?><tr><td colspan="3">Tu sucursal aún no tiene inventario. Da de alta productos en <a href="productos.php">Productos</a>.</td></tr><?php endif; ?>
      </tbody>
    </table>
    </div>
    <div class="acciones"><button class="boton" type="submit">Guardar existencias</button></div>
  </form>
</section>

<section class="bloque">
  <h2>Agregar una talla a un producto de tu sucursal</h2>
  <p class="aparte">Para dar de alta un producto nuevo o traer uno del catálogo, usa <a href="productos.php">Productos</a>.</p>
  <form method="post" class="form-admin" data-confirmar="¿Agregar esta talla al inventario?">
    <?php echo campoCsrf(); ?>
    <input type="hidden" name="accion" value="agregar">
    <label>Producto
      <select name="id_producto" required>
        <?php foreach ($productosActivos as $p): ?><option value="<?php echo $p['id_producto']; ?>"><?php echo h($p['nombre']); ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Talla
      <select name="talla">
        <option value="">Única (sin talla)</option>
        <?php foreach (['XS', 'S', 'M', 'L', 'XL', '2XL'] as $t): ?><option><?php echo $t; ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Existencias <input class="numero" type="number" name="cantidad" min="0" value="0"></label>
    <div class="acciones"><button class="boton" type="submit">Agregar</button></div>
  </form>
</section>
<?php pieAdmin(); ?>
