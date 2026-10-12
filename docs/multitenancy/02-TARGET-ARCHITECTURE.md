# Arquitectura vigente

Referencia normativa: [ADR multitenant compartida](14-ADR-MULTITENANT-COMPARTIDO.md).

## Planos

La landing exportada es estática y se publica en Vercel. Laravel local sirve registro, ingreso, verificación, recuperación, onboarding y soporte. El backoffice requiere superadmin de plataforma; las rutas operativas requieren membresía y tenant activo.

La API utiliza Sanctum y prioriza la sesión web autorizada. `SetTenantContext` resuelve header/sesión/dominio, comprueba pertenencia y estado, establece contexto antes de bindings y lo limpia en finally. `CheckPermission` evalúa el rol de la membresía; `EnsureTenantFeature` comprueba el módulo. El superadmin no tiene acceso operativo implícito.

## Datos y recursos

Una base MySQL 8 aloja usuarios globales, membresías, plataforma y tablas operativas con tenant_id. `BelongsToTenant` filtra consultas, asigna propietario e impide cambios de tenant; `HasTenantRelations` verifica referencias de negocio y operadores. Cache, archivos privados y procesos de fondo deben mantener el mismo contexto.

## Entrega

Rama integrada `feature/ISM-1-tenant-listo`, commits por entrega, pruebas con dos tenants ficticios y evidencia en Jira. El exportador de landing verifica hashes entre Windows/Linux. Publicar la landing no publica el inventario ni modifica una base productiva. La estrategia de instancias dedicadas anterior es histórica.
