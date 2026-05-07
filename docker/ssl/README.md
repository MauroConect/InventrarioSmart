# Certificado local (HTTPS para Chrome / PWA / modo sin conexión)

Chrome **no activa** Service Worker ni PWA en `http://192.168.x.x` (solo en `https://` o `http://localhost` / `http://127.0.0.1`).

## Opción 1 — Misma PC que el servidor (sin generar certificados)

En el navegador de **esa** máquina abrí:

`http://127.0.0.1:8000`

(no uses `http://192.168...` en la misma PC).

## Opción 2 — HTTPS en el puerto 8443 (otro dispositivo en la red o forzar HTTPS)

1. Generá el certificado (Git Bash, WSL o Linux; en Windows puede estar `openssl` con Git):

```bash
openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
  -keyout docker/ssl/local.key \
  -out docker/ssl/local.crt \
  -subj "/CN=localhost" \
  -addext "subjectAltName=DNS:localhost,DNS:127.0.0.1,IP:127.0.0.1"
```

Para acceder desde otro equipo por IP, volvé a generar con tu IP, por ejemplo:

`-subj "/CN=192.168.1.50" -addext "subjectAltName=DNS:localhost,IP:192.168.1.50"`

2. Levantá los contenedores **con** el override HTTPS:

```bash
docker compose -f docker-compose.yml -f docker-compose.https.yml up -d
```

3. Entrá a `https://127.0.0.1:8443` (o `https://TU_IP:8443`). Chrome mostrará “No es seguro”: en **Avanzado → Continuar a sitio** (es normal con certificado autofirmado).

4. En `.env`, si hace falta cookies/sesión, podés poner `APP_URL=https://127.0.0.1:8443` mientras probás.

El puerto **8000** sigue siendo HTTP por compatibilidad; **8443** es HTTPS.
