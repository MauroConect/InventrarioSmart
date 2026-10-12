# Fiscalidad por tenant y validación pendiente

La configuración tiene tenant_id único y se resuelve exclusivamente desde el contexto activo. CUIT/punto/ambiente no se pueden reutilizar en otro tenant porque comparten una numeración externa. La passphrase se guarda cifrada con APP_KEY y no se serializa; su backup requiere conservar APP_KEY fuera de Git.

Los campos de certificado/clave aceptan sólo nombres simples. Las credenciales se instalan en disco private bajo `tenants/<uuid>/fiscal/<ambiente>/credentials/`. Se rechazan rutas ajenas, absolutas, traversal y symlinks fuera del namespace. No se cargan secretos por un formulario público ni se publican archivos fiscales.

## Adaptador y estado

La inspección del SDK PHP 1.2 instalado mostró autenticación mediante app.afipsdk.com, que recibe certificado/clave. Se retiró ese camino del servicio de emisión. El adaptador directo usa los endpoints oficiales WSAA/WSFE, conserva tokens de autenticación en privado por tenant/ambiente y no entrega credenciales a ese proxy.

`FISCAL_ENABLED=false` por defecto impide cualquier llamada externa. `FISCAL_PRODUCTION_ENABLED=false` también impide emisión productiva. El adaptador SOAP necesita la extensión SOAP y certificados asociados a wsfe. **No habilitar los flags con esta entrega:** la homologación está sin configurar, conforme a la respuesta de Mauro del 12/10/2026.

Se prueban configuración, cifrado, namespaces y selección de emisor/venta con un gateway simulado. El transporte real, firmado CMS, esquemas y payloads deben validarse con homologación antes de emitir. El payload legacy todavía necesita revisión completa contra las condiciones fiscales, IVA y campos vigentes del receptor; no se presenta como motor fiscal certificado. Aprobación/rechazo/timeout/recuperación externos siguen pendientes en ISM-9/21.

## Migración y operación

Una configuración global sólo se puede asignar automáticamente cuando hay exactamente un tenant y una configuración. Con varios dueños/configuraciones, se detiene antes del DDL y requiere reconciliación explícita. No se leen/mueven archivos secretos legacy automáticamente. La base local tenía cero configuraciones: se amplía el esquema sin trasladar secretos reales.

El rollback de una tabla fiscal poblada se rechaza: no elimina propiedad ni descifra passphrases. Restaurar un snapshot fiscal requiere un entorno aislado, versión compatible y APP_KEY correcto. No regenerar APP_KEY en un despliegue con secretos cifrados.

## Referencias primarias consultadas el 12/10/2026

- [Documentación oficial WSAA y entornos](https://www.afip.gob.ar/ws/documentacion/wsaa.asp).
- [Manuales oficiales de factura electrónica](https://www.afip.gob.ar/fe/ayuda/webservice.asp).
- [Manual WSFE v4.7, revisión 01/09/2026](https://www.afip.gob.ar/ws/documentacion/manuales/manual-desarrollador-ARCA-COMPG.pdf).

La documentación oficial especifica la consulta de comprobantes para reconciliar respuestas incompletas. La idempotencia local no reemplaza la aprobación de ARCA ni habilita producción.
