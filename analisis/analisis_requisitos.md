# Análisis del proyecto

## Identificación

- **Aplicación:** Boutique UADY en línea (nombre provisional: UADY Boutique online).
- **Proyecto:** Proyecto integrador de Desarrollo de Aplicaciones Web, Facultad de Matemáticas, Universidad Autónoma de Yucatán.
- **Integrantes:** Angel Daniel Zuñiga Gomez y José Carlos Keb Zib.
- **Fecha de entrega de requisitos:** 29/10/2026.

## Problema y necesidad

Las boutiques de la UADY tienen sucursales en distintas facultades y venden ropa y accesorios institucionales, como playeras, sudaderas, gorras, termos y mochilas. Actualmente, las ventas se realizan presencialmente. Por ello, estudiantes, egresados y familiares deben acudir a una sucursal para conocer el catálogo y los precios; no pueden consultar por anticipado la disponibilidad de productos o tallas en una sucursal específica; las personas que están fuera de su facultad o de la ciudad tienen dificultades para acceder a los productos; y no se cuenta con un registro digital centralizado de ventas y existencias. Tampoco existe la posibilidad de realizar pedidos en línea.

Se requiere un canal de venta en línea sencillo y orientado a la comunidad universitaria, limitado a los productos oficiales de las boutiques UADY.

## Objetivo y funcionalidad principal

Desarrollar una aplicación web en la que las personas puedan explorar el catálogo, consultar la disponibilidad por sucursal, agregar productos a un carrito y generar pedidos para recoger en una sucursal.

## Usuarios

- **Cliente:** alumno, egresado, personal universitario o público en general que compra productos.
- **Administrador de sucursal:** gestiona los productos, el inventario y los pedidos de su boutique.
- **Administrador general:** gestiona sucursales, categorías y usuarios.

Las funciones administrativas deben restringirse de acuerdo con el rol del usuario.

## Alcance

### Incluido

- Registro, autenticación y mantenimiento de sesión.
- Consulta, búsqueda y filtrado del catálogo.
- Consulta de detalle y disponibilidad por sucursal.
- Carrito de compras y generación de pedidos.
- Selección de una sucursal para recoger cada pedido.
- Consulta del historial y estado de pedidos.
- Administración de productos, inventario y pedidos de sucursal.
- Gestión general de sucursales, categorías y usuarios.
- Manual de usuario y manual técnico.

### No incluido en esta versión

- Pago en línea con tarjeta: el pago se realiza al recoger el pedido.
- Reseñas de productos.

## Arquitectura e integración prevista

La aplicación se organizará en dos capas que se comunicarán mediante peticiones HTTP:

- **FrontEnd:** HTML, CSS y JavaScript para presentar el catálogo, carrito y formularios, y enviar las solicitudes al servidor.
- **BackEnd:** recibe solicitudes, valida datos, aplica la lógica de negocio (autenticación, sesiones, cálculo de totales y control de inventario) y se comunica con la base de datos.
- **Integración:** el FrontEnd consumirá servicios del BackEnd, por ejemplo `/login`, `/productos`, `/carrito` y `/pedidos`. La información se intercambiará en JSON o mediante páginas generadas por el servidor.

El trabajo se plantea de forma incremental: primera revisión con index, autenticación y una funcionalidad (catálogo); segunda revisión con el resto de los módulos; entrega final con integración completa.

## Base de datos prevista

Se especifican nueve tablas relacionales: `usuarios`, `sucursales`, `categorias`, `productos`, `inventario`, `carritos`, `detalle_carrito`, `pedidos` y `detalle_pedido`. Sus responsabilidades y la información especificada se documentan en `../diseno/arquitectura_y_base_de_datos.md`. El diagrama entidad-relación y el modelo relacional se entregan en documentos aparte.

## Hitos de entrega

| Fecha | Entregable |
|---|---|
| 29/10/2026 | Requisitos, diagrama E-R, index, autenticación y una funcionalidad (catálogo). |
| 12/11/2026 | Carrito, pedidos y módulo de administración. |
| 26/11/2026 | Integración final, sesiones, manual de usuario y manual técnico. |

## Consideraciones y decisiones pendientes

El documento de requisitos no detalla los atributos completos de cada tabla ni las cardinalidades del modelo. Estos detalles deben definirse en el diagrama E-R y el modelo relacional entregados por separado, sin asumir campos adicionales como requisitos confirmados.

La gestión detallada de sucursales, categorías y usuarios por el administrador general está incluida en la descripción del rol; el requisito funcional RF-09 especifica CRUD de productos para el administrador de sucursal. Conviene precisar las operaciones de administración general durante el diseño detallado.
