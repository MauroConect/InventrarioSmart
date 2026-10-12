# Estrategia de repositorio e instancia por cliente

## Decisión

Cada cliente tendrá:

- un repositorio Git privado;
- una aplicación desplegada de forma independiente;
- una base de datos, storage, backups y secretos propios;
- configuración y módulos ajustados a su operación.

El portal central administra captación, onboarding, soporte y trazabilidad de versiones. No almacena ventas, stock, cajas ni secretos fiscales de los clientes.

## Estructura recomendada

```text
inventariosmart-core/             producto base canónico
client-acme/                      instancia privada del cliente Acme
client-comercio-sur/              instancia privada de Comercio Sur
portal-inventariosmart/           landing, onboarding y backoffice central
```

`inventariosmart-core` es la única fuente de evolución común. Los repositorios cliente no deben convertirse en copias sin vínculo ni acumular cambios directos imposibles de actualizar.

## Creación de un repositorio cliente

1. seleccionar una versión estable etiquetada de `inventariosmart-core`;
2. generar un repositorio privado desde una plantilla automatizada;
3. guardar en un manifiesto la versión base y módulos habilitados;
4. aplicar configuración, branding y adaptadores del cliente;
5. crear CI, entornos, secretos y backups;
6. registrar URL, responsable y versión en el portal;
7. impedir secretos, dumps y datos personales dentro de Git.

Ejemplo conceptual de manifiesto:

```yaml
customer: comercio-sur
core_version: v1.4.0
profile: retail
modules:
  - catalog
  - stock
  - pos
  - cash
overrides:
  - reports.daily-margin
```

## Modelo de personalización

Toda solicitud se clasifica antes de programar:

| Tipo | Destino | Ejemplo |
|---|---|---|
| Corrección del núcleo | `inventariosmart-core` | cálculo incorrecto de stock |
| Mejora reutilizable | núcleo o módulo común | lector de balanza configurable |
| Configuración | manifiesto/variables del cliente | colores, ticket, permisos |
| Integración reutilizable | adaptador común | proveedor fiscal o medio de pago |
| Función exclusiva | extensión del repo cliente | reporte contractual específico |

Una función exclusiva debe justificarse. Si puede beneficiar a más comercios, se diseña como capacidad reutilizable y configurable.

## Evitar la divergencia de forks

Un repositorio por cliente mejora el aislamiento, pero aumenta el costo de actualizaciones. Para controlarlo:

- no editar archivos del núcleo cuando exista un punto de extensión;
- mantener módulos y overrides en carpetas explícitas;
- versionar el núcleo con tags y changelog;
- registrar `core_version` en cada repo cliente;
- automatizar la detección de clientes atrasados;
- actualizar primero staging y luego producción;
- exigir pruebas de regresión por perfil;
- no copiar una corrección manualmente en muchos repositorios.

## Estrategia de ramas

Por repositorio cliente:

- `main`: producción protegida;
- `staging`: validación previa del cliente;
- ramas `feature/*` y `fix/*`: trabajo acotado;
- PR obligatoria para llegar a `staging` o `main`;
- releases etiquetadas con versión del cliente y versión del núcleo.

El cliente pide funciones desde el portal de soporte. El pedido se vincula a un ticket y, cuando corresponde, a una PR del repositorio privado. El cliente no necesita operar Git para participar.

## Sincronización desde el núcleo

Proceso recomendado:

1. publicar release estable del núcleo;
2. obtener lista de repositorios compatibles;
3. abrir una actualización automática por cliente;
4. ejecutar migraciones en una base temporal y la suite del perfil;
5. revisar conflictos y overrides afectados;
6. desplegar a staging;
7. validar flujos críticos;
8. promover a producción con rollback disponible.

## Seguridad y propiedad

- repositorios privados con mínimo privilegio;
- acceso del equipo y del cliente definido por contrato;
- secretos únicamente en el proveedor de despliegue;
- datos productivos nunca se copian a issues, logs o repositorios;
- bajas de acceso auditadas;
- dependencias y vulnerabilidades revisadas en todos los repos;
- política explícita de propiedad intelectual para núcleo y funciones exclusivas.

## Métricas operativas

- versión del núcleo por cliente;
- días de atraso respecto de la release estable;
- cantidad de overrides exclusivos;
- conflictos por actualización;
- tiempo de propagación de correcciones críticas;
- costo de mantenimiento por cliente;
- porcentaje de solicitudes convertidas en mejoras reutilizables.

## Riesgo principal

El repositorio separado no reemplaza una arquitectura modular. Sin plantilla, manifiesto, puntos de extensión, CI y sincronización automatizada, cada cliente se convierte en un producto distinto. No se debe incorporar un nuevo cliente hasta poder actualizar los existentes de forma repetible.
