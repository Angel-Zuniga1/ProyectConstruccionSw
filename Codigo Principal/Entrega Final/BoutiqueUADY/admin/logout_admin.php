<?php
require 'admin_comun.php';
// Solo se cierra la parte de administrador; la llave tambien se descarta
unset($_SESSION['admin_id'], $_SESSION['admin_nombre'], $_SESSION['admin_rol'],
      $_SESSION['admin_sucursal'], $_SESSION['admin_sucursal_nombre'], $_SESSION['llave_admin'], $_SESSION['csrf_admin']);
header("Location: ../index.php");
exit();
