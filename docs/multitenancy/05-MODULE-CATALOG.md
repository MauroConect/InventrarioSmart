# Catálogo de módulos vigente

El catálogo ejecutable es config/modules.php; grants y permisos se evalúan por tenant. Un `is_ready` expresa disponibilidad de interfaz, no homologación externa ni certificación productiva.

| Capacidad | Estado de esta entrega |
|---|---|
| core.catalog / core.stock / core.pos / core.cash | Base siempre incluida; pruebas transaccionales y concurrentes ISM-20/21 |
| catalog.scanner / catalog.weighted | Opcionales existentes para POS, código y cantidades decimales |
| sales.open_orders | Comandas existentes con etiqueta y cierre posterior |
| sales.tables | No habilitado; diseño de plano/estados ISM-28, distinto de las comandas existentes |
| services.appointments | Turnos existentes con relaciones scoped |
| finance.credit / finance.cheques | Opcionales existentes; aislamiento y reconciliación entregados ISM-7/8 |
| fiscal.afip | Código existente; separación ISM-9 e idempotencia ISM-21; homologación real pendiente de entorno |
| operations.audit | Timeline operativo consultable según permisos |
| platform.support | Chat y solicitudes existentes por comercio |
| Recepción por scanner | Diseño ISM-29; no confundir búsqueda de POS con ingreso de stock |

Registro, onboarding, backoffice, roles y branding están implementados en la rama integrada. Documentos/checklist/aprobaciones se completan en ISM-19. Los módulos no se habilitan ni se anuncian listos antes de su validación.

Las referencias de ramas del análisis original son antecedentes de procedencia, no instrucciones para fusionar variantes. La arquitectura vigente es [multitenant compartida](14-ADR-MULTITENANT-COMPARTIDO.md).
