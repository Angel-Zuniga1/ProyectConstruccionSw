# Plan de pruebas

Este plan convierte los requisitos funcionales y no funcionales en escenarios verificables. Las pruebas deben ejecutarse en un entorno de prueba, con datos ficticios y sucursales/productos preparados para cada escenario.

## Pruebas funcionales

| ID | Requisito | Escenario | Resultado esperado |
|---|---|---|---|
| CP-01 | RF-01 | Registrar un usuario y después iniciar sesión con sus credenciales. | El registro se completa y el usuario puede autenticarse. |
| CP-02 | RF-01 | Intentar iniciar sesión con una contraseña incorrecta. | El acceso se rechaza sin revelar información sensible. |
| CP-03 | RF-02 | Iniciar sesión y navegar entre las páginas principales. | La sesión autenticada se mantiene durante la navegación. |
| CP-04 | RF-03 | Abrir el catálogo con productos activos de prueba. | Cada producto muestra imagen, precio y descripción. |
| CP-05 | RF-04 | Filtrar por categoría, sucursal y talla, y buscar por nombre. | Cada filtro y la búsqueda muestran productos que corresponden al criterio aplicado. |
| CP-06 | RF-05 | Consultar el detalle de un producto disponible y otro sin existencias en alguna sucursal. | Se muestra el detalle y la disponibilidad correcta por sucursal. |
| CP-07 | RF-06 | Agregar un producto al carrito, cambiar su cantidad y eliminarlo. | El carrito refleja las altas, cambios de cantidad y eliminación. |
| CP-08 | RF-07 | Confirmar un carrito y elegir una sucursal de recolección. | Se genera un pedido con la sucursal elegida. |
| CP-09 | RF-08 | Consultar el historial de pedidos de un cliente. | Se muestran sus pedidos y el estado correspondiente. |
| CP-10 | RF-09 | Como administrador de sucursal, registrar, consultar, modificar y desactivar un producto. | Las operaciones CRUD funcionan y el producto desactivado deja de estar activo en el catálogo, de acuerdo con la implementación. |
| CP-11 | RF-10 | Actualizar las existencias de un producto en una sucursal. | La disponibilidad de esa sucursal refleja el inventario actualizado. |
| CP-12 | RF-11 | Consultar los pedidos de una sucursal y cambiar sus estados. | El administrador ve los pedidos de su sucursal y puede asignar pendiente, listo para recoger, entregado o cancelado. |

## Pruebas no funcionales

| ID | Atributo | Verificación | Resultado esperado |
|---|---|---|---|
| CP-NF-01 | Seguridad | Inspeccionar el almacenamiento de la contraseña de un usuario de prueba. | La contraseña se almacena como hash, no en texto claro. |
| CP-NF-02 | Seguridad | Intentar acceder a administración como cliente. | Las funciones administrativas quedan restringidas por rol. |
| CP-NF-03 | Usabilidad | Usar catálogo, carrito y formularios en una pantalla de celular. | La interfaz es adaptable y los controles principales son utilizables. |
| CP-NF-04 | Rendimiento | Medir el tiempo de carga de las páginas principales. | Registrar tiempos; el equipo debe establecer el umbral cuantitativo, ya que el requisito solo dice «pocos segundos». |
| CP-NF-05 | Mantenibilidad | Revisar la estructura de módulos del código. | Se distingue presentación, lógica y acceso a datos. |

## Límites y datos de prueba

- No se debe probar un flujo de pago con tarjeta como requisito de esta versión: el pago se realiza al recoger el pedido.
- Las reseñas de productos están fuera del alcance.
- Preparar usuarios de prueba para cada rol, productos activos/inactivos, más de una categoría, varias tallas, inventario en distintas sucursales y pedidos con los estados definidos.
- Registrar para cada ejecución: fecha, versión probada, datos utilizados, resultado observado y evidencia de cualquier defecto.
