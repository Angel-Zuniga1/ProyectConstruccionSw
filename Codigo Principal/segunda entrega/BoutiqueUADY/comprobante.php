<?php
/*
  GET comprobante.php?id=5 -> descarga el comprobante .txt de un pedido PROPIO
  (equivale a ComprobanteServlet + ComprobanteService). El texto generado es
  identico al de la version Java.
*/
require 'conexion.php';
require 'api_comun.php';

$idUsuario = exigirSesion();

if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    responderError('Pedido invalido', 400);
}
$idPedido = (int)$_GET['id'];

// El pedido solo se entrega si pertenece al usuario con sesion
$stmt = $conexion->prepare(
    "SELECT p.id_pedido, CONCAT(u.nombre, ' ', u.apellidos) AS cliente, s.nombre AS sucursal,
            p.fecha, p.total, p.estado
     FROM pedidos p JOIN usuarios u ON u.id_usuario = p.id_usuario
     JOIN sucursales s ON s.id_sucursal = p.id_sucursal
     WHERE p.id_pedido = ? AND p.id_usuario = ?");
$stmt->bind_param("ii", $idPedido, $idUsuario);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
if (!$pedido) {
    responderError('Pedido no encontrado', 404);
}

$stmt = $conexion->prepare(
    "SELECT pr.nombre, d.talla, d.cantidad, d.precio_unitario
     FROM detalle_pedido d JOIN productos pr ON pr.id_producto = d.id_producto
     WHERE d.id_pedido = ? ORDER BY pr.nombre, d.talla");
$stmt->bind_param("i", $idPedido);
$stmt->execute();
$detalles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$fecha = date('d/m/Y H:i', strtotime($pedido['fecha']));
$t  = "==========================================\n";
$t .= "      BOUTIQUE UADY EN LINEA\n";
$t .= "      Comprobante de compra\n";
$t .= "==========================================\n";
$t .= "Pedido No.: " . $pedido['id_pedido'] . "\n";
$t .= "Cliente:    " . $pedido['cliente'] . "\n";
$t .= "Sucursal:   " . $pedido['sucursal'] . "\n";
$t .= "Fecha:      " . $fecha . "\n";
$t .= "Estado:     " . $pedido['estado'] . "\n";
$t .= "------------------------------------------\n";
foreach ($detalles as $d) {
    $t .= $d['nombre'];
    if ($d['talla'] !== '') {
        $t .= " (talla " . $d['talla'] . ")";
    }
    $t .= "\n";
    $t .= "   " . $d['cantidad'] . " x $" . number_format((float)$d['precio_unitario'], 2, '.', '')
        . " = $" . number_format($d['cantidad'] * (float)$d['precio_unitario'], 2, '.', '') . "\n";
}
$t .= "------------------------------------------\n";
$t .= "TOTAL: $" . number_format((float)$pedido['total'], 2, '.', '') . " MXN\n";
$t .= "==========================================\n";
$t .= "El pago y la entrega se realizan en la sucursal " . $pedido['sucursal'] . ".\n";
$t .= "Gracias por tu compra.\n";

header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="comprobante-' . $idPedido . '.txt"');
echo $t;
