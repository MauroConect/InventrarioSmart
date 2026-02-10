# ⚠️ Solución: Puerto 3306 en Uso

## Problema
El puerto 3306 está siendo usado por otro servicio MySQL en tu sistema local.

## Solución Aplicada
He cambiado el puerto de MySQL en Docker a **3307** para evitar conflictos.

### Configuración Actual:
- **Puerto Host (tu máquina):** 3307
- **Puerto Contenedor Docker:** 3306 (interno)
- **Para conectarte desde fuera de Docker:** usa el puerto 3307

### Conexión a la Base de Datos:

**Desde tu aplicación Laravel (dentro de Docker):**
- Host: `db` (nombre del servicio)
- Puerto: `3306` (interno)
- Database: `inventario_db`
- User: `inventario_user`
- Password: `root`

**Desde herramientas externas (como MySQL Workbench, phpMyAdmin, etc.):**
- Host: `localhost`
- Puerto: `3307` ← **Este es el nuevo puerto**
- Database: `inventario_db`
- User: `inventario_user`
- Password: `root`

## Nota Importante
El archivo `.env` NO necesita cambios, ya que la aplicación Laravel dentro de Docker se conecta usando el nombre del servicio `db` en el puerto interno 3306.

## Alternativa: Detener MySQL Local

Si prefieres usar el puerto 3306 estándar, puedes:

1. **Detener el servicio MySQL local:**
   ```cmd
   net stop MySQL80
   ```
   (O el nombre de tu servicio MySQL)

2. **Luego cambiar el docker-compose.yml de vuelta a:**
   ```yaml
   ports:
     - "3306:3306"
   ```

Pero es más seguro usar puertos diferentes para evitar conflictos.
