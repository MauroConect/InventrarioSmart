<?php

/**
 * Permisos por rol (clave = valor de users.role en minúsculas, salvo alias abajo).
 * admin: * = todo.
 * vendedor: operar caja, vender, consultar catálogo y clientes; ver stock sin ajustes.
 */
return [
    'roles' => [
        'owner' => ['*'],
        'admin' => ['*'],

        'stock' => ['categorias.view', 'categorias.manage', 'productos.view', 'productos.manage', 'proveedores.view', 'proveedores.manage', 'stock.view', 'stock.manage', 'support.use'],
        'finanzas' => ['clientes.view', 'cajas.view', 'movimientos_caja.view', 'movimientos_caja.manage', 'ventas.view', 'ventas.facturar', 'cuentas_corrientes.view', 'cuentas_corrientes.manage', 'deudas.view', 'deudas.manage', 'cheques.view', 'cheques.manage', 'support.use'],
        'auditor' => ['dashboard.view', 'audit.view', 'categorias.view', 'productos.view', 'proveedores.view', 'stock.view', 'clientes.view', 'cajas.view', 'movimientos_caja.view', 'ventas.view', 'cuentas_corrientes.view', 'deudas.view', 'cheques.view', 'turnos.view'],

        'vendedor' => [
            'cajas.view',
            'cajas.manage',
            'ventas.view',
            'ventas.create',
            'clientes.view',
            'categorias.view',
            'productos.view',
            'stock.view',
            'support.use',
            'turnos.view',
            'turnos.manage',
        ],
    ],

    /** users.role que se tratan como rol lógico "vendedor" para cargar la lista de permisos. */
    'vendedor_role_names' => ['vendedor', 'vendedora', 'cajero', 'cajera', 'mostrador'],
];
