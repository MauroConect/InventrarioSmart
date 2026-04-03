<?php

return [
    // true = volver al modelo antiguo (solo permisos del rol en config).
    'enforce_role_permissions' => env('ENFORCE_ROLE_PERMISSIONS', false),

    /** Sinónimos de rol en BD que cuentan como vendedor/mostrador para el menú. */
    'vendedor_role_aliases' => array_filter(array_map('trim', explode(',', (string) env('VENDEDOR_ROLE_ALIASES', '')))),

    'roles' => [
        // Incluye categorias.manage, productos.manage, etc. (vía isAdmin en User::hasPermission).
        'admin' => ['*'],
        // Solo operación de mostrador: caja, venta de helado, consulta de sabores (categorías) y productos (solo lectura en UI).
        'vendedor' => [
            'ventas.view',
            'ventas.create',
            'clientes.view',
            'cajas.view',
            'cajas.manage',
            'categorias.view',
            'productos.view',
        ],
    ],
];
