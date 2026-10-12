# Modelo de datos vigente

La [ADR compartida](14-ADR-MULTITENANT-COMPARTIDO.md) define aislamiento lógico; no hay una base física por cliente en esta entrega.

| Entidad | Propiedad y relaciones |
|---|---|
| users | Identidad global; email único; contraseña protegida; flag de superadmin separado |
| tenants | UUID/slug únicos, nombre, estado, timezone y metadatos de versión/entrega |
| tenant_user | Pareja tenant/user única, role string, status e is_default; no existe tabla roles |
| platform_modules / tenant_modules | Catálogo de capacidades y grants de módulos por tenant |
| tenant_onboardings | Caso global de plataforma vinculado al contacto y al tenant provisionado |
| categorías / proveedores / productos | tenant_id; código único por tenant; referencias al mismo comercio |
| clientes | tenant_id; relaciones comerciales del mismo tenant |
| cajas / ventas / items / movimientos | tenant_id; operadores miembros; referencias internas consistentes |
| cuentas / deudas / cheques | tenant_id, relaciones y saldos aislados; migraciones reconcilian importes |
| turnos / custom fields / support | contexto de comercio y autorización según el flujo |
| configuración de comercio | una configuración por tenant; branding y cache scoped |
| fiscalidad / adjuntos | controles transversales en ISM-9/12; no asumir protección por el scope de Venta |

Las migraciones legacy resuelven propiedad antes de ejecutar DDL; ambigüedades se rechazan. No reasignar un tenant_id para reparar referencias sin reconciliación explícita. Los índices únicos locales impiden duplicados dentro del comercio y permiten valores iguales entre comercios.

El esquema real está en database/migrations. Los tests con el esquema completo reinician exclusivamente la base descartable `testing`, usan transacciones para los fixtures y eliminan ese esquema al terminar la clase.
