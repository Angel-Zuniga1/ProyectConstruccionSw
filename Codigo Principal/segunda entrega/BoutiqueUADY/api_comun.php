<?php
/*
  Funciones compartidas por las APIs en PHP (Boutique UADY).
  Equivale a JsonUtil y SesionUtil de la version Java.
*/

function responder($datos, $codigo = 200) {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit();
}

function responderError($mensaje, $codigo) {
    responder(['error' => $mensaje], $codigo);
}

/*
  Equivale a AutenticacionFilter de la version Java: las APIs privadas
  (carrito, pedidos, comprobante) la llaman al inicio. Devuelve el id del
  usuario con sesion o corta la peticion con 401.
*/
function exigirSesion() {
    session_start();
    if (!isset($_SESSION['usuario_id'])) {
        responderError('Sin sesion', 401);
    }
    $idUsuario = (int)$_SESSION['usuario_id'];
    session_write_close(); // libera la sesion para no bloquear otras peticiones
    return $idUsuario;
}
