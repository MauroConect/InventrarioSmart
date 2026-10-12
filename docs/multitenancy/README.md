# Plan de evolución de InventarioSmart

Este directorio reúne el análisis original y la planificación vigente. La decisión actual es un **portal central con onboarding asistido**, un **producto operativo multi-tenant** y un **repositorio privado de features por cliente**.

La [ADR de instancia por cliente](12-ADR-INSTANCE-PER-CLIENT.md) reemplaza la arquitectura multitenant compartida descrita en los documentos históricos 02, 03, 04, 07 y 09.

## Decisiones rectoras

- `polirubro` continúa como referencia funcional para el producto base.
- El portal central captura registros, cuestionarios, onboarding y soporte.
- La configuración inicial la realiza el equipo de InventarioSmart junto al cliente.
- Cada cliente recibe repositorio, despliegue, base, storage, backups y secretos dedicados.
- `inventariosmart-core` concentra las mejoras reutilizables; los repos cliente declaran versión, módulos y overrides.
- Las funciones solicitadas se clasifican antes de decidir si son configuración, mejora común o extensión exclusiva.
- Blade + Alpine es la interfaz canónica. React residual o duplicado se retira sólo después de verificar paridad.
- La migración reutiliza capacidades selectivamente; no fusiona ramas completas.

## Alcance actual

La etapa actual es documental. Define el proceso comercial, la separación entre portal y aplicación operativa, la estrategia de repositorios y el roadmap. No autoriza implementar provisioning, multitenancy compartida ni personalizaciones de clientes.

## Mapa documental

1. [Análisis de ramas](00-BRANCH-ANALYSIS.md)
2. [Visión de producto](01-PRODUCT-VISION.md)
3. [Arquitectura objetivo](02-TARGET-ARCHITECTURE.md)
4. [Modelo de datos](03-DATA-MODEL.md)
5. [Autorización y aislamiento](04-AUTHORIZATION-AND-ISOLATION.md)
6. [Catálogo de módulos](05-MODULE-CATALOG.md)
7. [Fiscal y AFIP](06-FISCAL-AFIP.md)
8. [Playbook de migración legacy](07-LEGACY-MIGRATION-PLAYBOOK.md)
9. [Roadmap de desarrollo](08-DEVELOPMENT-ROADMAP.md)
10. [Alcance de la fundación](09-FOUNDATION-SCOPE.md)
11. [Onboarding asistido](10-ASSISTED-ONBOARDING.md)
12. [Estrategia de repositorio por cliente](11-CLIENT-REPOSITORY-STRATEGY.md)
13. [ADR de instancia dedicada](12-ADR-INSTANCE-PER-CLIENT.md)
14. [Alcance del portal central](13-CENTRAL-PORTAL-SCOPE.md)

## Orden recomendado de lectura

Para la decisión vigente: ADR 12 → onboarding 10 → repositorios 11 → visión → roadmap. Los documentos de multitenancy compartida se conservan como antecedente, no como instrucciones de implementación.

## Definiciones

- **Portal central:** landing, cuentas, cuestionarios, onboarding, soporte y backoffice.
- **Cliente:** comercio contratado, con una o más personas de contacto.
- **Instancia cliente:** aplicación operativa exclusiva de un comercio.
- **Producto base:** núcleo versionado del que derivan las instancias.
- **Override:** extensión excepcional y explícita de un repositorio cliente.
