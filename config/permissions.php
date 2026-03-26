<?php

return [
    'roles' => [
        'admin' => ['*'],
        'vendedor' => [
            'dashboard.view',
            'ventas.view',
            'ventas.create',
            'clientes.view',
            'productos.view',
            'cajas.view',
        ],
    ],
];
