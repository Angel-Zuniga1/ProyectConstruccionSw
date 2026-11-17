# Requisitos funcionales

**Aplicación:** Boutique UADY en línea (nombre provisional: UADY Boutique online).

Los siguientes requisitos funcionales se transcriben y organizan a partir del documento de requisitos del proyecto.

| ID | Requisito |
|---|---|
| RF-01 | El usuario puede registrarse e iniciar sesión (autenticación). |
| RF-02 | El sistema mantiene la sesión del usuario autenticado durante la navegación. |
| RF-03 | El usuario puede ver el catálogo de productos con imagen, precio y descripción. |
| RF-04 | El usuario puede filtrar productos por categoría, sucursal y talla, y buscar por nombre. |
| RF-05 | El usuario puede ver el detalle de un producto y su disponibilidad por sucursal. |
| RF-06 | El usuario puede agregar productos a su carrito, modificar su cantidad y eliminarlos. |
| RF-07 | El usuario puede confirmar su carrito para generar un pedido y elegir la sucursal donde lo recogerá. |
| RF-08 | El usuario puede consultar el historial y el estado de sus pedidos. |
| RF-09 | El administrador de sucursal puede registrar, consultar, modificar y desactivar productos (CRUD). |
| RF-10 | El administrador puede actualizar el inventario de su sucursal. |
| RF-11 | El administrador puede ver los pedidos de su sucursal y cambiar su estado a pendiente, listo para recoger, entregado o cancelado. |

## Roles relacionados

- **Cliente:** alumno, egresado, personal o público en general que compra productos.
- **Administrador de sucursal:** gestiona productos, inventario y pedidos de su boutique.
- **Administrador general:** gestiona sucursales, categorías y usuarios.

El acceso a las funciones de administración se restringe según el rol. La definición de funciones y permisos específicos del administrador general requiere detalle adicional, ya que el documento describe el rol pero no enumera sus operaciones como requisitos RF independientes.

## Límites funcionales de esta versión

- El pago se realiza al recoger el pedido; no se incluye pago en línea con tarjeta.
- No se incluyen reseñas de productos.
