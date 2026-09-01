<?php

return [
    /*
     * Roles con bypass total del CheckPermission middleware.
     * No se consulta formulario_permiso — acceso completo a todos los endpoints.
     * El nombre debe coincidir EXACTAMENTE con la columna rol.rol en BD.
     */
    'super_roles' => [
        'Administrador',
        'Superadmin',
    ],
];
