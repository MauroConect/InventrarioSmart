# Contrato de venta, stock, caja e idempotencia

## Consistencia

Una venta cerrada valida referencias por tenant, disponibilidad y tipo de cantidades antes de confirmar stock, ítems, cobro y movimientos auditables. El precio proviene del producto, no del cliente. Las cantidades por unidad son enteras; los pesables requieren módulo habilitado y precisión de tres decimales. Descuentos mayores que el total se rechazan.

Las comandas reservan stock al agregar ítems. Cerrar cobra sin descontarlo otra vez; anular una comanda abierta devuelve existencias una sola vez y deja movimientos inversos. No se implementó devolución de una venta cerrada/facturada como si fuera anulación de comanda.

Caja, venta, productos (en orden de ID) y cuentas de crédito se bloquean dentro de transacciones. Abrir/agregar/cerrar/anular una comanda se coordina con el cierre de caja. No se admiten movimientos nuevos sobre una caja cerrada. La numeración interna FAC/CMD usa contadores bloqueados por tenant/serie, inicializados desde los valores legacy; no es numeración fiscal.

## Efectivo físico

El esperado del cajón es apertura + efectivo de ventas cerradas + componente efectivo del pago mixto + ingresos - egresos. Tarjetas, transferencias y cuenta corriente se reportan aparte. Comandas abiertas y anuladas no integran cobros cerrados. Ejemplo: apertura 50 y venta mixta de 225 (50 efectivo, 75 tarjeta, 100 transferencia) producen un esperado físico de 100.

## Reintentos

POST/PUT/PATCH/DELETE operativos de ventas, caja y movimientos pueden enviar `Idempotency-Key` (8–128 caracteres). Se limita por tenant/actor, compara HMAC de método/ruta/payload (incluyendo hash de archivos), persiste respuesta exitosa en la misma transacción y reutiliza esa respuesta al repetir el mismo intento. Una clave con otro contenido produce 409. Los permisos, módulo y membresía se comprueban también al reintentar; una respuesta cacheada no restituye un permiso revocado.

El POS conserva la clave al perder una respuesta y la renueva al confirmar la venta o ante un rechazo definitivo. Los adjuntos del POS se envían dentro del alta, no en un segundo POST silencioso. Comandas conservan claves por operación mientras se resuelve el intento. Clientes API que omitan clave mantienen compatibilidad, pero no obtienen deduplicación de altas: deben implementarla al integrar.

Cerrar/anular una venta o cerrar caja ya resueltos devuelve el estado existente, sin repetir stock/deuda/cierre. La emisión fiscal se excluye de la transacción HTTP genérica: necesita su propia reconciliación durable y homologación; no se considera cubierta por una clave de POS.

Las respuestas de tenant_operations son datos privados de negocio y requieren la misma protección y backup que las ventas. Las claves de cache/locks del harness se guardan en un directorio separado para testing. No purgar claves de operaciones dentro de la ventana de reintentos de los clientes; una política de retención productiva requiere definir esa ventana.

## Historial de operadores

Revocar una membresía la marca inactive y retira acceso; conserva referencias históricas. Una relación de operador nueva/cambiada exige miembro activo, mientras una referencia histórica sin cambios admite la membresía conservada. Un admin de un comercio no puede cambiar credenciales globales de otra identidad compartida entre comercios; sólo su rol en ese tenant.

## Ensayo concurrente

Dos procesos PHP independientes inicializan kernels HTTP con autenticación de fixtures contra MySQL testing. Barreras de arranque y un lock de caja controlado aseguran solapamiento temporal, que se comprueba con timestamps. Se prueban reintentos de alta, competencia por stock y cierres por dos operadores. Se usa exclusivamente testing, sin datos reales y sin afirmar que equivale a carga Nginx o homologación fiscal.
