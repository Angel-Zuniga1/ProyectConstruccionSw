<?php
/* RF-09 (administrador de sucursal): registrar, consultar, modificar y dar de baja productos
   EN SU SUCURSAL. Solo ve los productos que tienen inventario en su sucursal.
   Nombre, precio, descripcion e imagen son datos compartidos del catalogo; las existencias
   y la presencia del producto en la sucursal son propias de cada sucursal. */
require 'admin_comun.php';
exigirAdmin('sucursal');

$idSuc = sucursalActual();
$nombreSuc = $_SESSION['admin_sucursal_nombre'];
$categorias = $conexion->query("SELECT id_categoria, nombre FROM categorias ORDER BY id_categoria")->fetch_all(MYSQLI_ASSOC);
const TALLAS = ['XS', 'S', 'M', 'L', 'XL', '2XL'];

function guardarImagen() {
    if (empty($_FILES['archivo']['name']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) { return ''; }
    $tipo = mime_content_type($_FILES['archivo']['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$tipo] ?? null;
    if ($ext === null) { throw new RuntimeException('La imagen debe ser JPG, PNG o WEBP.'); }
    $nombre = 'producto-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (!move_uploaded_file($_FILES['archivo']['tmp_name'], __DIR__ . '/../fotos boutique/' . $nombre)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }
    return 'fotos boutique/' . $nombre;
}

// El producto debe estar en el inventario de ESTA sucursal para poder tocarlo
function productoDeMiSucursal($conexion, $idProducto, $idSuc) {
    $stmt = $conexion->prepare("SELECT 1 FROM inventario WHERE id_producto = ? AND id_sucursal = ? LIMIT 1");
    $stmt->bind_param("ii", $idProducto, $idSuc);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'crear' || $accion === 'editar') {
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio = (float)($_POST['precio'] ?? 0);
            $idCategoria = (int)($_POST['id_categoria'] ?? 0);
            $imagen = trim($_POST['imagen'] ?? '');
            if ($nombre === '' || $precio <= 0 || $idCategoria <= 0) {
                throw new RuntimeException('Nombre, precio mayor a cero y categoría son obligatorios.');
            }
            $subida = guardarImagen();
            if ($subida !== '') { $imagen = $subida; }

            if ($accion === 'crear') {
                $conexion->begin_transaction();
                $stmt = $conexion->prepare("INSERT INTO productos (nombre, descripcion, precio, imagen, id_categoria, activo) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->bind_param("ssdsi", $nombre, $descripcion, $precio, $imagen, $idCategoria);
                $stmt->execute();
                $nuevo = (int)$conexion->insert_id;
                // Inventario SOLO en la sucursal del administrador, con las existencias iniciales capturadas
                $ins = $conexion->prepare("INSERT INTO inventario (id_producto, id_sucursal, talla, existencias) VALUES (?, ?, ?, ?)");
                if (isset($_POST['con_tallas'])) {
                    foreach (TALLAS as $t) {
                        $cant = max(0, (int)($_POST['inicial'][$t] ?? 0));
                        $ins->bind_param("iisi", $nuevo, $idSuc, $t, $cant);
                        $ins->execute();
                    }
                } else {
                    $cant = max(0, (int)($_POST['inicial_unica'] ?? 0));
                    $vacia = '';
                    $ins->bind_param("iisi", $nuevo, $idSuc, $vacia, $cant);
                    $ins->execute();
                }
                $conexion->commit();
                irA('productos.php', "Producto \"$nombre\" dado de alta en $nombreSuc.");
            } else {
                $id = (int)$_POST['id_producto'];
                if (!productoDeMiSucursal($conexion, $id, $idSuc)) { throw new RuntimeException('Ese producto no está en tu sucursal.'); }
                $stmt = $conexion->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, imagen = ?, id_categoria = ? WHERE id_producto = ?");
                $stmt->bind_param("ssdsii", $nombre, $descripcion, $precio, $imagen, $idCategoria, $id);
                $stmt->execute();
                irA('productos.php', 'Producto modificado.');
            }
        } elseif ($accion === 'baja') {
            // Baja EN LA SUCURSAL: se quita su inventario aqui; en otras sucursales sigue igual
            $id = (int)$_POST['id_producto'];
            if (!productoDeMiSucursal($conexion, $id, $idSuc)) { throw new RuntimeException('Ese producto no está en tu sucursal.'); }
            $stmt = $conexion->prepare("DELETE FROM inventario WHERE id_producto = ? AND id_sucursal = ?");
            $stmt->bind_param("ii", $id, $idSuc);
            $stmt->execute();
            irA('productos.php', "Producto dado de baja en $nombreSuc. Ya no se ofrece en esta sucursal.");
        } elseif ($accion === 'alta_existente') {
            // Alta de un producto que ya existe en el catalogo (lo creo otra sucursal)
            $id = (int)($_POST['id_producto'] ?? 0);
            $conTallas = isset($_POST['con_tallas_existente']);
            $ins = $conexion->prepare("INSERT IGNORE INTO inventario (id_producto, id_sucursal, talla, existencias) VALUES (?, ?, ?, 0)");
            foreach ($conTallas ? TALLAS : [''] as $t) {
                $ins->bind_param("iis", $id, $idSuc, $t);
                $ins->execute();
            }
            irA('inventario.php', 'Producto agregado a tu sucursal. Captura sus existencias.');
        }
    } catch (RuntimeException $e) {
        $conexion->rollback();
        irA('productos.php', null, $e->getMessage());
    } catch (mysqli_sql_exception $e) {
        $conexion->rollback();
        irA('productos.php', null, 'No se pudo guardar el producto.');
    }
}

// Producto a editar (solo si es de esta sucursal)
$editar = null;
if (isset($_GET['editar']) && productoDeMiSucursal($conexion, (int)$_GET['editar'], $idSuc)) {
    $stmt = $conexion->prepare("SELECT * FROM productos WHERE id_producto = ?");
    $idE = (int)$_GET['editar'];
    $stmt->bind_param("i", $idE);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}

// Productos de ESTA sucursal con sus existencias aqui
$stmt = $conexion->prepare(
    "SELECT p.*, c.nombre AS categoria, SUM(i.existencias) AS existencias, COUNT(i.talla) AS tallas
     FROM inventario i JOIN productos p ON p.id_producto = i.id_producto
     JOIN categorias c ON c.id_categoria = p.id_categoria
     WHERE i.id_sucursal = ? GROUP BY p.id_producto ORDER BY p.nombre");
$stmt->bind_param("i", $idSuc);
$stmt->execute();
$lista = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Productos del catalogo que aun no estan en esta sucursal (para darlos de alta sin volver a capturarlos)
$stmt = $conexion->prepare(
    "SELECT p.id_producto, p.nombre FROM productos p
     WHERE p.activo = 1 AND NOT EXISTS (SELECT 1 FROM inventario i WHERE i.id_producto = p.id_producto AND i.id_sucursal = ?)
     ORDER BY p.nombre");
$stmt->bind_param("i", $idSuc);
$stmt->execute();
$otros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

cabeceraAdmin('Productos · ' . $nombreSuc, 'productos.php');
avisos();
?>
<section class="bloque">
  <h2><?php echo $editar ? 'Modificar producto #' . (int)$editar['id_producto'] : 'Dar de alta un producto nuevo en ' . h($nombreSuc); ?></h2>
  <form method="post" enctype="multipart/form-data" class="form-admin" data-validar data-confirmar="<?php echo $editar ? '¿Guardar los cambios del producto?' : '¿Dar de alta este producto en tu sucursal?'; ?>">
    <?php echo campoCsrf(); ?>
    <input type="hidden" name="accion" value="<?php echo $editar ? 'editar' : 'crear'; ?>">
    <?php if ($editar): ?><input type="hidden" name="id_producto" value="<?php echo (int)$editar['id_producto']; ?>"><?php endif; ?>
    <label>Nombre <input name="nombre" required value="<?php echo h($editar['nombre'] ?? ''); ?>"></label>
    <label>Descripción <input name="descripcion" value="<?php echo h($editar['descripcion'] ?? ''); ?>"></label>
    <label>Precio (MXN) <input name="precio" type="number" step="0.01" min="0.01" required value="<?php echo h($editar['precio'] ?? ''); ?>"></label>
    <label>Categoría
      <select name="id_categoria" required>
        <?php foreach ($categorias as $c): ?>
          <option value="<?php echo $c['id_categoria']; ?>" <?php echo (($editar['id_categoria'] ?? 0) == $c['id_categoria']) ? 'selected' : ''; ?>><?php echo h($c['nombre']); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Ruta de imagen <input name="imagen" placeholder="fotos boutique/archivo.jpg" value="<?php echo h($editar['imagen'] ?? ''); ?>"></label>
    <label>o subir imagen <input type="file" name="archivo" accept=".jpg,.jpeg,.png,.webp"></label>
    <?php if (!$editar): ?>
      <label class="casilla"><input type="checkbox" name="con_tallas" id="con_tallas" checked> Producto con tallas</label>
      <div class="acciones" id="tallas-iniciales">
        <span>Existencias iniciales:</span>
        <?php foreach (TALLAS as $t): ?><label class="talla-inicial"><?php echo $t; ?> <input class="numero" type="number" min="0" name="inicial[<?php echo $t; ?>]" value="0"></label><?php endforeach; ?>
      </div>
      <label id="unica-inicial" hidden>Existencias iniciales (talla única) <input class="numero" type="number" min="0" name="inicial_unica" value="0"></label>
    <?php endif; ?>
    <div class="acciones">
      <button class="boton" type="submit"><?php echo $editar ? 'Guardar cambios' : 'Dar de alta'; ?></button>
      <?php if ($editar): ?><a class="boton secundario" href="productos.php">Cancelar</a><?php endif; ?>
    </div>
  </form>
  <?php if ($editar): ?><p class="aparte">Nombre, precio, descripción e imagen son del catálogo general: si otra sucursal también vende este producto, verá los mismos datos. Las existencias se cambian en Inventario.</p><?php endif; ?>
</section>

<section class="bloque">
  <h2>Productos en <?php echo h($nombreSuc); ?> (<?php echo count($lista); ?>)</h2>
  <div class="tabla-scroll">
  <table class="tabla-admin">
    <thead><tr><th>#</th><th>Imagen</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Tallas</th><th>Existencias aquí</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $p): ?>
      <tr class="<?php echo (int)$p['existencias'] === 0 ? 'agotado' : ''; ?>">
        <td><?php echo (int)$p['id_producto']; ?></td>
        <td><img class="mini" src="../<?php echo h($p['imagen']); ?>" alt=""></td>
        <td><?php echo h($p['nombre']); ?><br><small><?php echo h($p['descripcion']); ?></small></td>
        <td><?php echo h($p['categoria']); ?></td>
        <td>$<?php echo number_format((float)$p['precio'], 2); ?></td>
        <td><?php echo (int)$p['tallas'] > 1 ? (int)$p['tallas'] . ' tallas' : 'Única'; ?></td>
        <td><?php echo (int)$p['existencias']; ?><?php echo (int)$p['existencias'] === 0 ? ' (agotado)' : ''; ?></td>
        <td class="acciones">
          <a class="boton chico" href="productos.php?editar=<?php echo (int)$p['id_producto']; ?>">Modificar</a>
          <a class="boton chico secundario" href="inventario.php#p<?php echo (int)$p['id_producto']; ?>">Existencias</a>
          <form method="post" class="inline" data-confirmar="¿Dar de baja &quot;<?php echo h($p['nombre']); ?>&quot; en <?php echo h($nombreSuc); ?>? Se quitará su inventario de esta sucursal.">
            <?php echo campoCsrf(); ?>
            <input type="hidden" name="id_producto" value="<?php echo (int)$p['id_producto']; ?>">
            <input type="hidden" name="accion" value="baja">
            <button class="boton chico peligro" type="submit">Dar de baja</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$lista): ?><tr><td colspan="8">Tu sucursal aún no tiene productos. Da de alta uno arriba.</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</section>

<?php if ($otros): ?>
<section class="bloque">
  <h2>Agregar a tu sucursal un producto que ya existe en el catálogo</h2>
  <form method="post" class="form-admin" data-confirmar="¿Agregar este producto a tu sucursal?">
    <?php echo campoCsrf(); ?>
    <input type="hidden" name="accion" value="alta_existente">
    <label>Producto
      <select name="id_producto" required>
        <?php foreach ($otros as $o): ?><option value="<?php echo (int)$o['id_producto']; ?>"><?php echo h($o['nombre']); ?></option><?php endforeach; ?>
      </select>
    </label>
    <label class="casilla"><input type="checkbox" name="con_tallas_existente" checked> Con tallas (XS a 2XL)</label>
    <div class="acciones"><button class="boton" type="submit">Agregar y capturar existencias</button></div>
  </form>
</section>
<?php endif; ?>
<script>
  // Mostrar la captura por tallas o la de talla única según la casilla
  const casilla = document.getElementById('con_tallas');
  if (casilla) {
    const porTallas = document.getElementById('tallas-iniciales'), unica = document.getElementById('unica-inicial');
    const ajustar = () => { porTallas.hidden = !casilla.checked; unica.hidden = casilla.checked; };
    casilla.addEventListener('change', ajustar); ajustar();
  }
</script>
<?php pieAdmin(); ?>
