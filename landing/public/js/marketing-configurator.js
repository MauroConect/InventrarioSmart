function moduleConfigurator(modules) {
    const profiles = [
        { key: 'retail', name: 'Almacén y retail', image: 'comercio-almacen.webp', title: 'Un mostrador con todo a mano.', description: 'Ventas, existencias y caja en una misma rutina.', modules: ['catalog.scanner'] },
        { key: 'gastronomia', name: 'Heladería y gastronomía', image: 'comercio-heladeria.webp', title: 'Cada pedido sigue su recorrido.', description: 'Productos por peso y comandas que acompañan la atención.', modules: ['catalog.weighted', 'sales.open_orders'] },
        { key: 'servicios', name: 'Servicios y turnos', image: 'comercio-servicios.webp', title: 'Más tiempo para atender bien.', description: 'Una agenda organizada para acompañar a cada cliente.', modules: ['services.appointments'] },
    ];
    const available = new Set(modules.map(module => module.key));
    return {
        modules,
        profiles,
        activeProfile: 'retail',
        selected: profiles[0].modules.filter(key => available.has(key)),
        get currentProfile() { return profiles.find(profile => profile.key === this.activeProfile); },
        get profileImage() { return '/images/marketing/' + this.currentProfile.image; },
        get profileTitle() { return this.currentProfile.title; },
        get profileDescription() { return this.currentProfile.description; },
        get selectedModules() { return this.modules.filter(module => this.isEnabled(module.key)); },
        isEnabled(key) { return this.selected.includes(key); },
        chooseProfile(key) {
            const profile = this.profiles.find(profile => profile.key === key);
            if (!profile) return;
            this.activeProfile = key;
            this.selected = profile.modules.filter(moduleKey => available.has(moduleKey));
        },
        toggleModule(key) {
            if (!available.has(key)) return;
            this.selected = this.isEnabled(key) ? this.selected.filter(selectedKey => selectedKey !== key) : [...this.selected, key];
        },
    };
}
