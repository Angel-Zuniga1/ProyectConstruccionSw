<?php
// ==========================================
// CONEXIÓN A LA BASE DE DATOS (BOUTIQUE UADY)
// ==========================================

$host = "localhost";
$usuario_bd = "root";       
$password_bd = "";          
$nombre_bd = "boutique_uady"; 

$conexion = new mysqli($host, $usuario_bd, $password_bd, $nombre_bd);

if ($conexion->connect_error) {
    die("Error de conexión a la base de datos: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
?>