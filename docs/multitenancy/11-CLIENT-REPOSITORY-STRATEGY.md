# Versiones del producto y metadatos de entrega

El nombre histórico del archivo se conserva para enlaces existentes. La entrega vigente usa un repositorio compartido de InventarioSmart y una rama de integración. Un tenant representa un comercio dentro del mismo producto, no un repositorio generado ni una base dedicada.

core_version, environment_url, repository_url y delivery_notes son metadatos de entrega. No prueban existencia de infraestructura y no almacenan secretos. ISM-27 define manifiesto, versión y provisioning repetible compatible con la [ADR compartida](14-ADR-MULTITENANT-COMPARTIDO.md).

Las mejoras comunes se integran al núcleo; extensiones por cliente requieren alcance y aislamiento explícitos. Un futuro modelo de forks o despliegues dedicados debe plantearse en una ADR nueva; la propuesta anterior está archivada en history/2026-08-23.
