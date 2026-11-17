-- ==========================================================
-- Boutique UADY en línea - Base de datos completa (phpMyAdmin > Importar)
-- Seguro de importar varias veces: usa IF NOT EXISTS / INSERT IGNORE
-- y no borra ni modifica datos existentes.
-- Sucursales activas: FMAT (1) y Principal (2).
-- Los productos "fmat" solo existen en la sucursal FMAT.
-- ==========================================================
CREATE DATABASE IF NOT EXISTS boutique_uady CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE boutique_uady;

CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario    INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  apellidos     VARCHAR(100) NOT NULL,
  correo        VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  telefono      VARCHAR(20)  NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sucursales (
  id_sucursal INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(80) NOT NULL,
  activa      TINYINT(1)  NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categorias (
  id_categoria INT AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS productos (
  id_producto  INT AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(120) NOT NULL,
  descripcion  VARCHAR(255) NOT NULL DEFAULT '',
  precio       DECIMAL(10,2) NOT NULL,
  imagen       VARCHAR(255) NOT NULL DEFAULT '',
  id_categoria INT NOT NULL,
  activo       TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inventario (
  id_producto INT NOT NULL,
  id_sucursal INT NOT NULL,
  talla       VARCHAR(10) NOT NULL DEFAULT '',
  existencias INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id_producto, id_sucursal, talla),
  FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
  FOREIGN KEY (id_sucursal) REFERENCES sucursales(id_sucursal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS carritos (
  id_carrito INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL UNIQUE,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS detalle_carrito (
  id_carrito  INT NOT NULL,
  id_producto INT NOT NULL,
  talla       VARCHAR(10) NOT NULL DEFAULT '',
  cantidad    INT NOT NULL,
  orden       INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id_carrito, id_producto, talla),
  FOREIGN KEY (id_carrito) REFERENCES carritos(id_carrito) ON DELETE CASCADE,
  FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pedidos (
  id_pedido   INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario  INT NOT NULL,
  id_sucursal INT NOT NULL,
  fecha       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total       DECIMAL(10,2) NOT NULL,
  estado      VARCHAR(30) NOT NULL DEFAULT 'pendiente',
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  FOREIGN KEY (id_sucursal) REFERENCES sucursales(id_sucursal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS detalle_pedido (
  id_pedido       INT NOT NULL,
  id_producto     INT NOT NULL,
  talla           VARCHAR(10) NOT NULL DEFAULT '',
  cantidad        INT NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id_pedido, id_producto, talla),
  FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido) ON DELETE CASCADE,
  FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Datos iniciales ----------
INSERT IGNORE INTO sucursales (id_sucursal, nombre, activa) VALUES
  (1, 'FMAT', 1),
  (2, 'Principal', 1);

INSERT IGNORE INTO categorias (id_categoria, nombre) VALUES
  (1, 'Playeras'), (2, 'Sudaderas'), (3, 'Gorras'), (4, 'Accesorios');

INSERT IGNORE INTO productos (id_producto, nombre, descripcion, precio, imagen, id_categoria, activo) VALUES
  (1, 'Playera UADY clásica', 'Algodón 100%, escudo bordado al pecho.', 180.00, 'fotos boutique/Playera-clasicauady.jpg', 1, 1),
  (2, 'Playera deportiva UADY', 'Tela dry-fit para entrenar.', 220.00, 'fotos boutique/Playera deportiva uady.jpg', 1, 1),
  (3, 'Sudadera con capucha', 'Azul marino con escudo en relieve.', 420.00, 'fotos boutique/sudadera con capuccha.jpg', 2, 1),
  (4, 'Chamarra rompevientos', 'Ligera e impermeable.', 480.00, 'fotos boutique/chamarra-rompevientos.jpg', 2, 1),
  (5, 'Gorra UADY', 'Ajustable, logotipo bordado.', 150.00, 'fotos boutique/gorra_sin_fondo.png', 3, 1),
  (6, 'Termo institucional', 'Acero inoxidable, 750 ml.', 260.00, 'fotos boutique/termo.jpg', 4, 1),
  (7, 'Mochila UADY', 'Compartimento para laptop de 15".', 390.00, 'fotos boutique/mochila_uady.png', 4, 1),
  (8, 'Llavero escudo', 'Metal con esmalte.', 60.00, 'fotos boutique/llavero.jpg', 4, 1),
  (9, 'Playera polo fmat', 'Playera tipo polo con logo bordado de la facultad.', 250.00, 'fotos boutique/Playera polo fmat.jpg', 1, 1),
  (10, 'Playera fmat uady', 'Diseño exclusivo de la Facultad de Matemáticas.', 200.00, 'fotos boutique/Playera fmat.jpg', 1, 1),
  (11, 'Libreta fmat', 'Libreta oficial de la facultad, ideal para apuntes.', 90.00, 'fotos boutique/Libreta fmat.jpg', 4, 1),
  (12, 'Abánico uady', 'Abanico de mano con diseño institucional.', 80.00, 'fotos boutique/Abanico uady.jpg', 4, 1),
  (13, 'Taza fmat', 'Taza de cerámica con el escudo de la facultad.', 120.00, 'fotos boutique/Taza fmat.jpg', 4, 1),
  (14, 'Peluche uady', 'Peluche del jaguar, mascota oficial de la UADY.', 180.00, 'fotos boutique/Peluche uady.jpg', 4, 1);

-- Existencias por producto, sucursal y talla (los accesorios y gorras no tienen talla)
INSERT IGNORE INTO inventario (id_producto, id_sucursal, talla, existencias) VALUES
  (1, 1, 'XS', 10),
  (1, 1, 'S', 10),
  (1, 1, 'M', 10),
  (1, 1, 'L', 10),
  (1, 1, 'XL', 10),
  (1, 1, '2XL', 10),
  (1, 2, 'XS', 6),
  (1, 2, 'S', 6),
  (1, 2, 'M', 6),
  (1, 2, 'L', 6),
  (1, 2, 'XL', 6),
  (1, 2, '2XL', 6),
  (2, 1, 'XS', 10),
  (2, 1, 'S', 10),
  (2, 1, 'M', 10),
  (2, 1, 'L', 10),
  (2, 1, 'XL', 10),
  (2, 1, '2XL', 10),
  (2, 2, 'XS', 6),
  (2, 2, 'S', 6),
  (2, 2, 'M', 6),
  (2, 2, 'L', 6),
  (2, 2, 'XL', 6),
  (2, 2, '2XL', 6),
  (3, 1, 'XS', 10),
  (3, 1, 'S', 10),
  (3, 1, 'M', 10),
  (3, 1, 'L', 10),
  (3, 1, 'XL', 10),
  (3, 1, '2XL', 10),
  (3, 2, 'XS', 6),
  (3, 2, 'S', 6),
  (3, 2, 'M', 6),
  (3, 2, 'L', 6),
  (3, 2, 'XL', 6),
  (3, 2, '2XL', 6),
  (4, 1, 'XS', 10),
  (4, 1, 'S', 10),
  (4, 1, 'M', 10),
  (4, 1, 'L', 10),
  (4, 1, 'XL', 10),
  (4, 1, '2XL', 10),
  (4, 2, 'XS', 6),
  (4, 2, 'S', 6),
  (4, 2, 'M', 6),
  (4, 2, 'L', 6),
  (4, 2, 'XL', 6),
  (4, 2, '2XL', 6),
  (5, 1, '', 20),
  (5, 2, '', 12),
  (6, 1, '', 20),
  (6, 2, '', 12),
  (7, 1, '', 20),
  (7, 2, '', 12),
  (8, 1, '', 20),
  (8, 2, '', 12),
  (9, 1, 'XS', 10),
  (9, 1, 'S', 10),
  (9, 1, 'M', 10),
  (9, 1, 'L', 10),
  (9, 1, 'XL', 10),
  (9, 1, '2XL', 10),
  (10, 1, 'XS', 10),
  (10, 1, 'S', 10),
  (10, 1, 'M', 10),
  (10, 1, 'L', 10),
  (10, 1, 'XL', 10),
  (10, 1, '2XL', 10),
  (11, 1, '', 20),
  (12, 1, '', 20),
  (12, 2, '', 12),
  (13, 1, '', 20),
  (14, 1, '', 20),
  (14, 2, '', 12);

-- ------------------------------------------------------------
-- Administradores (modulo de administracion, RF-09 a RF-11)
-- Separados de la tabla usuarios (clientes).
--   rol 'llave'     : cuenta que, desde el login de la tienda, abre el login de administradores.
--                     No entra al panel.
--   rol 'principal' : administrador principal; crea las cuentas de sucursal.
--   rol 'sucursal'  : administrador de una sucursal.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS administradores (
  id_admin      INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  correo        VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol           ENUM('llave','principal','sucursal') NOT NULL,
  id_sucursal   INT NULL,
  activo        TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (id_sucursal) REFERENCES sucursales(id_sucursal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Por si la tabla ya existia de una version anterior (sin el rol 'llave')
ALTER TABLE administradores MODIFY rol ENUM('llave','principal','sucursal') NOT NULL;

-- Cuentas de prueba:
--   Paso 1 (login de la tienda):      admin@uady.com / 12345678   -> abre admin/login_admin.php
--   Paso 2 (login de administradores): A24216404@alumnos.uady.mx / 3431halo -> panel principal
INSERT IGNORE INTO administradores (id_admin, nombre, correo, password_hash, rol, id_sucursal) VALUES
(1, 'Acceso a administradores', 'admin@uady.com', '$2y$10$5iWdKYrxrId9GIFhcTcLFeNwSJwD184H/gz2D5wEr4rLk6uXDDKwi', 'llave', NULL),
(2, 'Administrador principal', 'A24216404@alumnos.uady.mx', '$2y$10$Zl7T7ClaVkLpm/VNFW8Ny.uVtVsTqOkXrROmL1JEgQVy1PmovtKBK', 'principal', NULL);
-- Si ya existian, se dejan con la contraseña y el rol de pruebas
UPDATE administradores SET password_hash = '$2y$10$5iWdKYrxrId9GIFhcTcLFeNwSJwD184H/gz2D5wEr4rLk6uXDDKwi', rol = 'llave', activo = 1 WHERE correo = 'admin@uady.com';
UPDATE administradores SET password_hash = '$2y$10$Zl7T7ClaVkLpm/VNFW8Ny.uVtVsTqOkXrROmL1JEgQVy1PmovtKBK', rol = 'principal', activo = 1 WHERE correo = 'A24216404@alumnos.uady.mx';
