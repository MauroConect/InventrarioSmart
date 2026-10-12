# Fundación implementada y límites

La fundación incluye Tenant/TenantUser, resolución de comercio, permisos por membresía, selector, scopes, onboarding asistido, módulos por tenant, branding, soporte y backoffice. Las tablas operativas ya usan tenant_id; no se limita al plano de plataforma.

La arquitectura es [MySQL compartida](14-ADR-MULTITENANT-COMPARTIDO.md). Las brechas restantes tienen tickets ISM específicos y pruebas; la existencia de fundación no certifica adjuntos, fiscalidad, procesos asíncronos o concurrencia hasta validar cada entrega.

No crea infraestructura ni repositorios por cliente. El backend corre local; la landing estática pública es una entrega separada.
