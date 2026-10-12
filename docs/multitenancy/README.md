# InventarioSmart: arquitectura y entrega multitenant

La decisión vigente es [MySQL compartida y aislamiento por tenant](14-ADR-MULTITENANT-COMPARTIDO.md), conforme al producto implementado y al desarrollo ISM autorizado por Mauro el 12/10/2026. El portal de onboarding es asistido; los comercios operan en la aplicación Laravel mediante membresías, módulos y permisos propios.

La antigua decisión de infraestructura dedicada está [archivada](history/2026-08-23/12-ADR-INSTANCE-PER-CLIENT.md). Sus límites no gobiernan esta entrega. No se generan repositorios, bases, secretos o despliegues de clientes por registrar un comercio.

## Referencias vigentes

- [Visión](01-PRODUCT-VISION.md), [arquitectura](02-TARGET-ARCHITECTURE.md) y [modelo](03-DATA-MODEL.md).
- [Autorización](04-AUTHORIZATION-AND-ISOLATION.md) y [catálogo de módulos](05-MODULE-CATALOG.md).
- [Roadmap y estado de validación](08-DEVELOPMENT-ROADMAP.md).
- [Fundación](09-FOUNDATION-SCOPE.md), [onboarding](10-ASSISTED-ONBOARDING.md), [versiones](11-CLIENT-REPOSITORY-STRATEGY.md) y [portal](13-CENTRAL-PORTAL-SCOPE.md).
- [Landing pública y exportación](../marketing/README.md).

Los documentos 00, 06 y 07 conservan análisis históricos y requieren leer su estado. La evidencia verificable de cada entrega está en Jira y commits; una propuesta de diseño no implica una función habilitada ni una validación externa ejecutada.
