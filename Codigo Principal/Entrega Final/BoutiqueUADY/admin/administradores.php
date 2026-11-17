<?php
/* Solo el administrador principal: crea y administra las cuentas de administrador de sucursal. */
require 'admin_comun.php';
exigirAdmin('principal');

$sucursales = listarSucursales($conexion, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'crear') {
            $nombre = trim($_POST['nombre'] ?? '');
            $correo = trim($_POST['correo'] ?? '');
            $password = $_POST['password'] ?? '';
            $idSucursal = (int)($_POST['id_sucursal'] ?? 0);
            if ($nombre === '' || $correo === '' || $password === '' || $idSucursal <= 0) {
                throw new RuntimeException('Todos los campos son obligatorios.');
            }
            if (strlen($password) < 6) { throw new RuntimeException('La contraseña debe tener al menos 6 caracteres.'); }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("INSERT INTO administradores (nombre, correo, password_hash, rol, id_sucursal) VALUES (?, ?, ?, 'sucursal', ?)");
            $stmt->bind_param("sssi", $nombre, $correo, $hash, $idSucursal);
            $stmt->execute();
            irA('administradores.php', "Cuenta creada. $correo ya puede entrar como administrador de sucursal.");
        } elseif ($accion === 'activar' || $accion === 'desactivar') {
            $id = (int)$_POST['id_admin'];
            if ($id === (int)$_SESSION['admin_id']) { throw new RuntimeException('No puedes desactivar tu propia cuenta.'); }
            $activo = $accion === 'activar' ? 1 : 0;
            $stmt = $conexion->prepare("UPDATE administradores SET activo = ? WHERE id_admin = ? AND rol = 'sucursal'");
            $stmt->bind_param("ii", $activo, $id);
            $stmt->execute();
            irA('administradores.php', $activo ? 'Cuenta activada.' : 'Cuenta desactivada.');
        } elseif ($accion === 'password') {
            $id = (int)$_POST['id_admin'];
            $password = $_POST['password'] ?? '';
            if (strlen($password) < 6) { throw new RuntimeException('La contraseña debe tener al menos 6 caracteres.'); }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("UPDATE administradores SET password_hash = ? WHERE id_admin = ? AND rol = 'sucursal'");
            $stmt->bind_param("si", $hash, $id);
            $stmt->execute();
            irA('administradores.php', 'Contraseña actualizada.');
        }
    } catch (RuntimeException $e) {
        irA('administradores.php', null, $e->getMessage());
    } catch (mysqli_sql_exception $e) {
        irA('administradores.php', null, $e->getCode() == 1062 ? 'Ese correo ya tiene una cuenta de administrador.' : 'No se pudo guardar.');
    }
}

$lista = $conexion->query(
    "SELECT a.*, s.nombre AS sucursal FROM administradores a LEFT JOIN sucursales s ON s.id_sucursal = a.id_sucursal
     WHERE a.rol IN ('principal', 'sucursal') ORDER BY a.rol = 'principal' DESC, a.id_admin")->fetch_all(MYSQLI_ASSOC);

cabeceraAdmin('Administradores', 'administradores.php');
avisos();
?>
<section class="bloque">
  <h2>Crear administrador de sucursal</h2>
  <form method="post" class="form-admin" autocomplete="off" data-validar data-confirmar="¿Crear esta cuenta de administrador de sucursal?">
    <?php echo campoCsrf(); ?>
    <input type="hidden" name="accion" value="crear">
    <label>Nombre <input name="nombre" required></label>
    <label>Correo <input type="email" name="correo" required></label>
    <label>Contraseña <input type="password" name="password" minlength="6" required></label>
    <label>Sucursal
      <select name="id_sucursal" required>
        <?php foreach ($sucursales as $s): ?><option value="<?php echo $s['id_sucursal']; ?>"><?php echo h($s['nombre']); ?></option><?php endforeach; ?>
      </select>
    </label>
    <div class="acciones"><button class="boton" type="submit">Crear cuenta</button></div>
  </form>
  <?php if (!$sucursales): ?><div class="error">No hay sucursales activas: agrega una en <a href="sucursales.php">Sucursales</a>.</div><?php endif; ?>
  <p class="aparte">El administrador de sucursal entra igual que tú: en la tienda, <b>Iniciar sesión</b> con la cuenta llave (admin@uady.com) y después, en el login de administradores, con el correo y la contraseña que le creaste aquí.</p>
</section>

<section class="bloque">
  <h2>Cuentas existentes</h2>
  <div class="tabla-scroll">
  <table class="tabla-admin">
    <thead><tr><th>#</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Sucursal</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $a): ?>
      <tr class="<?php echo $a['activo'] ? '' : 'inactivo'; ?>">
        <td><?php echo (int)$a['id_admin']; ?></td>
        <td><?php echo h($a['nombre']); ?></td>
        <td><?php echo h($a['correo']); ?></td>
        <td><?php echo $a['rol'] === 'principal' ? 'Principal' : 'Sucursal'; ?></td>
        <td><?php echo h($a['sucursal'] ?? '—'); ?></td>
        <td><?php echo $a['activo'] ? 'Activa' : 'Desactivada'; ?></td>
        <td class="acciones">
          <?php if ($a['rol'] === 'sucursal'): ?>
            <form method="post" class="inline" data-confirmar="¿<?php echo $a['activo'] ? 'Desactivar' : 'Activar'; ?> la cuenta de <?php echo h($a['nombre']); ?>?">
              <?php echo campoCsrf(); ?>
              <input type="hidden" name="id_admin" value="<?php echo (int)$a['id_admin']; ?>">
              <input type="hidden" name="accion" value="<?php echo $a['activo'] ? 'desactivar' : 'activar'; ?>">
              <button class="boton chico <?php echo $a['activo'] ? 'peligro' : ''; ?>" type="submit"><?php echo $a['activo'] ? 'Desactivar' : 'Activar'; ?></button>
            </form>
            <form method="post" class="inline" autocomplete="off" data-validar data-confirmar="¿Cambiar la contraseña de <?php echo h($a['nombre']); ?>?">
              <?php echo campoCsrf(); ?>
              <input type="hidden" name="id_admin" value="<?php echo (int)$a['id_admin']; ?>">
              <input type="hidden" name="accion" value="password">
              <input type="password" name="password" placeholder="Nueva contraseña" minlength="6" required class="chico">
              <button class="boton chico" type="submit">Cambiar</button>
            </form>
          <?php else: ?>—<?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</section>
<?php pieAdmin(); ?>
