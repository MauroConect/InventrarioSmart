# Roadmap de desarrollo

> **Roadmap vigente:** onboarding asistido e instancia dedicada por cliente. El roadmap multitenant original se conserva al final como antecedente descartado.

## Etapa documental — Definir antes de programar

- aprobar cuestionario, estados, responsables y criterios de calificación;
- definir catálogo inicial de perfiles y módulos;
- acordar política de personalizaciones y propiedad;
- definir estrategia de repositorio, versiones y actualización;
- seleccionar un cliente piloto y documentar su recorrido manual;
- decidir qué código experimental de multitenancy se conserva o revierte.

**Salida:** documentos aprobados y ninguna duda estructural pendiente.

## Fase 1 — Proceso manual validado

- usar landing/formulario simple o herramientas internas para captar un piloto;
- ejecutar discovery, propuesta, configuración, demo y capacitación;
- medir tiempos, preguntas faltantes y retrabajo;
- cerrar checklist de entrega y soporte.

**Salida:** un cliente puede completar el proceso acompañado sin depender de automatización.

## Fase 2 — Portal de captación y backoffice

- landing, registro y verificación de email;
- cuestionario reanudable;
- ficha de cliente y pipeline de onboarding;
- notas, responsables, próximas acciones y documentos;
- soporte y solicitudes vinculados al cliente;
- metadatos de repositorio, versión y entorno, sin datos operativos.

**Salida:** el equipo gestiona el onboarding de punta a punta desde el portal.

## Fase 3 — Producto base y generación repetible

- consolidar `inventariosmart-core`;
- definir perfiles, módulos y puntos de extensión;
- crear manifiesto por cliente;
- automatizar repositorio privado, CI, staging, producción, base y backups;
- eliminar secretos y datos de Git;
- agregar smoke tests por perfil.

**Salida:** una segunda instancia se crea desde una release estable sin copiar pasos manuales opacos.

## Fase 4 — Piloto dedicado

- crear repo e infraestructura del cliente piloto;
- configurar módulos y branding;
- migrar datos con reconciliación;
- probar ventas, stock, caja, tickets y fiscalidad aplicables;
- capacitar y entregar;
- ensayar rollback y restauración.

**Salida:** piloto activo y soporte trazable, sin infraestructura compartida con otros clientes.

## Fase 5 — Actualizaciones y escala

- releases versionadas del núcleo;
- detección de repositorios desactualizados;
- PRs automáticas de upgrade;
- validación en staging por perfil;
- propagación medible de correcciones críticas;
- métricas de costo y divergencia.

**Salida:** incorporar un cliente no impide mantener ni actualizar a los anteriores.

## Puertas vigentes

- no desarrollar una función antes de clasificarla;
- no crear un repo cliente desde una rama inestable;
- no entregar sin backups, validación y capacitación;
- no poner secretos o datos productivos en Git;
- no ampliar clientes si las actualizaciones dependen de copiar cambios manualmente.

## Roadmap histórico descartado — Enfoque

El roadmap prioriza una barrera de aislamiento verificable antes de migrar módulos operativos. Cada fase termina con criterios observables; completar tareas sin satisfacerlos no habilita la siguiente fase.

## Histórico — Fase 0: línea base y red de seguridad

**Entregables**

- Inventario de rutas, modelos, tablas, jobs, archivos, caches y consultas raw.
- Casos de uso por rama y decisión portar/reescribir/descartar.
- Harness de pruebas y fixtures mínimos.
- Pruebas de caracterización para ventas, stock y caja.
- Convenciones de código y ADRs de multitenancy.

**Criterios de aceptación**

- La suite corre en CI con MySQL compatible.
- Hay al menos un test de caso feliz para cada flujo crítico relevado.
- Cada módulo tiene dueño, fuente y dependencias.
- React residual está identificado y no recibe funcionalidad nueva.

## Histórico — Fase 1: fundación multitenant

**Entregables**

- Entidades `Tenant` y membresía.
- Usuarios globales, rol por tenant y superadmin separado.
- Resolver de tenant, `TenantContext` y contrato de `TenantScope`.
- Middleware, policies base y selector de comercio.
- Namespaces de cache/storage y contrato de jobs.
- Auditoría base y observabilidad contextual.

**Criterios de aceptación**

- Un usuario puede pertenecer a dos tenants con roles distintos.
- Solicitudes sin membresía fallan cerradas.
- Contexto se limpia entre requests/tests/jobs.
- Superadmin no obtiene acceso operativo implícito.
- Pruebas demuestran separación de cache, storage y payloads de jobs.

> Esta fase define la fundación. La entrega actual incorpora el plano de plataforma y sus pruebas, pero no agrega aún `tenant_id` a tablas de negocio.

## Histórico — Fase 2: configuración, catálogo y stock

**Entregables**

- Configuración de comercio completa.
- Categorías, descuentos, productos, pesables y scanner existente.
- Proveedores y movimientos de stock.
- Migración de datos con expand/backfill/contract.

**Criterios de aceptación**

- Catálogo y stock sólo muestran datos del tenant activo.
- SKU/códigos y reglas únicas son locales al tenant.
- Cantidades decimales conservan precisión.
- Ajustes de stock requieren permiso y quedan auditados.
- Backfill reconcilia conteos y no deja relaciones cruzadas.

## Histórico — Fase 3: POS, ventas abiertas, tickets y caja

**Entregables**

- POS consolidado en Blade + Alpine.
- Venta abierta, agregado de ítems, cierre y anulación autorizada.
- Tickets configurables por tenant.
- Punto de caja, movimientos, apertura/cierre.
- Medios de pago y vínculo con stock.

**Criterios de aceptación**

- Scanner y productos pesables funcionan sin regresión.
- Dos operadores no corrompen una venta abierta concurrente.
- Cerrar venta actualiza stock y caja en una transacción consistente.
- Venta abierta no introduce mesa/comanda.
- Tickets no mezclan datos/configuración entre tenants.
- Hay pruebas de autorización y acceso cruzado por endpoint.

## Histórico — Fase 4: finanzas y relaciones comerciales

**Entregables**

- Clientes, cuentas corrientes, deudas, cuotas y cheques.
- Reportes y conciliaciones básicos por tenant.
- Auditoría de operaciones sensibles.

**Criterios de aceptación**

- Saldos migrados reconcilian con el legacy.
- Relaciones cliente/venta/cuenta pertenecen al mismo tenant.
- Roles de caja, finanzas y auditor tienen permisos diferenciados.
- Exportaciones y adjuntos están aislados.

## Histórico — Fase 5: fiscal/AFIP

**Entregables**

- Configuración y secretos por tenant.
- Adaptador fiscal, ambiente de homologación y jobs idempotentes.
- Puntos de venta, numeración, CAE, tickets y bandeja de errores.
- Alertas de vigencia de certificados.

**Criterios de aceptación**

- Homologación cubre aprobación, rechazo, timeout y reintento.
- No se duplican comprobantes.
- Numeración es independiente por tenant/punto/tipo.
- Logs y auditoría no contienen secretos.
- No se habilita producción sin checklist fiscal y rollback operativo.

## Histórico — Fase 6: piloto y endurecimiento

**Entregables**

- Piloto controlado, monitoreo, backups y restauración.
- Pruebas de carga y seguridad.
- Runbooks de incidentes y soporte.
- Retiro gradual de duplicación React/legacy validada.

**Criterios de aceptación**

- Cero hallazgos críticos de aislamiento abiertos.
- Restauración de backup ensayada.
- SLOs operativos acordados y medidos.
- Piloto completa ciclos de venta, caja, stock y fiscal.
- Existe aprobación explícita para ampliar tenants.

## Histórico — Fase 7: capacidades posteriores

Ingreso de mercadería por scanner, suscripciones/planes avanzados, reportes ampliados e integraciones. Cada una requiere discovery y criterios propios; no bloquean la plataforma inicial.

## Puertas del roadmap histórico

En todas las fases: migraciones reversibles, pruebas de aislamiento, revisión de consultas raw, políticas de autorización, auditoría, observabilidad, documentación y plan de rollback. Ninguna fecha debe imponerse sobre estas puertas de calidad.
