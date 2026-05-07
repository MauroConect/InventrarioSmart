# Certificados HTTPS (Chrome, PWA, modo sin conexión)

Chrome **no activa** Service Worker ni PWA en `http://192.168.x.x` (sí en `https://` o en `http://localhost` / `http://127.0.0.1`).

## Si el shell te mostró `>` y no terminaba

Faltaba cerrar las comillas del `-addext "..."`. Copiá el comando **en una sola línea** (abajo) o asegurate de que la última línea termine en `"` y Enter.

## Opción A — Misma PC que el servidor (sin certificados)

Abrí: `http://127.0.0.1:8000` (no uses `http://192.168...` en esa máquina).

## Opción B — Certificado solo para probar (localhost / 127.0.0.1)

Una sola línea (evita errores de comillas):

```bash
openssl req -x509 -nodes -days 3650 -newkey rsa:2048 -keyout docker/ssl/local.key -out docker/ssl/local.crt -subj "/CN=localhost" -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"
```

Comprobá que existan `docker/ssl/local.crt` y `docker/ssl/local.key`:

```bash
ls -la docker/ssl/
```

## Opción C — VPS con dominio público (recomendado si entrás por `https://tudominio.com`)

Regenerá el certificado con **tu dominio** (sin `https://`), para que el navegador no marque error de nombre:

```bash
openssl req -x509 -nodes -days 3650 -newkey rsa:2048 -keyout docker/ssl/local.key -out docker/ssl/local.crt -subj "/CN=tudominio.com" -addext "subjectAltName=DNS:tudominio.com,DNS:www.tudominio.com"
```

Reemplazá `tudominio.com` por el tuyo. Luego usá **HTTPS** en la URL con el que entrás (puerto **8443** si usás `docker-compose.https.yml`, o el que tengas en nginx del sistema).

**Producción seria:** usá **Let's Encrypt** (Certbot) con nginx en el host o un proxy, en lugar de este certificado autofirmado.

## Levantar Docker con HTTPS

Desde la raíz del proyecto (donde está `docker-compose.yml`):

```bash
docker compose -f docker-compose.yml -f docker-compose.https.yml up -d
```

- **HTTP:** `http://TU_SERVIDOR:8000`
- **HTTPS:** `https://TU_SERVIDOR:8443` (o el puerto que mapees)

La primera vez Chrome avisará que el certificado no es de una CA conocida: **Avanzado → continuar** (normal con autofirmado).

## `.env`

Mientras probás HTTPS, alineá la URL, por ejemplo:

```env
APP_URL=https://tudominio.com:8443
```

(Ajustá dominio y puerto a lo que uses en producción.)
