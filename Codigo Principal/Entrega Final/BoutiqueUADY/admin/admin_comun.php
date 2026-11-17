<?php
/*
  Funciones comunes del panel de administracion.
  La sesion de administrador usa variables propias (admin_id, admin_rol, admin_sucursal,
  admin_nombre): NUNCA se mezcla con la sesion de cliente (usuario_id) ni crea la cookie
  del saludo. Entrar al panel requiere la "llave" que deja login.php al reconocer a un
  administrador ($_SESSION['llave_admin']).
*/
require_once __DIR__ . '/../conexion.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

const ESTADOS_PEDIDO = ['pendiente', 'listo para recoger', 'entregado', 'cancelado'];

function h($t) { return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'); }

// Solo se llega al login del panel si login.php reconocio a un administrador
function exigirLlave() {
    if (empty($_SESSION['llave_admin']) && empty($_SESSION['admin_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

// Paginas del panel: requieren sesion de administrador (y rol, si se indica)
function exigirAdmin($rol = null) {
    if (empty($_SESSION['admin_id'])) {
        header("Location: login_admin.php");
        exit();
    }
    if ($rol !== null && $_SESSION['admin_rol'] !== $rol) {
        header("Location: index.php?error=permiso");
        exit();
    }
}

function esPrincipal() { return ($_SESSION['admin_rol'] ?? '') === 'principal'; }

// Sucursal asignada al administrador de sucursal (todo lo que ve y toca es de esta sucursal)
function sucursalActual() {
    return (int)($_SESSION['admin_sucursal'] ?? 0);
}

// Resumen de ventas de una sucursal (0 = todas): pedidos por estado y total vendido
function resumenVentas($conexion, $idSucursal = 0) {
    $where = $idSucursal ? "WHERE id_sucursal = " . (int)$idSucursal : "";
    $r = ['pedidos' => 0, 'ventas' => 0.0, 'por_estado' => array_fill_keys(ESTADOS_PEDIDO, 0)];
    $res = $conexion->query("SELECT estado, COUNT(*) c, SUM(total) t FROM pedidos $where GROUP BY estado");
    while ($f = $res->fetch_assoc()) {
        $r['por_estado'][$f['estado']] = (int)$f['c'];
        $r['pedidos'] += (int)$f['c'];
        if ($f['estado'] !== 'cancelado') { $r['ventas'] += (float)$f['t']; }
    }
    return $r;
}

// Inventario de una sucursal agrupado por producto
function inventarioDe($conexion, $idSucursal) {
    $stmt = $conexion->prepare(
        "SELECT p.id_producto, p.nombre, p.precio, p.activo, i.talla, i.existencias
         FROM inventario i JOIN productos p ON p.id_producto = i.id_producto
         WHERE i.id_sucursal = ?
         ORDER BY p.nombre, FIELD(i.talla, '', 'XS', 'S', 'M', 'L', 'XL', '2XL')");
    $stmt->bind_param("i", $idSucursal);
    $stmt->execute();
    $porProducto = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $f) {
        $porProducto[$f['id_producto']]['nombre'] = $f['nombre'];
        $porProducto[$f['id_producto']]['precio'] = $f['precio'];
        $porProducto[$f['id_producto']]['activo'] = $f['activo'];
        $porProducto[$f['id_producto']]['tallas'][] = $f;
    }
    return $porProducto;
}

// Token contra envios de formularios desde otros sitios (CSRF)
function tokenCsrf() {
    if (empty($_SESSION['csrf_admin'])) { $_SESSION['csrf_admin'] = bin2hex(random_bytes(16)); }
    return $_SESSION['csrf_admin'];
}
function campoCsrf() { return '<input type="hidden" name="csrf" value="' . h(tokenCsrf()) . '">'; }
function exigirCsrf() {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf_admin'] ?? null)) {
        http_response_code(400);
        exit('Formulario no valido. Vuelve a intentarlo.');
    }
}

function listarSucursales($conexion, $soloActivas = false) {
    $sql = "SELECT id_sucursal, nombre, activa FROM sucursales " . ($soloActivas ? "WHERE activa = 1 " : "") . "ORDER BY id_sucursal";
    return $conexion->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function nombreSucursal($conexion, $id) {
    $stmt = $conexion->prepare("SELECT nombre FROM sucursales WHERE id_sucursal = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $f = $stmt->get_result()->fetch_assoc();
    return $f ? $f['nombre'] : '';
}

// Cabecera y pie compartidos por todas las paginas del panel
function cabeceraAdmin($titulo, $activo = '') {
    $nombre = h($_SESSION['admin_nombre'] ?? '');
    $rol = esPrincipal() ? 'Administrador principal' : 'Admin. de sucursal ' . h($_SESSION['admin_sucursal_nombre'] ?? '');
    // El principal solo supervisa: no maneja productos ni inventario (eso es de cada sucursal)
    if (esPrincipal()) {
        $menu = ['index.php' => 'Inicio', 'sucursales.php' => 'Sucursales', 'administradores.php' => 'Administradores'];
    } else {
        $menu = ['index.php' => 'Inicio', 'productos.php' => 'Productos', 'inventario.php' => 'Inventario', 'pedidos.php' => 'Pedidos'];
    }
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . h($titulo) . ' | Admin Boutique UADY</title>';
    echo '<link rel="stylesheet" href="../assets/css/estilo.css"><link rel="stylesheet" href="../assets/css/admin.css"></head><body>';
    echo '<header class="barra"><a class="marca" href="index.php">Boutique <b>UADY</b> <span class="etiqueta-admin">ADMIN</span></a><nav>';
    foreach ($menu as $url => $texto) {
        echo '<a href="' . $url . '"' . ($activo === $url ? ' class="activo"' : '') . '>' . $texto . '</a>';
    }
    echo '<span class="saludo">' . $nombre . ' · ' . $rol . '</span>';
    echo '<a href="logout_admin.php" class="enlace-salir">Salir</a></nav></header><main class="panel">';
    echo '<h1>' . h($titulo) . '</h1>';
}

function pieAdmin() {
    echo '</main><footer class="pie">Boutique UADY en línea · Panel de administración</footer>';
    echo '<script src="../assets/js/script.js"></script><script src="../assets/js/admin.js"></script></body></html>';
}

function avisos() {
    if (isset($_GET['ok']))    { echo '<div class="ok">' . h($_GET['ok']) . '</div>'; }
    if (isset($_GET['error'])) {
        $m = $_GET['error'] === 'permiso' ? 'No tienes permiso para esa sección.' : $_GET['error'];
        echo '<div class="error">' . h($m) . '</div>';
    }
}

function irA($pagina, $ok = null, $error = null) {
    $sep = strpos($pagina, '?') === false ? '?' : '&';
    if ($ok !== null)    { $pagina .= $sep . 'ok=' . rawurlencode($ok); }
    if ($error !== null) { $pagina .= $sep . 'error=' . rawurlencode($error); }
    header("Location: $pagina");
    exit();
}
