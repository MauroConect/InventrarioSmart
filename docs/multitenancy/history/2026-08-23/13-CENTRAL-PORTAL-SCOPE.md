# Alcance del portal central

## Propósito

El portal central organiza la relación entre InventarioSmart y sus clientes. No reemplaza la aplicación comercial ni ejecuta ventas, movimientos de stock, cajas o facturación.

## Roles

- **Contacto cliente:** completa información, consulta avances y participa en soporte.
- **Consultor de onboarding:** revisa respuestas, releva procesos y propone configuración.
- **Implementador:** prepara repositorio, entorno, módulos, migración y validación.
- **Soporte:** responde conversaciones y clasifica solicitudes.
- **Administrador:** gestiona accesos internos, catálogo, estados y auditoría.

## Secciones públicas

- landing;
- propuesta de valor y casos de uso;
- registro e ingreso;
- recuperación y verificación de email;
- contacto y política de privacidad.

## Área del cliente

- cuestionario reanudable;
- estado del onboarding y próximo paso;
- datos de contacto y responsables;
- propuesta/resumen de configuración;
- documentos y checklist compartido;
- canal de soporte;
- solicitudes de funciones y bugs;
- historial de respuestas y cambios de estado;
- información visible de versión y ventanas de mantenimiento.

El cliente no ve credenciales, secretos, repositorios de otros clientes ni controles internos de esfuerzo/costo.

## Backoffice

- bandeja de registros y cuestionarios pendientes;
- ficha 360° del cliente;
- respuestas, notas internas y archivos;
- pipeline y responsables;
- agenda de discovery, demo y capacitación;
- configuración propuesta: perfil, módulos, integraciones y overrides;
- riesgos, estimación y aprobaciones;
- repositorio, entornos, versión, despliegues y backups;
- inbox de soporte y solicitudes;
- auditoría de mutaciones.

## Entidades conceptuales

- `Account`: comercio cliente;
- `Contact`: persona vinculada al comercio;
- `Questionnaire` y `QuestionnaireAnswer`;
- `OnboardingCase`, `OnboardingStage` y `Task`;
- `ConfigurationProposal`;
- `Delivery`: repositorio, entornos y versión, sin secretos;
- `SupportConversation`, `SupportMessage` y `FeatureRequest`;
- `AuditEvent`.

Los nombres son conceptuales; no autorizan crear migraciones hasta revisar el modelo.

## Reglas

- una cuenta puede tener varios contactos;
- todo caso tiene responsable y próxima acción;
- las notas internas nunca se muestran al cliente;
- todo cambio sensible queda auditado;
- URLs de repositorios privados requieren permiso interno;
- secretos se referencian por identificador del vault, nunca por valor;
- soporte puede vincular una solicitud con un cliente, delivery, versión y módulo;
- la IA puede resumir o clasificar, pero no aprobar alcance ni ejecutar cambios.

## MVP documental

1. landing y registro;
2. cuestionario;
3. backoffice de revisión;
4. pipeline de onboarding;
5. soporte y solicitudes;
6. registro manual de repo, entorno y versión;
7. auditoría.

Quedan fuera del primer MVP: pagos, creación automática de repositorios, despliegue automático, acceso remoto a instancias, impersonación, telemetría operativa y actualización masiva.

## Criterios para autorizar implementación

- cuestionario probado con al menos un comercio real;
- estados y responsables aprobados;
- política de datos y privacidad definida;
- clasificación de solicitudes acordada;
- checklist de entrega validado;
- decisión explícita sobre qué cambios experimentales de código deben descartarse;
- wireframes o criterios de pantalla revisados antes de crear controladores y migraciones.
