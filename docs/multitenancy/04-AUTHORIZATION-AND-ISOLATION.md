# Autorización y aislamiento vigentes

Una identidad global sólo opera en un comercio con membresía activa y tenant activo. El rol se toma de tenant_user, no del role global cuando hay contexto. Un header o ID suministrado por el cliente nunca concede acceso. El contexto se resuelve antes de bindings y se limpia también ante errores.

| Rol | Alcance operativo |
|---|---|
| owner / admin | Administración del comercio; no de otros tenants |
| vendedor y aliases | Mostrador, caja, ventas, catálogo/stock de consulta y turnos habilitados |
| stock | Catálogo/proveedores y movimientos de stock; sin finanzas ni usuarios |
| finanzas | Consulta de clientes/ventas/caja, finanzas y facturación habilitada; sin catálogo ni usuarios |
| auditor | Consulta operativa, reportes y timeline habilitado; sin escrituras |
| superadmin plataforma | Backoffice; requiere membresía explícita para operaciones comerciales |

La matriz ejecutable es config/permissions.php. El módulo habilitado y el permiso son requisitos independientes. La navegación acompaña permisos; ocultar botones no sustituye controles en API.

Las validaciones exists y unique de negocio se restringen por tenant. Los modelos verifican referencias y la membresía de operadores. El bypass de scopes se usa sólo en plataforma/migraciones explícitas. Las pruebas intentan IDs y relaciones cruzadas, cambios de rol entre dos membresías y acceso de superadmin sin pertenencia.

No se promete aislamiento físico. Adjuntos, credenciales, cache/jobs y consultas raw requieren controles propios además del scope. [ADR y límites](14-ADR-MULTITENANT-COMPARTIDO.md).
