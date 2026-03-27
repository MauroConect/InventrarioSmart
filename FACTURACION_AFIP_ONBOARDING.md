# Onboarding facturacion AFIP/ARCA (nuevo comercio)

## 1. Requisitos previos

- CUIT del comercio habilitado en AFIP.
- Punto de venta asignado para factura electronica (WSFE).
- Certificado digital y clave privada para WSAA (mismo que usa ARCA/AFIP web).
- Definir si operan en **homologacion** (pruebas) o **produccion**.

## 2. Instalacion de certificados en el servidor

1. Copie el certificado (`.crt` / `.pem`) y la clave (`.key`) a una carpeta accesible por el contenedor PHP, por ejemplo:
   - `storage/app/certs/certificado.crt`
   - `storage/app/certs/clave.key`
2. En el panel **Configuracion Fiscal** indique la ruta **absoluta** dentro del contenedor (ej. `/var/www/storage/app/certs/...` si el proyecto monta `storage` en Docker).
3. Si la clave tiene passphrase, carguela solo en el campo correspondiente; **no se devuelve** en las respuestas API por seguridad.

## 3. Parametros en la aplicacion

| Campo | Uso |
|--------|-----|
| Razon social | Texto en comprobante impreso |
| CUIT emisor | 11 digitos, validado con digito verificador |
| Condicion IVA | Debe ser coherente con el tipo de comprobante (ver abajo) |
| Punto de venta | Numero de AFIP |
| Ambiente | homologacion / produccion |
| Comprobante por defecto | A, B o C |

### Coherencia emisor / comprobante

- **Factura A**: solo para emisor **Responsable Inscripto** y cliente con **CUIT** valido (11 digitos) cargado en el cliente.
- **Factura B / C**: monotributo o exento segun corresponda; cliente consumidor final sin CUIT usa DNI o documento generico.

## 4. Flujo de prueba recomendado

1. Dejar **homologacion** activado.
2. Emitir ventas de prueba y facturar desde **Facturacion** o el detalle de venta.
3. Revisar CAE y vencimiento en el detalle.
4. Pasar a **produccion** solo cuando la configuracion fiscal este validada.

## 5. Migraciones y despliegue

Tras actualizar codigo:

```bash
./deploy-produccion.sh
```

Esto aplica migraciones (incluye `cuit` en clientes si aplica).

## 6. Soporte

- Errores en facturacion quedan en `venta.afip_observaciones` y en logs (`storage/logs/laravel.log`) con prefijo `AFIP facturacion`.
