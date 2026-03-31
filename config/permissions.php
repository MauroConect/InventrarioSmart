<?php

return [
    'roles' => [
        'admin' => ['*'],
        'vendedor' => [
            'ventas.view',
            'ventas.create',
            'cajas.view',
            'cajas.manage',
            // Permisos de soporte para poder operar una venta
            'clientes.view',
            'productos.view',
        ],
    ],
];
