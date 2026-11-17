<?php
session_start();
session_unset();
session_destroy();
// Borrar la cookie del saludo
setcookie('usuario_nombre', '', ['expires' => time() - 3600, 'path' => '/']);
header("Location: index.php");
exit();
?>