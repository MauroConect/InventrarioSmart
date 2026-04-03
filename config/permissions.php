<?php

/**
 * Permisos por rol (clave = valor de users.role en minúsculas, salvo alias abajo).
 * admin: * = todo.
 * vendedor: operar caja, vender, consultar catálogo y clientes; ver stock sin ajustes.
 */
return [
    'roles' => [
        'admin' => ['*'],

        'vendedor' => [
            'cajas.view',
            'cajas.manage',
            'ventas.view',
            'ventas.create',
            'clientes.view',
            'categorias.view',
            'productos.view',
            'stock.view',
        ],
    ],

    /** users.role que se tratan como rol lógico "vendedor" para cargar la lista de permisos. */
    'vendedor_role_names' => ['vendedor', 'vendedora', 'cajero', 'cajera', 'mostrador'],
];
