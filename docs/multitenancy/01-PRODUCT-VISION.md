# Visión de producto

InventarioSmart organiza catálogo, stock, ventas de mostrador y caja para varios comercios en una aplicación. Cada comercio conserva su identidad, personas autorizadas, módulos opcionales y datos operativos.

La configuración se acompaña desde onboarding: cuestionario, discovery, propuesta, implementación, validación y capacitación. El cliente puede seguir su caso y solicitar soporte. Las extensiones específicas se relevan antes de desarrollarlas; no se mezclan ramas completas de variantes históricas.

Blade + Alpine es la interfaz canónica. La landing pública muestra personas trabajando, capturas con datos de ejemplo y combinaciones configurables; no incluye un destino comercial o ingreso público mientras Mauro no lo defina.

El producto actual sigue la [ADR de MySQL compartida](14-ADR-MULTITENANT-COMPARTIDO.md). Repositorios privados por cliente, hosting dedicado y propagación automática de upgrades son alternativas futuras, no capacidades entregadas.
