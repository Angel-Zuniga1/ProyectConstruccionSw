<?php
session_start();

// Si hay sesion pero falta la cookie de la barra (sesion anterior), se vuelve a crear
if (isset($_SESSION['usuario_id']) && !isset($_COOKIE['usuario_nombre'])) {
    setrawcookie('usuario_nombre', rawurlencode($_SESSION['usuario_nombre']), ['expires' => 0, 'path' => '/', 'samesite' => 'Lax']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Inicio | Boutique UADY</title>
  <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>

   <header class="barra">
    <a class="marca" href="index.php">Boutique <b>UADY</b></a>
    <nav>
      <a href="catalogo.html">Catálogo</a>
      <a href="carrito.html" class="carrito-nav">🛒 Carrito (<span id="contador-carrito">0</span>)</a>
      
      <!-- Lógica de sesión PHP -->
      <?php if (isset($_SESSION['usuario_id'])): ?>
          <a href="mis_pedidos.html">Mis pedidos</a>
          <span class="saludo">Hola, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></span>
          <a href="logout.php" class="enlace-salir">Cerrar sesión</a>
      <?php else: ?>
          <a href="login.php">Iniciar sesión</a>
          <a class="boton" href="registro.php">Crear cuenta</a>
      <?php endif; ?>
      
    </nav>
  </header>

  <main>
    <section class="portada">
      <h1>Lo que vistes en la boutique, ahora a un clic.</h1>
      <p>Elige ropa y accesorios oficiales de la UADY, arma tu carrito y recógelo en la sucursal FMAT o Principal.</p>
      <a class="boton grande" href="catalogo.html">Ver catálogo</a>
    </section>

    <section class="bloque">
      <h2>Categorías</h2>
      <div class="chips">
        <a href="catalogo.html?categoria=1">Playeras</a>
        <a href="catalogo.html?categoria=2">Sudaderas</a>
        <a href="catalogo.html?categoria=3">Gorras</a>
        <a href="catalogo.html?categoria=4">Accesorios</a>
      </div>
    </section>

    <section class="bloque">
      <h2>Novedades</h2>
      <div class="rejilla">
        
        <article class="tarjeta" data-nombre="llavero escudo" data-cat="4">
          <div class="foto"><img src="fotos boutique/llavero.jpg" alt="Llavero escudo"></div>
          <div class="info">
            <span class="cat">Accesorios</span>
            <h3>Llavero escudo</h3>
            <p>Metal con esmalte.</p>
            <div class="fila"><strong>$60.00</strong></div>
          </div>
        </article>

        <article class="tarjeta" data-nombre="mochila uady" data-cat="4">
          <div class="foto"><img src="fotos boutique/mochila_uady.png" alt="Mochila UADY"></div>
          <div class="info">
            <span class="cat">Accesorios</span>
            <h3>Mochila UADY</h3>
            <p>Compartimento para laptop de 15".</p>
            <div class="fila"><strong>$390.00</strong></div>
          </div>
        </article>

        <article class="tarjeta" data-nombre="termo institucional" data-cat="4">
          <div class="foto"><img src="fotos boutique/termo.jpg" alt="Termo institucional"></div>
          <div class="info">
            <span class="cat">Accesorios</span>
            <h3>Termo institucional</h3>
            <p>Acero inoxidable, 750 ml.</p>
            <div class="fila"><strong>$260.00</strong></div>
          </div>
        </article>

        <article class="tarjeta" data-nombre="gorra uady" data-cat="3">
          <div class="foto"><img src="fotos boutique/gorra_sin_fondo.png" alt="Gorra UADY"></div>
          <div class="info">
            <span class="cat">Gorras</span>
            <h3>Gorra UADY</h3>
            <p>Ajustable, logotipo bordado.</p>
            <div class="fila"><strong>$150.00</strong></div>
          </div>
        </article>

      </div>
    </section>
  </main>

  <footer class="pie">
    Boutique UADY en línea · Proyecto integrador de Desarrollo de Aplicaciones Web · Facultad de Matemáticas
  </footer>

  <script src="assets/js/carrito.js"></script>
  <script src="assets/js/script.js"></script>
</body>
</html>