<?php

return [
    'roles' => [
        // Incluye categorias.manage, productos.manage, etc. (vía isAdmin en User::hasPermission).
        'admin' => ['*'],
        // Solo operación de mostrador: caja, venta de helado, consulta de sabores (categorías) y productos (solo lectura en UI).
        'vendedor' => [
            'ventas.view',
            'ventas.create',
            'cajas.view',
            'cajas.manage',
            'categorias.view',
            'productos.view',
        ],
    ],
];
