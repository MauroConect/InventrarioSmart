# Onboarding asistido de clientes

## Estado

Decisión vigente. El producto operativo es multi-tenant. El alta no es self-service de módulos: la landing capta y califica al cliente; el equipo analiza la operación, crea el tenant, habilita módulos y, si hace falta, un repositorio privado de features.

## Objetivo

Reducir errores de configuración y evitar que un comercio tenga que traducir sus problemas a módulos técnicos. El cliente describe cómo trabaja; InventarioSmart propone, configura, valida y entrega una solución adecuada.

## Flujo

```mermaid
flowchart LR
    A[Landing] --> B[Registro y email verificado]
    B --> C[Cuestionario del comercio]
    C --> D[Revisión en backoffice]
    D --> E[Reunión de descubrimiento]
    E --> F[Propuesta de configuración]
    F --> G[Tenant multi-tenant + repo de features]
    G --> H[Configuración y migración]
    H --> I[Demo y validación]
    I --> J[Capacitación y entrega]
    J --> K[Soporte y evolución]
```

## Cuestionario inicial

El formulario debe ser corto, reanudable y comprensible. No debe preguntar qué tablas, permisos o componentes necesita.

### Datos básicos

- nombre comercial, localidad y datos de contacto;
- rubro principal y actividades secundarias;
- cantidad de sucursales, cajas y usuarios;
- fecha deseada de puesta en marcha.

### Volumen y operación

- cantidad aproximada de ventas mensuales;
- cantidad aproximada de productos o servicios;
- picos de operación y horarios;
- venta por unidad, peso, variantes o combos;
- uso de scanner, balanza, impresora y otros periféricos;
- manejo de stock, compras y proveedores;
- ventas abiertas, mesas o pedidos pendientes, si aplica.

### Cobros, clientes y fiscalidad

- medios de pago utilizados;
- cuenta corriente, crédito, cheques o cuotas;
- necesidad de identificar clientes;
- facturación electrónica y condición fiscal;
- cantidad de puntos de venta fiscales.

### Situación actual

- sistema, planillas o proceso utilizado hoy;
- datos que deberían migrarse;
- principales problemas y tareas repetitivas;
- reportes indispensables;
- funciones especiales que diferencian al negocio.

## Clasificación interna

El backoffice traduce las respuestas a una ficha técnica:

- perfil base recomendado;
- módulos requeridos;
- integraciones y periféricos;
- complejidad de migración;
- personalizaciones candidatas;
- riesgos, dependencias y preguntas pendientes;
- esfuerzo estimado y responsable interno.

La clasificación automática puede sugerir opciones, pero la decisión de entrega es humana.

## Estados del onboarding

1. `registered`: cuenta creada;
2. `questionnaire_pending`: cuestionario incompleto;
3. `review_pending`: esperando análisis;
4. `discovery_scheduled`: reunión coordinada;
5. `configuration_proposed`: alcance propuesto;
6. `implementation`: instancia en preparación;
7. `customer_validation`: demo o pruebas del cliente;
8. `training`: capacitación y carga inicial;
9. `active`: sistema entregado;
10. `paused|rejected`: detenido con motivo documentado.

Todo cambio de estado debe registrar fecha, actor, comentario y próxima acción.

## Checklist de entrega

- alcance y módulos aprobados;
- repositorio privado creado desde una versión estable del producto base;
- entorno, base de datos, storage y secretos dedicados;
- branding, usuarios, permisos y dispositivos configurados;
- datos iniciales migrados y reconciliados;
- backup y restauración probados;
- flujos críticos validados con el cliente;
- capacitación realizada;
- canal y tiempos de soporte comunicados;
- responsable y versión de producto registrados en el portal.

## Criterios de éxito

- el cliente nunca queda solo frente a decisiones técnicas;
- ninguna instancia se entrega sin validación de ventas, stock y caja aplicables;
- cada requisito queda clasificado como estándar, configurable o desarrollo;
- el portal permite conocer el próximo paso y el responsable;
- el tiempo entre cuestionario completo y propuesta puede medirse.
