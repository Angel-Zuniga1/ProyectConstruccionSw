<?php
/*
  PASO 2: login de administradores. Solo se muestra si en el login de la tienda se entro con la
  cuenta llave (admin@uady.com). Aqui entran el administrador principal y los administradores de
  sucursal con su propia cuenta; el rol lo decide la tabla administradores.
*/
require 'admin_comun.php';
exigirLlave();

if (!empty($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($correo === '' || $password === '') {
        $mensaje = "<div class='error'>Ingresa tu correo y contraseña de administrador.</div>";
    } else {
        $stmt = $conexion->prepare(
            "SELECT a.id_admin, a.nombre, a.password_hash, a.rol, a.id_sucursal, a.activo, s.nombre AS sucursal
             FROM administradores a LEFT JOIN sucursales s ON s.id_sucursal = a.id_sucursal
             WHERE a.correo = ? AND a.rol IN ('principal', 'sucursal')");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            $mensaje = "<div class='error'>Correo o contraseña incorrectos.</div>";
        } elseif (!$admin['activo']) {
            $mensaje = "<div class='error'>Esta cuenta de administrador está desactivada.</div>";
        } else {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id_admin'];
            $_SESSION['admin_nombre'] = $admin['nombre'];
            $_SESSION['admin_rol'] = $admin['rol'];
            $_SESSION['admin_sucursal'] = $admin['id_sucursal'] !== null ? (int)$admin['id_sucursal'] : null;
            $_SESSION['admin_sucursal_nombre'] = $admin['sucursal'];
            unset($_SESSION['llave_admin']);
            $saludo = $admin['rol'] === 'principal' ? 'Bienvenido, ' . $admin['nombre'] . ' (administrador principal).'
                                                     : 'Bienvenido, ' . $admin['nombre'] . '. Estás administrando la sucursal ' . $admin['sucursal'] . '.';
            header("Location: index.php?ok=" . rawurlencode($saludo));
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Acceso de administradores | Boutique UADY</title>
  <link rel="stylesheet" href="../assets/css/estilo.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<header class="barra">
  <a class="marca" href="../index.php">Boutique <b>UADY</b> <span class="etiqueta-admin">ADMIN</span></a>
  <nav><a href="../index.php">Volver a la tienda</a></nav>
</header>
<main>
<section class="formulario">
  <h1>Acceso de administradores</h1>
  <p class="aparte">Paso 2 de 2. Entra con tu cuenta de administrador principal o de sucursal.</p>
  <?php echo $mensaje; ?>
  <form action="login_admin.php" method="post" id="formulario" data-validar novalidate>
    <label>Correo <input type="email" name="correo" required value="<?php echo h($_POST['correo'] ?? ''); ?>"></label>
    <label>Contraseña <input type="password" name="password" required></label>
    <button class="boton grande" type="submit">Entrar al panel</button>
  </form>
</section>
</main>
<footer class="pie">Boutique UADY en línea · Panel de administración</footer>
<script src="../assets/js/script.js"></script>
<script src="../assets/js/admin.js"></script>
<script>
  // Si el login de administradores devolvió un error, también se avisa con alert
  const err = document.querySelector('.formulario .error');
  if (err) { alert(err.textContent); }
</script>
</body>
</html>
