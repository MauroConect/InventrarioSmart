# Onboarding asistido vigente

El contacto se registra, verifica email y completa un cuestionario reanudable. El equipo revisa, releva, propone módulos, provisiona un Tenant en la aplicación compartida y acompaña validación/capacitación. No hay activación técnica automática de infraestructura dedicada.

TenantOnboarding conserva contacto, comercio, responsable, draft, estado, próximo paso y timestamps. Notas internas y eventos de estado son visibles según permisos. Los documentos/checklist/aprobaciones de ISM-19 deben separar visibilidad del cliente y controles internos; cada aprobación tiene responsable y auditoría.

Los módulos se configuran desde config/modules.php con dependencias y base incluida. Activar entrega requiere criterios de validación satisfechos; no habilitar fiscalidad real sin homologación. La transición activa no significa que se publicaron hosting, secretos o un repositorio externo.

[Portal](13-CENTRAL-PORTAL-SCOPE.md), [versiones](11-CLIENT-REPOSITORY-STRATEGY.md) y [ADR](14-ADR-MULTITENANT-COMPARTIDO.md).
