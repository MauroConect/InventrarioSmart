# Plan de accion - Facturacion AFIP/ARCA

## Objetivo
Integrar facturacion electronica AFIP/ARCA en el sistema, permitiendo seleccionar ventas a facturar y operar de forma parametrizable para distintos comercios.

## Principios
- Sin datos hardcodeados de fiscalizacion.
- Configuracion editable desde la app por usuario administrador.
- Trazabilidad completa de cada intento de facturacion.
- Evolucion por etapas con checkpoints.

## Etapas y checkpoints

### Etapa 1 - Base parametrizable (checkpoint 1)
- [x] Crear plan de implementacion.
- [x] Crear estructura de configuracion fiscal editable (CUIT emisor, punto de venta, condicion IVA, ambiente, paths de certificado y clave).
- [x] Crear pantalla de configuracion fiscal (solo admin).
- [x] Agregar campos de estado de facturacion en ventas.

### Etapa 2 - Motor AFIP/ARCA (homologacion)
- [x] Implementar servicio WSAA (via SDK AFIP).
- [x] Implementar servicio WSFEv1 (CAE).
- [x] Guardar request/response y errores (respuesta y observaciones en venta).
- [x] Endpoint para facturar una venta.

### Etapa 3 - UI operativa de facturacion
- [x] Listado de ventas pendientes de facturar.
- [x] Accion "Facturar" individual y por lote.
- [x] Visualizacion de estado: pendiente, facturada, error.
- [x] Reintento manual de facturacion.

### Etapa 4 - Comprobantes y cierre operativo
- [x] Vista de comprobante fiscal en detalle de venta.
- [x] Impresion/PDF con CAE y vencimiento.
- [x] Filtros y reportes de facturacion.

### Etapa 5 - Produccion y hardening
- [x] Soporte homologacion/produccion por parametro.
- [x] Validaciones fiscales robustas por tipo de comprobante.
- [x] Manejo seguro de secretos/certificados.
- [x] Documentacion de onboarding para nuevos comercios.

## Parametros que se podran editar
- CUIT emisor.
- Punto de venta AFIP.
- Condicion IVA del emisor.
- Ambiente (homologacion/produccion).
- Tipo de comprobante por defecto (A/B/C).
- Razon social y domicilio comercial.
- Path de certificado y clave privada.

## Notas de negocio
- Se comienza con factura B para salida rapida (configurable).
- Se contempla evolucion a A/C y notas de credito/debito.
- El modulo sera multi-comercio via parametrizacion por instancia.
