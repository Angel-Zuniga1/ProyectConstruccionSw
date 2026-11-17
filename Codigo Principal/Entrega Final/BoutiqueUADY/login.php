<?php
// Conexión a la base de datos y manejo de sesiones
require 'conexion.php';
session_start();

// Si la peticion viene de auth.js (fetch) se responde JSON igual que la version Java;
// si viene del formulario normal se muestra la pagina con el mensaje (como en la 1.5).
$porFetch = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'fetch';
function respuestaJson($ok, $mensaje, $redirigir = null) {
    header('Content-Type: application/json; charset=utf-8');
    $r = ['ok' => $ok, 'mensaje' => $mensaje];
    if ($redirigir !== null) { $r['redirigir'] = $redirigir; }
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
    exit();
}

$mensaje = "";

// Si el usuario ya está logueado, lo mandamos al catálogo
if (isset($_SESSION['usuario_id'])) {
    // Si falta la cookie de la barra (sesion anterior), se vuelve a crear
    if (!isset($_COOKIE['usuario_nombre'])) {
        setrawcookie('usuario_nombre', rawurlencode($_SESSION['usuario_nombre']), ['expires' => 0, 'path' => '/', 'samesite' => 'Lax']);
    }
    if ($porFetch) { respuestaJson(true, "", "catalogo.html"); }
    header("Location: catalogo.html");
    exit();
}

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $error = "";

    if (empty($correo) || empty($password)) {
        $error = "Por favor, ingresa tu correo y contraseña.";
    } else {
        // PASO 1 DE ADMINISTRADORES: la cuenta "llave" (rol 'llave' en la tabla administradores,
        // de pruebas: admin@uady.com / 12345678) NO crea sesion de cliente ni cookie de saludo;
        // solo deja una marca temporal y manda al login de administradores (admin/login_admin.php),
        // donde el principal o los de sucursal entran con su propia cuenta (paso 2).
        $stmtAdmin = $conexion->prepare("SELECT password_hash, activo FROM administradores WHERE correo = ? AND rol = 'llave'");
        $stmtAdmin->bind_param("s", $correo);
        $stmtAdmin->execute();
        $admin = $stmtAdmin->get_result()->fetch_assoc();
        $stmtAdmin->close();
        if ($admin && password_verify($password, $admin['password_hash'])) {
            if (!$admin['activo']) {
                $error = "Correo o contraseña incorrectos.";
            } else {
                session_regenerate_id(true);
                $_SESSION['llave_admin'] = true;
                if ($porFetch) { respuestaJson(true, "", "admin/login_admin.php"); }
                header("Location: admin/login_admin.php");
                exit();
            }
        }
    }

    if ($error === "" && !empty($correo) && !empty($password)) {
        // Buscar al usuario (cliente) en la base de datos por su correo
        $stmt = $conexion->prepare("SELECT id_usuario, nombre, password_hash FROM usuarios WHERE correo = ?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();

            // Verificar si la contraseña ingresada coincide con la encriptada en la BD
            if (password_verify($password, $usuario['password_hash'])) {
                // ¡Contraseña correcta! Creamos la sesión con un id nuevo (evita fijación de sesión)
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = $usuario['id_usuario'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];

                // Cookie con el nombre para que las paginas .html muestren el saludo
                setrawcookie('usuario_nombre', rawurlencode($usuario['nombre']), ['expires' => 0, 'path' => '/', 'samesite' => 'Lax']);

                // Redirigir al catálogo
                if ($porFetch) { respuestaJson(true, "", "catalogo.html"); }
                header("Location: catalogo.html");
                exit();
            } else {
                // Mensaje generico: no se revela si el correo existe o no
                $error = "Correo o contraseña incorrectos.";
            }
        } else {
            $error = "Correo o contraseña incorrectos.";
        }
        $stmt->close();
    }
    if ($porFetch) { respuestaJson(false, $error); }
    $mensaje = "<div class='error'>" . htmlspecialchars($error) . "</div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión | Boutique UADY</title>
  <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>
<header class="barra">
  <!-- Ahora el inicio es index.php -->
  <a class="marca" href="index.php">Boutique <b>UADY</b></a>
  <nav>
    <a href="catalogo.html">Catálogo</a>
    <a href="login.php">Iniciar sesión</a>
    <a class="boton" href="registro.php">Crear cuenta</a>
  </nav>
</header>
<main>
<section class="formulario">
  <h1>Iniciar sesión</h1>

  <!-- Aquí se muestran los mensajes de error (PHP al recargar, auth.js sin recargar) -->
  <div id="mensaje"><?php echo $mensaje; ?></div>

  <form action="login.php" method="post" id="formulario" data-accion="login" novalidate>
    <label>Correo <input type="email" name="correo"></label>
    <label>Contraseña <input type="password" name="password"></label>
    <button class="boton grande" type="submit">Entrar</button>
  </form>
  <p class="aparte">¿Aún no tienes cuenta? <a href="registro.php">Créala aquí</a></p>
</section>
</main>
<footer class="pie">
  Boutique UADY en línea · Proyecto integrador de Desarrollo de Aplicaciones Web · Facultad de Matemáticas
</footer>
  <script src="assets/js/validacion.js"></script>
  <script src="assets/js/script.js"></script>
  <script src="assets/js/auth.js"></script>
</body>
</html>
