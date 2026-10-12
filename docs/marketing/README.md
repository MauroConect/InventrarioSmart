# Landing pública de InventarioSmart

La landing local se sirve con Laravel en `/`. La publicación en Vercel usa una exportación estática de las mismas vistas; el sistema, las sesiones y los datos de comercios permanecen fuera de esa publicación.

## Actualizar la exportación

1. Editar las vistas en `resources/views/marketing`, el controlador web de la landing o los assets en `public`.
2. Con PHP y `vendor` disponibles, ejecutar `npm run build:landing`. Si PHP no está en PATH, definir `LANDING_PHP_BINARY` con su ruta.
3. Ejecutar `npm run check:landing` y `node --test tests/Frontend/marketing-configurator.test.cjs`.
4. Revisar `landing/public` y publicar los cambios junto con las fuentes en Git.

Vercel usa raíz `landing`, framework **Other**, salida `public` y el comando definido en `landing/vercel.json`. Habilitar **Include source files outside of the Root Directory in the Build Step** para que el verificador acceda a las fuentes del repositorio. La verificación compara hashes de fuentes y archivos exportados, normalizando finales de línea Windows/Linux, para impedir desplegar una copia desactualizada. El build remoto no necesita PHP, base de datos ni variables del sistema operativo.

## Contenido y alcance

- La sección Configurable explica **Ajustes → Módulos** y muestra combinaciones de módulos opcionales en una demostración sin guardar cambios.
- El producto base (catálogo, stock, POS y caja) siempre está incluido. El catálogo del ejemplo proviene de `config/modules.php` y sólo muestra módulos disponibles.
- Las extensiones a medida se presentan como un alcance a relevar y acordar antes de desarrollarlas.
- Mauro indicó el 12/10/2026 que todavía no hay destino público de contacto ni ingreso. La versión pública usa navegación al producto/configurador. La landing local conserva registro e ingreso.
- No se incluyen formularios públicos sin receptor, URLs de localhost, credenciales, datos de tenants ni endpoints del inventario.

Las imágenes de comercios son ilustrativas. Las capturas del producto muestran datos de ejemplo; la captura de módulos fue aportada por Mauro. No se presentan fotografías como testimonios ni se prometen métricas de productividad.
