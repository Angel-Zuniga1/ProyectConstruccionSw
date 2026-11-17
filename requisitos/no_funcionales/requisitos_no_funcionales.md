# Requisitos no funcionales

**Aplicación:** Boutique UADY en línea (nombre provisional: UADY Boutique online).

| Atributo | Requisito |
|---|---|
| Seguridad | Las contraseñas se almacenan con hash. El acceso a las funciones de administración está restringido por rol. |
| Usabilidad | La interfaz debe ser sencilla y adaptable a celulares mediante un diseño responsivo. |
| Rendimiento | Las páginas principales deben cargar en pocos segundos. El documento no establece un umbral numérico. |
| Mantenibilidad | El código debe organizarse en módulos, separando presentación, lógica y acceso a datos. |

## Criterios de verificación derivados

- Comprobar que el almacenamiento de contraseñas no conserve contraseñas en texto claro y que la autenticación permita iniciar sesión con credenciales válidas.
- Comprobar que un usuario sin el rol adecuado no pueda acceder a funciones de administración.
- Revisar la interfaz en tamaños de pantalla de celular y escritorio y confirmar que los controles principales se puedan utilizar.
- Medir la carga de las páginas principales en el entorno de prueba y registrar los resultados. El equipo debe acordar el límite concreto, pues el requisito original solo indica «pocos segundos».
- Revisar la organización del código para verificar la separación de presentación, lógica de negocio y acceso a datos.
