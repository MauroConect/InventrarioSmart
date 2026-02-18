# Guía: Configurar Nginx como Proxy para Inventario Inteligente

## Situación Actual

Tienes varios archivos de configuración en `/etc/nginx/sites-available`:
- `app.conf`
- `app.confe`
- `default`
- `lucianogonzalez`

## Recomendación: Crear un Archivo Nuevo

**NO modifiques los archivos existentes** a menos que sepas qué hacen. Es mejor crear uno nuevo específico para este proyecto.

## Opción 1: Usar el Script Automatizado (Recomendado)

```bash
# Dar permisos
chmod +x configurar-nginx-proxy.sh

# Ejecutar
./configurar-nginx-proxy.sh
```

El script te preguntará:
- El dominio o IP a usar
- El puerto interno de Docker (por defecto 8000)
- Y creará todo automáticamente

## Opción 2: Crear Manualmente

### Paso 1: Crear el archivo de configuración

```bash
sudo nano /etc/nginx/sites-available/inventario
```

### Paso 2: Pegar esta configuración

**Si usas un dominio:**
```nginx
server {
    listen 80;
    server_name inventario.tudominio.com;  # Cambiar por tu dominio

    access_log /var/log/nginx/inventario-access.log;
    error_log /var/log/nginx/inventario-error.log;

    client_max_body_size 20M;

    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
}
```

**Si usas solo IP:**
```nginx
server {
    listen 80;
    server_name TU_IP_AQUI;  # Cambiar por la IP de tu servidor

    access_log /var/log/nginx/inventario-access.log;
    error_log /var/log/nginx/inventario-error.log;

    client_max_body_size 20M;

    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### Paso 3: Habilitar el sitio

```bash
# Crear enlace simbólico
sudo ln -s /etc/nginx/sites-available/inventario /etc/nginx/sites-enabled/

# Verificar configuración
sudo nginx -t

# Si todo está bien, recargar nginx
sudo systemctl reload nginx
```

## Paso 4: Modificar docker-compose.prod.yml

Antes de levantar Docker, cambia el puerto en `docker-compose.prod.yml`:

```yaml
webserver:
  ports:
    - "8000:80"  # Cambiar de "80:80" a "8000:80"
    # - "443:443"  # Comentar si no usas SSL en el contenedor
```

## Verificar que Funciona

```bash
# Verificar que nginx está corriendo
sudo systemctl status nginx

# Verificar que Docker está en el puerto 8000
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps

# Probar la aplicación
curl http://localhost
# O desde el navegador: http://tu-dominio-o-ip
```

## Configurar SSL/HTTPS (Opcional pero Recomendado)

```bash
# Instalar certbot si no lo tienes
sudo apt-get install certbot python3-certbot-nginx

# Obtener certificado SSL
sudo certbot --nginx -d tu-dominio.com

# Certbot modificará automáticamente el archivo inventario
```

## Troubleshooting

### Error: "upstream connection refused"
- Verifica que Docker esté corriendo: `docker ps`
- Verifica el puerto: `netstat -tulpn | grep 8000`

### Error: "502 Bad Gateway"
- Verifica que el contenedor esté corriendo: `docker-compose ps`
- Revisa logs: `docker-compose logs webserver`

### Ver logs de Nginx
```bash
sudo tail -f /var/log/nginx/inventario-error.log
sudo tail -f /var/log/nginx/inventario-access.log
```

## Nota Importante

**NO modifiques** los archivos existentes (`app.conf`, `default`, etc.) a menos que sepas exactamente qué hacen. Cada archivo puede estar sirviendo otra aplicación.

Si necesitas ver qué hace cada archivo:
```bash
cat /etc/nginx/sites-available/app.conf
cat /etc/nginx/sites-available/default
# etc.
```

Pero es mejor crear uno nuevo (`inventario`) para no afectar otras aplicaciones.
