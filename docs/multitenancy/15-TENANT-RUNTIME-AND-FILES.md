# Contexto de ejecución y archivos privados

## Requests, jobs y scheduler

SetTenantContext resuelve membresía y limpia el contexto en finally. Los trabajos comerciales extienden `App\Jobs\TenantJob`: payload con tenantId y actorUserId explícitos, sin modelos serializados ni secretos. El middleware valida tenant activo/configurado y membresía del actor cuando existe, y ejecuta dentro de TenantContext::run. El despacho espera commit por defecto; un worker limpia el contexto en cada iteración. Los jobs síncronos anidados restauran el contexto del llamador.

TenantScheduler::forEachActive recorre sólo comercios activos y configurados. No se creó un proceso comercial periódico ficticio para demostrarlo; todo futuro comando programado debe usar ese contrato. Un actor nulo se reserva a procesos internos de sistema, nunca a payloads recibidos de clientes.

TenantRuntime construye claves de cache/locks `tenant:<id>:<key>` y rutas privadas `tenants/<uuid>/<suffix>`. Sin contexto falla cerrado; rutas absolutas y traversal se rechazan. El cache file está configurado y permite locks compartidos entre procesos locales; una instalación distribuida requiere un backend de locks compartido probado.

Los canales single/daily/stderr agregan tenant_id dinámico y sanean claves sensibles del contexto. No escribir certificados, claves, tokens o datos de clientes en el mensaje del log; la sanitización estructurada no puede identificar secretos arbitrarios incrustados en texto libre.

## Adjuntos de ventas

Las filas tienen tenant_id y venta del mismo comercio. Archivos nuevos quedan en disco private (`storage/app/private`), bajo `tenants/<uuid>/sales/<venta>/`. La API expone una URL de descarga autorizada; no publica ruta física ni URL de storage público. Descarga exige ventas.view; carga/eliminación ventas.create y módulo POS. MIME permitido PDF/JPG/PNG/WebP, máximo cinco archivos de 10 MB por solicitud.

La descarga fuerza Content-Disposition attachment, no-store y nosniff. El directorio privado nunca se enlaza con public/storage. Nginx bloquea explícitamente el directorio público legacy ventas_adjuntos; otras implementaciones del servidor deben conservar esa regla antes de servir el producto.

## Migración legacy

1. Backup privado de MySQL y archivos antes de migrar.
2. Ejecutar migración de tenant_id: adjuntos sin venta/owner válido detienen el proceso antes del DDL.
3. `php artisan tenant:privatize-sale-attachments` hace preflight sin cambios.
4. Con respaldo verificado, `php artisan tenant:privatize-sale-attachments --apply` copia a namespace privado, compara SHA-256, actualiza la referencia en transacción y elimina el original público.
5. Verificar descarga A, rechazo B/sin sesión y ausencia de archivo público. Con cero filas, el comando es un no-op. Un archivo faltante/path inválido no se saltea silenciosamente.

La base local tenía cero adjuntos al revisar ISM-12; no se movieron archivos reales. El ensayo con archivo legacy ficticio prueba copia/reconciliación/reintento. La migración del esquema no vuelve a publicar archivos al hacer rollback: revertir privacidad es una decisión operativa separada.
