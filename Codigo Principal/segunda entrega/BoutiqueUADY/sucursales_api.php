<?php
/*
  GET sucursales_api.php -> sucursales activas en JSON [{id, nombre}]
  (equivale a SucursalServlet / SucursalDAO).
*/
require 'conexion.php';
require 'api_comun.php';

$resultado = $conexion->query("SELECT id_sucursal, nombre FROM sucursales WHERE activa = 1 ORDER BY id_sucursal");
$lista = [];
while ($fila = $resultado->fetch_assoc()) {
    $lista[] = ['id' => (int)$fila['id_sucursal'], 'nombre' => $fila['nombre']];
}
responder($lista);
