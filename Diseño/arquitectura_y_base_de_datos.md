# Arquitectura e información de base de datos

## Arquitectura de dos capas

El sistema se construirá con un FrontEnd y un BackEnd que se comunican mediante peticiones HTTP.

### FrontEnd

Desarrollado con HTML, CSS y JavaScript. Presenta el catálogo, el carrito y los formularios, y envía al servidor las solicitudes realizadas por los usuarios.

### BackEnd

Recibe las solicitudes, valida los datos, aplica la lógica de negocio —autenticación, sesiones, cálculo de totales y control de inventario— y se comunica con la base de datos.

### Comunicación e integración

El FrontEnd consumirá servicios del BackEnd, por ejemplo `/login`, `/productos`, `/carrito` y `/pedidos`. La respuesta podrá entregarse en formato JSON o como páginas generadas por el servidor. La integración se realizará incrementalmente: primero index, autenticación y catálogo; luego el resto de los módulos; finalmente la integración completa.

## Base de datos relacional

El documento de requisitos define nueve tablas:

| Tabla | Responsabilidad indicada |
|---|---|
| `usuarios` | Datos de acceso y perfil de cada usuario, con su rol. |
| `sucursales` | Boutiques ubicadas en las distintas facultades. |
| `categorias` | Clasificación de productos (playeras, sudaderas, gorras, accesorios, entre otros). |
| `productos` | Catálogo general: nombre, descripción, precio, imagen, categoría y estado activo. |
| `inventario` | Existencias de cada producto por talla en cada sucursal. |
| `carritos` | Carrito activo de cada usuario. |
| `detalle_carrito` | Productos y cantidades dentro de un carrito. |
| `pedidos` | Pedido confirmado: usuario, sucursal de recolección, fecha, total y estado. |
| `detalle_pedido` | Productos, cantidades y precio unitario de cada pedido. |

El catálogo y el inventario deben permitir consultar disponibilidad por sucursal y talla. Los pedidos deben conservar su sucursal de recolección y estado, y sus detalles las cantidades y el precio unitario indicados por los requisitos.

## Diagramas y detalle pendiente

El diagrama entidad-relación y el modelo relacional se entregan en documentos aparte. El requisito proporcionado no especifica todas las columnas, claves, restricciones ni cardinalidades; por tanto, este documento no las inventa. Deben completarse y validarse en dichos entregables de diseño.

## Entregas incrementales

| Fecha | Hito que afecta al diseño e integración |
|---|---|
| 29/10/2026 | Requisitos, diagrama E-R, index, autenticación y una funcionalidad: catálogo. |
| 12/11/2026 | Carrito, pedidos y módulo de administración. |
| 26/11/2026 | Integración final, sesiones, manual de usuario y manual técnico. |
