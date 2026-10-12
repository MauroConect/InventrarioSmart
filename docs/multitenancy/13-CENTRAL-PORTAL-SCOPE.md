# Portal y backoffice vigentes

El portal administra registro, verificación y recuperación de acceso, cuestionario, onboarding asistido y soporte. El backoffice requiere superadmin de plataforma y conserva eventos, responsables, notas y metadatos de entrega.

El cliente consulta sólo su propio caso y documentos visibles; no recibe notas internas, secretos ni datos de otros comercios. La aplicación comercial está en rutas operativas con tenant/membresía/permiso/módulo, aunque portal e inventario compartan Laravel y MySQL.

Los estados del caso se alinean con configuración, validación, capacitación y entrega. ISM-19 agrega documentos/checklist/aprobaciones trazables. Los registros técnicos de versión son metadatos y no generan repositorios, pipelines o hosting.

La landing Vercel explora el producto; no expone registro/ingreso/contacto público hasta definir sus destinos. [ADR](14-ADR-MULTITENANT-COMPARTIDO.md), [onboarding](10-ASSISTED-ONBOARDING.md) y [roadmap](08-DEVELOPMENT-ROADMAP.md).
