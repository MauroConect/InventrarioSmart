# ADR: portal central e instancia dedicada por cliente

- **Estado:** aceptada
- **Fecha:** 2026-08-23
- **Complementa:** el producto operativo sigue siendo multi-tenant (MySQL compartida + `tenant_id`). El portal es de captación asistida y cada cliente puede tener un repositorio privado de features.

## Contexto

InventarioSmart atiende comercios con procesos y pedidos de evolución diferentes. La prioridad actual es acompañar el descubrimiento, configurar cada entrega y permitir personalizaciones controladas, no ofrecer autogestión técnica ni activación inmediata.

Se evaluaron dos modelos:

1. aplicación operativa multitenant compartida;
2. portal central de clientes más aplicación, datos y repositorio dedicados por comercio.

## Decisión

Adoptar el segundo modelo.

El portal central incluye:

- landing y registro;
- verificación de identidad;
- cuestionario comercial;
- seguimiento del onboarding;
- backoffice interno;
- soporte, solicitudes y estado de entregas;
- referencia al repositorio, entorno y versión de cada cliente.

Cada aplicación operativa incluye:

- un solo comercio;
- repositorio privado;
- despliegue independiente;
- base de datos y storage dedicados;
- secretos y credenciales fiscales independientes;
- módulos y extensiones elegidos durante el onboarding asistido.

## Motivos

- aislamiento físico y operativo más simple de explicar y verificar;
- libertad para entregar integraciones o funciones específicas;
- despliegues y ventanas de actualización por cliente;
- menor riesgo de que una personalización afecte a todos;
- soporte alineado con una etapa de producto donde el acompañamiento humano aporta valor.

## Consecuencias positivas

- una falla de datos o despliegue queda contenida;
- backups, restauraciones y residencia pueden administrarse por cliente;
- cada solicitud puede vincularse a su versión y repositorio;
- los clientes pueden avanzar a ritmos diferentes;
- el portal no necesita acceder a datos transaccionales.

## Costos y riesgos

- más repositorios, pipelines, entornos y bases para operar;
- correcciones críticas deben propagarse a varias instancias;
- riesgo alto de divergencia entre clientes;
- mayor costo de observabilidad y actualización;
- reportes globales requieren metadatos agregados, no consultas directas a todas las bases.

Estos costos se aceptan sólo con producto base canónico, manifiesto por cliente, automatización de creación, CI común y proceso de actualización.

## Límites

- un repositorio por cliente no autoriza copiar y modificar libremente el núcleo;
- el portal no es una puerta administrativa a las bases operativas;
- soporte no implica impersonación silenciosa;
- datos de clientes no se usan para entrenar modelos ni se envían a servicios externos sin autorización;
- una personalización no se desarrolla hasta clasificar si debe ser configuración, módulo común o extensión exclusiva.

## Implicaciones sobre documentación previa

Los documentos de `tenant_id`, `TenantContext`, membresías globales y base compartida quedan como análisis histórico de una alternativa descartada para la aplicación operativa. No deben guiar nuevas implementaciones mientras esta ADR esté vigente.

El término “cliente” en el portal identifica una cuenta comercial. Dentro de la aplicación entregada no existe selección de tenant: toda la instancia pertenece a un único comercio.

## Condiciones para revisar la decisión

Reevaluar sólo si:

- la cantidad de clientes vuelve inmanejable la operación por instancia;
- la mayoría usa el mismo producto sin overrides;
- la automatización de updates demuestra ser insuficiente;
- costos medidos de infraestructura superan claramente el beneficio;
- existe presupuesto para una migración multitenant segura y pruebas de aislamiento completas.

Una revisión futura no debe convertir repositorios existentes ni mezclar bases sin un plan explícito de migración y rollback.
