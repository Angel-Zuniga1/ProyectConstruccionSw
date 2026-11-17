<?php
/*
  GET sesion_api.php -> {"autenticado": true/false, "nombre": "..."}
  Equivale a SesionServlet de la version Java: informa si hay sesion iniciada y
  mantiene en orden la cookie del saludo (la crea si falta y la borra si la sesion
  ya no existe). La usan script.js (barra superior) y auth.js (login y registro).
*/
session_start();
require 'api_comun.php';

$datos = ['autenticado' => false];
if (isset($_SESSION['usuario_id'])) {
    $datos['autenticado'] = true;
    $datos['nombre'] = $_SESSION['usuario_nombre'];
    // Sesion activa pero sin cookie del saludo (sesion anterior): se vuelve a crear
    if (!isset($_COOKIE['usuario_nombre'])) {
        setrawcookie('usuario_nombre', rawurlencode($_SESSION['usuario_nombre']), ['expires' => 0, 'path' => '/', 'samesite' => 'Lax']);
    }
} elseif (isset($_COOKIE['usuario_nombre'])) {
    // La sesion expiro pero la cookie sigue: se borra para no mostrar un saludo falso
    setcookie('usuario_nombre', '', ['expires' => time() - 3600, 'path' => '/']);
}
header('Cache-Control: no-store');
responder($datos);
