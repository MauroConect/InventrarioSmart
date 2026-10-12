<?php

return [
    'max_fields_per_form' => 25,

    'types' => [
        'text' => 'Texto',
        'number' => 'Numérico',
        'textarea' => 'Texto largo',
        'date' => 'Fecha',
        'checkbox' => 'Sí / No',
    ],

    /*
     * Formularios que aceptan inputs extra. No hace falta una migración
     * por cada campo nuevo: se guardan en custom_form_fields / custom_form_values.
     */
    'forms' => [
        'turnos' => [
            'module' => 'services.appointments',
            'entity' => App\Models\Turno::class,
            'label' => 'Turnos',
            'view_permission' => 'turnos.view',
            'manage_permission' => 'turnos.manage',
        ],
        'productos' => [
            'module' => 'core.catalog',
            'entity' => App\Models\Producto::class,
            'label' => 'Productos',
            'view_permission' => 'productos.view',
            'manage_permission' => 'productos.manage',
        ],
        'clientes' => [
            'module' => null,
            'entity' => App\Models\Cliente::class,
            'label' => 'Clientes',
            'view_permission' => 'clientes.view',
            'manage_permission' => 'clientes.manage',
        ],
    ],
];
