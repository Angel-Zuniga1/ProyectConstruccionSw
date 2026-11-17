<?php
/*
  API del carrito por usuario (Boutique UADY) sobre las tablas carritos y detalle_carrito.
  GET  carrito_api.php -> devuelve en JSON el carrito del usuario con sesion
  POST carrito_api.php -> reemplaza el carrito del usuario con el JSON recibido
  El navegador manda id, talla y cantidad; nombre, precio e imagen se leen SIEMPRE de
  la tabla productos, por lo que no se puede alterar un precio desde el cliente.
  (Equivale a CarritoServlet + CarritoService + CarritoDAO de la version Java.)
*/
require 'conexion.php';
require 'api_comun.php';

$idUsuario = exigirSesion();

// ---------- LEER el carrito ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conexion->prepare(
        "SELECT p.id_producto, d.talla, p.nombre, p.precio, p.imagen, d.cantidad
         FROM carritos c
         JOIN detalle_carrito d ON d.id_carrito = c.id_carrito
         JOIN productos p ON p.id_producto = d.id_producto
         WHERE c.id_usuario = ? ORDER BY d.orden");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $items = [];
    while ($fila = $resultado->fetch_assoc()) {
        $items[] = [
            'id'       => (string)$fila['id_producto'],
            'talla'    => $fila['talla'],
            'nombre'   => $fila['nombre'],
            'precio'   => (float)$fila['precio'],
            'imagen'   => $fila['imagen'],
            'cantidad' => (int)$fila['cantidad']
        ];
    }
    responder($items);
}

// ---------- GUARDAR el carrito ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = json_decode(file_get_contents('php://input'), true);
    if (!is_array($datos)) {
        responderError('Datos invalidos', 400);
    }

    // Validar y limpiar cada linea; mismo producto y talla se suman
    $tallasValidas = ['', 'XS', 'S', 'M', 'L', 'XL', '2XL'];
    $limpios = [];
    foreach ($datos as $item) {
        if (!is_array($item)) { continue; }

        $id       = trim((string)($item['id'] ?? ''));
        $talla    = (string)($item['talla'] ?? '');
        $cantidad = (int)($item['cantidad'] ?? 0);

        if (!ctype_digit($id) || $cantidad < 1) { continue; }
        if (!in_array($talla, $tallasValidas, true)) { continue; }
        if ($cantidad > 99) { $cantidad = 99; }

        $clave = $id . '|' . $talla;
        if (isset($limpios[$clave])) {
            $limpios[$clave]['cantidad'] = min(99, $limpios[$clave]['cantidad'] + $cantidad);
        } else {
            $limpios[$clave] = ['id' => (int)$id, 'talla' => $talla, 'cantidad' => $cantidad];
        }
    }

    // Reemplazar el carrito completo dentro de una transaccion
    $conexion->begin_transaction();
    try {
        // Carrito del usuario (se crea si no existe)
        $stmt = $conexion->prepare("SELECT id_carrito FROM carritos WHERE id_usuario = ?");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        if ($fila) {
            $idCarrito = (int)$fila['id_carrito'];
        } else {
            $stmt = $conexion->prepare("INSERT INTO carritos (id_usuario) VALUES (?)");
            $stmt->bind_param("i", $idUsuario);
            $stmt->execute();
            $idCarrito = (int)$conexion->insert_id;
        }

        $borrar = $conexion->prepare("DELETE FROM detalle_carrito WHERE id_carrito = ?");
        $borrar->bind_param("i", $idCarrito);
        $borrar->execute();

        if (count($limpios) > 0) {
            // Solo se insertan productos que existan y esten activos
            $insertar = $conexion->prepare(
                "INSERT INTO detalle_carrito (id_carrito, id_producto, talla, cantidad, orden)
                 SELECT ?, id_producto, ?, ?, ? FROM productos WHERE id_producto = ? AND activo = 1");
            $orden = 0;
            foreach ($limpios as $l) {
                $insertar->bind_param("isiii", $idCarrito, $l['talla'], $l['cantidad'], $orden, $l['id']);
                $insertar->execute();
                $orden++;
            }
        }
        $conexion->commit();
    } catch (Throwable $e) {
        $conexion->rollback();
        responderError('No se pudo guardar el carrito', 500);
    }
    responder(['ok' => true]);
}

responderError('Metodo no permitido', 405);
