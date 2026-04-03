<?php

return [
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
            'cajas.mostrador.view',
            'categorias.view',
            'productos.view',
        ],
    ],
];
