# Alcance de la fundación multitenant

> **Documento histórico, no vigente para nuevas implementaciones.** La ADR 12 reemplazó esta fundación por un portal central y una instancia/repositorio dedicado por cliente. Se conserva para explicar decisiones y trabajo experimental previo.

## Objetivo de la entrega

Establecer una base documental y un esqueleto técnico verificable para evolucionar InventarioSmart a SaaS multitenant sin alterar prematuramente datos de negocio ni mezclar ramas completas. La fundación reduce decisiones implícitas y define las puertas de calidad de las siguientes entregas.

## Incluido

- Arquitectura objetivo de MySQL compartida con `tenant_id`.
- Definición de `Tenant`, usuario global y pivot `tenant_user`.
- Rol por tenant y separación del superadmin.
- Responsabilidades de `TenantContext` y `TenantScope` propios.
- Estrategias de aislamiento para cache, storage, jobs, scheduler, logs y AFIP.
- Catálogo de módulos y procedencia por ramas.
- Modelo objetivo y estrategia expand/backfill/contract.
- Reglas de autorización, auditoría y pruebas de aislamiento.
- Playbook de migración legacy.
- Roadmap faseado con criterios de aceptación.
- Límites fiscales y contrato de integración AFIP.
- Migraciones de plataforma para `tenants`, `tenant_user` y el indicador de superadmin.
- Modelos, relaciones, `TenantContext`, `TenantScope` y trait `BelongsToTenant`.
- Middleware de tenant y superadmin, selección de comercio y endpoint mínimo de plataforma.
- Seeders opt-in sin credenciales fijas y configuración de compatibilidad legacy.
- Pruebas aisladas para contexto, membresía y separación entre tenants.

## Excluido explícitamente

- Agregar `tenant_id` a tablas de negocio existentes.
- Ejecutar backfills, constraints o migraciones de datos.
- Fusionar `main` u `origin/heladeria` completas.
- Portar todos los módulos o resolver divergencias de implementación.
- Activar AFIP en homologación o producción.
- Crear mesa, comanda o entidades gastronómicas.
- Implementar ingreso de mercadería por scanner.
- Reescribir el frontend o ampliar React.
- Eliminar código legacy/residual.
- Crear planes, cobros de suscripción o provisioning automático.
- Realizar commits o publicar la rama.

## Artefactos implementados en esta fundación

1. modelos y migraciones de plataforma (`tenants`, membresías y superadmin);
2. `TenantContext` registrado como singleton de aplicación;
3. middleware para resolver membresía y tenant desde sesión o header;
4. trait y `TenantScope` ensayados exclusivamente sobre un fixture de pruebas;
5. rol efectivo por membresía del tenant activo, con `users.role` como compatibilidad legacy;
6. selector web, metadatos de tenant en login API y consulta mínima para superadmin;
7. seeders configurables y suite inicial de aislamiento.

La incorporación de `tenant_id` a negocio debe ser una entrega posterior, tabla por tabla.

## Invariantes no negociables

- Una request o job opera con cero o un tenant, nunca con contexto ambiguo.
- Una identidad no accede a un tenant sin membresía vigente.
- El rol efectivo proviene de la membresía del tenant activo.
- El superadmin no equivale a miembro universal.
- Todo recurso tenant-aware tiene namespace o pertenencia explícita.
- El código falla cerrado cuando falta contexto.
- Ninguna migración asigna datos ambiguos a un tenant por defecto.
- AFIP mantiene credenciales y numeración independientes.

## Criterios de aceptación de esta entrega

- En la entrega histórica existían los documentos originales enumerados entonces en `README.md`.
- Los documentos están en español y son coherentes entre sí.
- `polirubro` figura como base de integración.
- Se documenta el aporte diferenciado de `main` y `origin/heladeria`.
- Se aclara que no existen mesa/comanda ni ingreso de mercadería por scanner.
- Blade + Alpine es objetivo y React queda identificado como residual.
- La ausencia de multitenancy y tests reales se refleja en riesgos y roadmap.
- La arquitectura usa MySQL compartida, `tenant_id`, `TenantContext`, `TenantScope`, `tenant_user` y rol por tenant.
- Cache, storage, jobs y AFIP tienen aislamiento definido.
- El roadmap contiene entregables y criterios de aceptación por fase.
- No se agregó `tenant_id` ni un scope parcial a tablas operativas.
- Las migraciones de plataforma pueden ejecutarse y revertirse sin tocar datos de negocio.
- La suite demuestra fallo cerrado y separación entre dos tenants.

## Condiciones para migrar el primer módulo

Antes de migrar el primer módulo se requiere inventario de consultas raw, backup/restauración ensayados, entorno MySQL de CI y una regla inequívoca para mapear datos legacy a tenants.

## Definición de terminado

Esta entrega termina con documentación revisable, migraciones limitadas al plano de plataforma, esqueleto runtime sin scopes de negocio y pruebas de aislamiento, además de una secuencia clara para continuar de manera incremental.

## Validación ejecutada

- Esquema completo aplicado sobre una base MySQL 8 aislada.
- Las tres migraciones de plataforma se aplicaron, revirtieron y reaplicaron correctamente.
- Rutas de selección, contexto y consulta de plataforma cargadas por Laravel.
- Suite completa: 9 pruebas aprobadas y 24 aserciones.
- Cobertura específica: restauración de contexto, bypass acotado, fallo cerrado, separación de consultas, rol por membresía, rechazo cross-tenant y selección autorizada de comercio.
