<?php
require 'admin_comun.php';
exigirAdmin();
cabeceraAdmin('Panel de administración', 'index.php');
avisos();

if (esPrincipal()):
    // ----- PRINCIPAL: vista global, solo supervisión -----
    $global = resumenVentas($conexion, 0);
    $sucursales = listarSucursales($conexion);
    $admins = (int)$conexion->query("SELECT COUNT(*) c FROM administradores WHERE rol = 'sucursal' AND activo = 1")->fetch_assoc()['c'];
?>
<div class="tarjetas-resumen">
  <div class="resumen"><b>$<?php echo number_format($global['ventas'], 2); ?></b><span>Ventas globales (no canceladas)</span></div>
  <a class="resumen" href="sucursales.php"><b><?php echo $global['por_estado']['pendiente']; ?></b><span>Pedidos pendientes en total</span></a>
  <a class="resumen" href="sucursales.php"><b><?php echo count(array_filter($sucursales, fn($s) => $s['activa'])); ?></b><span>Sucursales activas</span></a>
  <a class="resumen" href="administradores.php"><b><?php echo $admins; ?></b><span>Admins. de sucursal activos</span></a>
</div>
<section class="bloque">
  <h2>Resumen por sucursal</h2>
  <div class="tabla-scroll">
  <table class="tabla-admin">
    <thead><tr><th>Sucursal</th><th>Estado</th><th>Pedidos</th><th>Pendientes</th><th>Listos</th><th>Entregados</th><th>Cancelados</th><th>Ventas</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($sucursales as $s): $r = resumenVentas($conexion, (int)$s['id_sucursal']); ?>
      <tr class="<?php echo $s['activa'] ? '' : 'inactivo'; ?>">
        <td><?php echo h($s['nombre']); ?></td>
        <td><?php echo $s['activa'] ? 'Activa' : 'Inactiva'; ?></td>
        <td><?php echo $r['pedidos']; ?></td>
        <td><?php echo $r['por_estado']['pendiente']; ?></td>
        <td><?php echo $r['por_estado']['listo para recoger']; ?></td>
        <td><?php echo $r['por_estado']['entregado']; ?></td>
        <td><?php echo $r['por_estado']['cancelado']; ?></td>
        <td>$<?php echo number_format($r['ventas'], 2); ?></td>
        <td><a class="boton chico" href="sucursal_detalle.php?id=<?php echo (int)$s['id_sucursal']; ?>">Ver inventario y ventas</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</section>
<section class="bloque">
  <h2>Tu papel como administrador principal</h2>
  <ul>
    <li><b>Sucursales:</b> ver y agregar sucursales, y consultar el inventario y las ventas de cada una.</li>
    <li><b>Administradores:</b> crear las cuentas de los administradores de sucursal y administrarlas.</li>
    <li>El alta de prendas, las tallas, las existencias y los pedidos los lleva cada administrador de sucursal.</li>
  </ul>
</section>
<?php
else:
    // ----- SUCURSAL: solo su sucursal -----
    $idSuc = sucursalActual();
    $r = resumenVentas($conexion, $idSuc);
    $agotados = (int)$conexion->query("SELECT COUNT(*) c FROM inventario WHERE id_sucursal = $idSuc AND existencias = 0")->fetch_assoc()['c'];
    $productos = (int)$conexion->query("SELECT COUNT(DISTINCT i.id_producto) c FROM inventario i JOIN productos p ON p.id_producto = i.id_producto WHERE i.id_sucursal = $idSuc AND p.activo = 1")->fetch_assoc()['c'];
?>
<div class="tarjetas-resumen">
  <a class="resumen" href="pedidos.php?estado=pendiente"><b><?php echo $r['por_estado']['pendiente']; ?></b><span>Pedidos pendientes</span></a>
  <a class="resumen" href="pedidos.php?estado=listo+para+recoger"><b><?php echo $r['por_estado']['listo para recoger']; ?></b><span>Listos para recoger</span></a>
  <a class="resumen" href="inventario.php"><b><?php echo $agotados; ?></b><span>Tallas agotadas</span></a>
  <a class="resumen" href="productos.php"><b><?php echo $productos; ?></b><span>Productos en tu sucursal</span></a>
  <div class="resumen"><b>$<?php echo number_format($r['ventas'], 2); ?></b><span>Ventas de tu sucursal</span></div>
</div>
<section class="bloque">
  <h2>Qué administras en <?php echo h($_SESSION['admin_sucursal_nombre']); ?></h2>
  <ul>
    <li><b>Productos:</b> dar de alta prendas y accesorios en tu sucursal, modificarlos y darlos de baja (RF-09).</li>
    <li><b>Inventario:</b> existencias por talla de tu sucursal (RF-10).</li>
    <li><b>Pedidos:</b> ventas de tu sucursal y su estado: pendiente, listo para recoger, entregado o cancelado (RF-11).</li>
  </ul>
</section>
<?php endif; pieAdmin(); ?>
