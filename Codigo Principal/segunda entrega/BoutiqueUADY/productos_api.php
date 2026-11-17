<?php
/*
  Catalogo publico (equivale a ProductoServlet + CatalogoService + ProductoDAO + InventarioDAO).
  GET productos_api.php?sucursal=1&talla=M -> lista filtrada (sin parametros: todo el catalogo)
  GET productos_api.php?id=3              -> detalle de un producto con su disponibilidad
*/
require 'conexion.php';
require 'api_comun.php';

const SELECT_PRODUCTOS = "SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.imagen,
                                 p.id_categoria, c.nombre AS categoria
                          FROM productos p JOIN categorias c ON c.id_categoria = p.id_categoria
                          WHERE p.activo = 1";

function armarProducto($fila) {
    return [
        'id'            => (int)$fila['id_producto'],
        'nombre'        => $fila['nombre'],
        'descripcion'   => $fila['descripcion'],
        'precio'        => (float)$fila['precio'],
        'imagen'        => $fila['imagen'],
        'idCategoria'   => (int)$fila['id_categoria'],
        'categoria'     => $fila['categoria'],
        'disponibilidad'=> []
    ];
}

function armarDisponibilidad($fila) {
    return [
        'idSucursal'  => (int)$fila['id_sucursal'],
        'sucursal'    => $fila['nombre'],
        'talla'       => $fila['talla'],
        'existencias' => (int)$fila['existencias']
    ];
}

$sqlInventario = "SELECT i.id_producto, i.id_sucursal, s.nombre, i.talla, i.existencias
                  FROM inventario i JOIN sucursales s ON s.id_sucursal = i.id_sucursal
                  WHERE s.activa = 1 %s
                  ORDER BY i.id_producto, i.id_sucursal, FIELD(i.talla, '', 'XS', 'S', 'M', 'L', 'XL', '2XL')";

// ---------- DETALLE de un producto ----------
if (isset($_GET['id']) && $_GET['id'] !== '') {
    if (!ctype_digit((string)$_GET['id'])) {
        responderError('Producto invalido', 400);
    }
    $id = (int)$_GET['id'];

    $stmt = $conexion->prepare(SELECT_PRODUCTOS . " AND p.id_producto = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    if (!$fila) {
        responderError('Producto no encontrado', 404);
    }
    $producto = armarProducto($fila);

    $stmt = $conexion->prepare(sprintf($sqlInventario, "AND i.id_producto = ?"));
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($d = $res->fetch_assoc()) {
        $producto['disponibilidad'][] = armarDisponibilidad($d);
    }
    responder($producto);
}

// ---------- LISTA filtrada por sucursal y talla ----------
$sucursal = isset($_GET['sucursal']) ? trim((string)$_GET['sucursal']) : '';
$talla    = isset($_GET['talla']) ? trim((string)$_GET['talla']) : '';

// Productos activos
$productos = [];
$res = $conexion->query(SELECT_PRODUCTOS . " ORDER BY p.id_producto");
while ($fila = $res->fetch_assoc()) {
    $productos[(int)$fila['id_producto']] = armarProducto($fila);
}

// Inventario de las sucursales activas
$res = $conexion->query(sprintf($sqlInventario, ""));
while ($d = $res->fetch_assoc()) {
    $idProducto = (int)$d['id_producto'];
    if (!isset($productos[$idProducto])) { continue; }

    // Misma regla que CatalogoService.filtrar de la version Java:
    // sin filtros se muestra todo el inventario; con filtro solo lo que tiene existencias
    if ($sucursal === '' && $talla === '') {
        $coincide = true;
    } elseif ($sucursal === '') {
        $coincide = $d['talla'] === $talla && (int)$d['existencias'] > 0;
    } elseif ($talla === '') {
        $coincide = (string)$d['id_sucursal'] === $sucursal && (int)$d['existencias'] > 0;
    } else {
        $coincide = (string)$d['id_sucursal'] === $sucursal && $d['talla'] === $talla && (int)$d['existencias'] > 0;
    }
    if ($coincide) {
        $productos[$idProducto]['disponibilidad'][] = armarDisponibilidad($d);
    }
}

// Solo se devuelven los productos con alguna disponibilidad
$lista = [];
foreach ($productos as $p) {
    if (count($p['disponibilidad']) > 0) {
        $lista[] = $p;
    }
}
responder($lista);
