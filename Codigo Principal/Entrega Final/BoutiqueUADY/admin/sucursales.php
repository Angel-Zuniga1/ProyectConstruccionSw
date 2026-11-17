<?php
/* Solo el principal: ver y agregar sucursales; desde aqui se consulta el inventario y las ventas de cada una. */
require 'admin_comun.php';
exigirAdmin('principal');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'crear') {
            $nombre = trim($_POST['nombre'] ?? '');
            if ($nombre === '') { throw new RuntimeException('Escribe el nombre de la sucursal.'); }
            $stmt = $conexion->prepare("SELECT 1 FROM sucursales WHERE nombre = ?");
            $stmt->bind_param("s", $nombre);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) { throw new RuntimeException('Ya existe una sucursal con ese nombre.'); }
            $stmt = $conexion->prepare("INSERT INTO sucursales (nombre, activa) VALUES (?, 1)");
            $stmt->bind_param("s", $nombre);
            $stmt->execute();
            irA('sucursales.php', "Sucursal \"$nombre\" agregada. Ahora crea su administrador en Administradores.");
        } elseif ($accion === 'activar' || $accion === 'desactivar') {
            $id = (int)$_POST['id_sucursal'];
            $activa = $accion === 'activar' ? 1 : 0;
            $stmt = $conexion->prepare("UPDATE sucursales SET activa = ? WHERE id_sucursal = ?");
            $stmt->bind_param("ii", $activa, $id);
            $stmt->execute();
            irA('sucursales.php', $activa ? 'Sucursal activada: vuelve a aparecer en la tienda.' : 'Sucursal desactivada: deja de aparecer en la tienda (su inventario y pedidos se conservan).');
        }
    } catch (RuntimeException $e) {
        irA('sucursales.php', null, $e->getMessage());
    } catch (mysqli_sql_exception $e) {
        irA('sucursales.php', null, 'No se pudo guardar la sucursal.');
    }
}

$sucursales = listarSucursales($conexion);
cabeceraAdmin('Sucursales', 'sucursales.php');
avisos();
?>
<section class="bloque">
  <h2>Agregar sucursal</h2>
  <form method="post" class="form-admin" data-validar data-confirmar="¿Agregar esta sucursal?">
    <?php echo campoCsrf(); ?>
    <input type="hidden" name="accion" value="crear">
    <label>Nombre de la sucursal <input name="nombre" required placeholder="Ej. Ingeniería"></label>
    <div class="acciones"><button class="boton" type="submit">Agregar</button></div>
  </form>
</section>

<section class="bloque">
  <h2>Sucursales (<?php echo count($sucursales); ?>)</h2>
  <div class="tabla-scroll">
  <table class="tabla-admin">
    <thead><tr><th>#</th><th>Sucursal</th><th>Estado</th><th>Administradores</th><th>Productos</th><th>Pedidos</th><th>Ventas</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($sucursales as $s):
        $id = (int)$s['id_sucursal'];
        $r = resumenVentas($conexion, $id);
        $admins = $conexion->query("SELECT GROUP_CONCAT(nombre SEPARATOR ', ') n FROM administradores WHERE rol = 'sucursal' AND activo = 1 AND id_sucursal = $id")->fetch_assoc()['n'];
        $prods = (int)$conexion->query("SELECT COUNT(DISTINCT id_producto) c FROM inventario WHERE id_sucursal = $id")->fetch_assoc()['c'];
    ?>
      <tr class="<?php echo $s['activa'] ? '' : 'inactivo'; ?>">
        <td><?php echo $id; ?></td>
        <td><?php echo h($s['nombre']); ?></td>
        <td><?php echo $s['activa'] ? 'Activa' : 'Inactiva'; ?></td>
        <td><?php echo $admins ? h($admins) : '<small>Sin administrador</small>'; ?></td>
        <td><?php echo $prods; ?></td>
        <td><?php echo $r['pedidos']; ?> (<?php echo $r['por_estado']['pendiente']; ?> pend.)</td>
        <td>$<?php echo number_format($r['ventas'], 2); ?></td>
        <td class="acciones">
          <a class="boton chico" href="sucursal_detalle.php?id=<?php echo $id; ?>">Inventario y ventas</a>
          <form method="post" class="inline" data-confirmar="¿<?php echo $s['activa'] ? 'Desactivar' : 'Activar'; ?> la sucursal <?php echo h($s['nombre']); ?>?">
            <?php echo campoCsrf(); ?>
            <input type="hidden" name="id_sucursal" value="<?php echo $id; ?>">
            <input type="hidden" name="accion" value="<?php echo $s['activa'] ? 'desactivar' : 'activar'; ?>">
            <button class="boton chico <?php echo $s['activa'] ? 'peligro' : ''; ?>" type="submit"><?php echo $s['activa'] ? 'Desactivar' : 'Activar'; ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</section>
<?php pieAdmin(); ?>
