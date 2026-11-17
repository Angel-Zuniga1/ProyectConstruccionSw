<?php
/*
  Pedidos del usuario con sesion (equivale a PedidoServlet + PedidoService + PedidoDAO).
  POST pedidos_api.php  {"idSucursal": 1} -> confirma el carrito como pedido
  GET  pedidos_api.php                    -> historial de pedidos del usuario
  Todo el pedido ocurre en UNA transaccion: si falta existencia de cualquier
  producto no se descuenta nada ni se crea el pedido (todo o nada).
*/
require 'conexion.php';
require 'api_comun.php';

$idUsuario = exigirSesion();

// ---------- HISTORIAL ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conexion->prepare(
        "SELECT p.id_pedido, p.id_sucursal, s.nombre, p.fecha, p.total, p.estado
         FROM pedidos p JOIN sucursales s ON s.id_sucursal = p.id_sucursal
         WHERE p.id_usuario = ? ORDER BY p.id_pedido DESC");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $res = $stmt->get_result();
    $lista = [];
    while ($f = $res->fetch_assoc()) {
        $lista[] = [
            'idPedido'   => (int)$f['id_pedido'],
            'idUsuario'  => $idUsuario,
            'idSucursal' => (int)$f['id_sucursal'],
            'sucursal'   => $f['nombre'],
            'fecha'      => $f['fecha'],
            'total'      => (float)$f['total'],
            'estado'     => $f['estado']
        ];
    }
    responder($lista);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderError('Metodo no permitido', 405);
}

// ---------- CONFIRMAR PEDIDO ----------
$json = json_decode(file_get_contents('php://input'), true);
$idSucursal = (int)($json['idSucursal'] ?? 0);

$conexion->begin_transaction();
try {
    // 1. validar la sucursal
    $stmt = $conexion->prepare("SELECT nombre FROM sucursales WHERE id_sucursal = ? AND activa = 1");
    $stmt->bind_param("i", $idSucursal);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    if (!$fila) {
        $conexion->rollback();
        responderError('Selecciona una sucursal válida.', 400);
    }
    $nombreSucursal = $fila['nombre'];

    // 2. leer el carrito con los precios actuales de la base
    $stmt = $conexion->prepare(
        "SELECT p.id_producto, p.nombre, d.talla, d.cantidad, p.precio
         FROM carritos c JOIN detalle_carrito d ON d.id_carrito = c.id_carrito
         JOIN productos p ON p.id_producto = d.id_producto
         WHERE c.id_usuario = ? AND p.activo = 1 ORDER BY d.orden");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $detalles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (count($detalles) === 0) {
        $conexion->rollback();
        responderError('Tu carrito está vacío.', 400);
    }

    // 3. revisar y descontar existencias (bloquea las filas para evitar ventas dobles)
    $total = 0;
    $leer = $conexion->prepare(
        "SELECT existencias FROM inventario WHERE id_producto = ? AND id_sucursal = ? AND talla = ? FOR UPDATE");
    $descontar = $conexion->prepare(
        "UPDATE inventario SET existencias = existencias - ? WHERE id_producto = ? AND id_sucursal = ? AND talla = ?");
    foreach ($detalles as $d) {
        $leer->bind_param("iis", $d['id_producto'], $idSucursal, $d['talla']);
        $leer->execute();
        $f = $leer->get_result()->fetch_assoc();
        $existencias = $f ? (int)$f['existencias'] : 0;

        if ($existencias < (int)$d['cantidad']) {
            $conexion->rollback();
            $t = $d['talla'] === '' ? '' : ' talla ' . $d['talla'];
            responderError('No hay existencias suficientes de ' . $d['nombre'] . $t
                . ' en la sucursal ' . $nombreSucursal . ' (disponibles: ' . $existencias . ').', 409);
        }
        $descontar->bind_param("iiis", $d['cantidad'], $d['id_producto'], $idSucursal, $d['talla']);
        $descontar->execute();
        $total += (float)$d['precio'] * (int)$d['cantidad'];
    }

    // 4. crear el pedido y sus renglones
    $stmt = $conexion->prepare(
        "INSERT INTO pedidos (id_usuario, id_sucursal, total, estado) VALUES (?, ?, ?, 'pendiente')");
    $stmt->bind_param("iid", $idUsuario, $idSucursal, $total);
    $stmt->execute();
    $idPedido = (int)$conexion->insert_id;

    $stmt = $conexion->prepare(
        "INSERT INTO detalle_pedido (id_pedido, id_producto, talla, cantidad, precio_unitario) VALUES (?, ?, ?, ?, ?)");
    foreach ($detalles as $d) {
        $stmt->bind_param("iisid", $idPedido, $d['id_producto'], $d['talla'], $d['cantidad'], $d['precio']);
        $stmt->execute();
    }

    // 5. vaciar el carrito
    $stmt = $conexion->prepare(
        "DELETE d FROM detalle_carrito d JOIN carritos c ON c.id_carrito = d.id_carrito WHERE c.id_usuario = ?");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();

    $conexion->commit();
} catch (Throwable $e) {
    $conexion->rollback();
    responderError('No se pudo confirmar el pedido', 500);
}

responder(['ok' => true, 'idPedido' => $idPedido, 'total' => $total, 'sucursal' => $nombreSucursal]);
