# Roadmap de entrega multitenant

La [ADR vigente](14-ADR-MULTITENANT-COMPARTIDO.md) reemplaza el roadmap de repositorios e infraestructura dedicada. Mauro autorizó completar el backlog ISM e integrar commits/pushes en `feature/ISM-1-tenant-listo`.

## Base entregada y endurecimiento

Fundación, onboarding asistido, catálogo de módulos, configuración de comercio y soporte existen. ISM-26 consolidó Git; ISM-6/7/8/10/14 aislaron clientes, finanzas, cheques, dashboard y widgets. ISM-30 publicó la landing en Vercel.

La siguiente entrega cubre relaciones operativas (ISM-11), archivos (12), contexto de jobs/cache/logs (13), identidad/selector/roles (15/16/17) y recuperación (18). Se cierra cada ticket con pruebas concretas y trazabilidad; el tablero es la fuente del estado actualizado.

## Flujos y calidad

ISM-19: documentos, checklist y aprobación de onboarding. ISM-20/21: ciclo venta-stock-caja, locking e idempotencia. ISM-22: CI con MySQL y aislamiento por endpoint. ISM-23: ensayo de backup/restauración/rollback en entorno aislado. ISM-24: piloto ficticio y regresiones visuales por perfil.

## Versiones y diseño

ISM-25 alinea estos documentos; ISM-27 define versiones y provisioning del tenant repetible, sin asumir infraestructura por cliente. ISM-28/29 entregan diseño de mesas y recepción por scanner con contratos y ejemplos, sin declarar módulos productivos por disponer de un diseño.

## Dependencia fiscal externa

ISM-9/21 requieren homologación ARCA/AFIP. Mauro confirmó el 12/10/2026 que el entorno no está configurado. Implementar aislamiento y adaptador simulado permite probar controles localmente; no cumple por sí solo la validación externa. Esa parte permanece pendiente y debe constar en Jira.

## Puertas

- Sin secretos o datos reales en Git, artefactos públicos o logs.
- Sin acceso operativo implícito para plataforma ni relaciones cruzadas.
- Sin cierre artificial de tickets con criterios externos pendientes.
- Sin migraciones productivas, emisión fiscal real o despliegue del backend por publicar la landing.
- Los backups y pilotos usan fixtures y entornos descartables, sin restaurar sobre datos activos.
