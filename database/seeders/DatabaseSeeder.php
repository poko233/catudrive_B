<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Iniciando siembra del sistema...');

        // =====================================================================
        // 1. EMPRESA BASE (única, id=1)
        // =====================================================================
        DB::table('empresa')->updateOrInsert(
            ['id' => 1],
            [
                'empresa' => 'MetaSoft Bolivia',
                'sigla' => 'MSB',
                'email' => 'contacto@metasoft.bo',
                'telefono' => '59123456',
                'direccion' => 'Av. Principal #100, La Paz',
                'estado' => 'Activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $this->command->info('✅ Empresa OK');

        // =====================================================================
        // 2. ACCIONES RBAC
        //    Deben coincidir con SecurityConfig::actions()
        // =====================================================================
        foreach (['Ver', 'Crear', 'Editar', 'Eliminar'] as $accion) {
            DB::table('accion')->updateOrInsert(
                ['accion' => $accion],
                ['accion' => $accion]
            );
        }
        $accionIds = DB::table('accion')->pluck('id', 'accion');
        $this->command->info('✅ Acciones OK');

        // =====================================================================
        // 3. SUCURSAL CENTRAL (pertenece a empresa 1)
        // =====================================================================
        DB::table('sucursal')->updateOrInsert(
            ['id_empresa' => 1, 'sucursal' => 'Central'],
            [
                'id_empresa' => 1,
                'sucursal' => 'Central',
                'responsable' => 'Administrador',
                'direccion' => 'Av. Principal #100, La Paz',
                'ciudad' => 'La Paz',
                'pais' => 'Bolivia',
                'estado' => 'Activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $idSucursal = DB::table('sucursal')
            ->where('id_empresa', 1)
            ->where('sucursal', 'Central')
            ->value('id');
        $this->command->info('✅ Sucursal Central OK');

        // =====================================================================
        // 4. ROLES
        //    'Superadmin' debe coincidir con config('rbac.super_roles')
        //
        //    NOTA: 'Chofer' es un rol de negocio que NO es super-rol.
        //    Sus permisos se resuelven por formulario_permiso normal.
        // =====================================================================
        $roles = [
            'Superadmin' => 'Acceso total al sistema',
            'Administrador' => 'Acceso administrativo',
            'Usuario' => 'Acceso básico de consulta',
            'Chofer' => 'Chofer con acceso a venta de pasajes y encomiendas',
        ];
        foreach ($roles as $rol => $descripcion) {
            DB::table('rol')->updateOrInsert(
                ['rol' => $rol],
                [
                    'descripcion' => $descripcion,
                    'estado' => 'Activo',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        $rolIds = DB::table('rol')->pluck('id', 'rol');
        $this->command->info('✅ Roles OK (' . count($roles) . ')');

        // =====================================================================
        // 5. MÓDULOS — estructura completa del sistema
        // =====================================================================
        $modulos = [
            'Inicio' => [
                'icono' => 'home',
                'descripcion' => 'Panel principal del sistema',
                'orden' => 1,
            ],
            'Configuracion' => [
                'icono' => 'settings',
                'descripcion' => 'Panel de administración del sistema',
                'orden' => 2,
            ],
            'Recursos Humanos' => [
                'icono' => 'people',
                'descripcion' => 'Se gestionará la información de todos los Usuarios del sistema',
                'orden' => 3,
            ],
            'Choferes' => [
                'icono' => 'id-card',
                'descripcion' => 'Gestión de la información de los choferes',
                'orden' => 4,
            ],
            'Rutas' => [
                'icono' => 'navigate',
                'descripcion' => 'Gestión de Rutas de transporte',
                'orden' => 5,
            ],
            'Vehiculos' => [
                'icono' => 'car',
                'descripcion' => 'Gestión de vehículos',
                'orden' => 6,
            ],
            'Asignaciones' => [
                'icono' => 'document-text',
                'descripcion' => 'Asignación de Vehículos, Choferes.',
                'orden' => 7,
            ],
            'Ventas' => [
                'icono' => 'card',
                'descripcion' => 'Módulo de ventas del sistema',
                'orden' => 8,
            ],
            'Encomiendas' => [
                'icono' => 'cube',
                'descripcion' => null,
                'orden' => 9,
            ],
            'Arqueo' => [
                'icono' => 'calculator',
                'descripcion' => 'Gestión de arqueos de caja, ingresos y egresos',
                'orden' => 10,
            ],
        ];

        foreach ($modulos as $nombre => $data) {
            DB::table('modulo')->updateOrInsert(
                ['modulo' => $nombre],
                [
                    'icono' => $data['icono'],
                    'descripcion' => $data['descripcion'],
                    'orden' => $data['orden'],
                    'estado' => 'Activo',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        $moduloIds = DB::table('modulo')->pluck('id', 'modulo');
        $this->command->info('✅ Módulos OK (' . count($modulos) . ')');

        // =====================================================================
        // 6. FORMULARIOS — estructura completa
        //    Cada entrada declara su módulo padre, del que se deriva
        //    automáticamente el pivot formulario_modulo.
        //
        //    IMPORTANTE:
        //    El orden de este array determina el id autoincremental en
        //    formulario. Los nuevos formularios de reportes van al final
        //    para preservar los ids 1-17 ya existentes en producción.
        // =====================================================================
        $formularios = [
            // ─── Configuración / Núcleo ──────────────────────────────
            'Inicio' => ['ruta' => '/dashboard', 'descripcion' => null, 'modulo' => 'Inicio'],
            'Roles' => ['ruta' => '/roles', 'descripcion' => null, 'modulo' => 'Configuracion'],
            'Modulos' => ['ruta' => '/modulos', 'descripcion' => null, 'modulo' => 'Configuracion'],
            'Formularios' => ['ruta' => '/formularios', 'descripcion' => null, 'modulo' => 'Configuracion'],
            'Permisos' => ['ruta' => '/permisos', 'descripcion' => null, 'modulo' => 'Configuracion'],
            'Empresas' => ['ruta' => '/empresa', 'descripcion' => null, 'modulo' => 'Configuracion'],
            'Sucursales' => ['ruta' => '/sucursales', 'descripcion' => null, 'modulo' => 'Configuracion'],
            'Usuarios' => ['ruta' => '/usuarios', 'descripcion' => null, 'modulo' => 'Configuracion'],
            'Formulario con Modulo' => ['ruta' => '/formulario_modulo', 'descripcion' => null, 'modulo' => 'Configuracion'],
            'Modulo con Rol' => ['ruta' => '/modulo_rol', 'descripcion' => null, 'modulo' => 'Configuracion'],

            // ─── Dominio ─────────────────────────────────────────────
            'Recursos Humanos' => ['ruta' => '/recursoshumanos', 'descripcion' => 'Gestión de Usuarios y su información', 'modulo' => 'Recursos Humanos'],
            'Choferes' => ['ruta' => '/choferes', 'descripcion' => 'Gestión de la información de los Choferes', 'modulo' => 'Choferes'],
            'Rutas' => ['ruta' => '/rutas', 'descripcion' => 'Gestión de las Rutas de transporte', 'modulo' => 'Rutas'],
            'Vehiculos' => ['ruta' => '/vehiculos', 'descripcion' => 'Gestión de vehículos', 'modulo' => 'Vehiculos'],
            'Asignaciones' => ['ruta' => '/asignaciones-vehiculos', 'descripcion' => 'Asignaciones de vehículo, choferes y rutas', 'modulo' => 'Asignaciones'],
            'Pasajes' => ['ruta' => '/venta', 'descripcion' => 'Módulo de ventas de pasajes', 'modulo' => 'Ventas'],
            'Encomiendas' => ['ruta' => '/encomiendas', 'descripcion' => null, 'modulo' => 'Encomiendas'],
            'Reportes Vehi.' => ['ruta' => '/vehiculos-reportes', 'descripcion' => null, 'modulo' => 'Vehiculos'],
            'Reportes Vent.' => ['ruta' => '/ventas-reportes', 'descripcion' => null, 'modulo' => 'Ventas'],
            'Reportes Chof.' => ['ruta' => '/choferes-reportes', 'descripcion' => null, 'modulo' => 'Choferes'],
            'Reportes Asig.' => ['ruta' => '/asignaciones-reportes', 'descripcion' => null, 'modulo' => 'Asignaciones'],
            'Arqueo' => ['ruta' => '/arqueo', 'descripcion' => 'Gestión de arqueos de caja', 'modulo' => 'Arqueo'],
        ];

        foreach ($formularios as $nombre => $data) {
            DB::table('formulario')->updateOrInsert(
                ['formulario' => $nombre],
                [
                    'ruta' => $data['ruta'],
                    'descripcion' => $data['descripcion'],
                    'estado' => 'Activo',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        $formularioIds = DB::table('formulario')->pluck('id', 'formulario')->toArray();
        $this->command->info('✅ Formularios OK (' . count($formularios) . ')');

        // =====================================================================
        // 7. FORMULARIO ↔ MÓDULO
        //    Derivado automáticamente del array $formularios.
        //    Incluye automáticamente los formularios de reportes.
        // =====================================================================
        foreach ($formularios as $nombreForm => $data) {
            $idForm = $formularioIds[$nombreForm] ?? null;
            $idMod = $moduloIds[$data['modulo']] ?? null;

            if (!$idForm || !$idMod) {
                continue;
            }

            DB::table('formulario_modulo')->updateOrInsert(
                ['id_modulo' => $idMod, 'id_formulario' => $idForm],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
        $this->command->info('✅ Formulario ↔ Módulo OK');

        // =====================================================================
        // 8. MÓDULO ↔ ROL
        //
        //    Distribución idéntica al dump de producción:
        //      Superadmin    → los 9 módulos
        //      Administrador → Inicio, Configuracion, Vehiculos
        //      Usuario       → Inicio
        //      Chofer        → Ventas, Encomiendas           (NUEVO)
        // =====================================================================
        $planModuloRol = [
            'Superadmin' => '*',
            'Administrador' => ['Inicio', 'Configuracion', 'Vehiculos', 'Arqueo'],
            'Usuario' => ['Inicio'],
            'Chofer' => ['Ventas', 'Encomiendas'],
        ];

        foreach ($planModuloRol as $nombreRol => $modulosPermitidos) {
            $idRol = $rolIds[$nombreRol] ?? null;
            if (!$idRol) {
                continue;
            }

            DB::table('modulo_rol')->where('id_rol', $idRol)->delete();

            $listaModulos = ($modulosPermitidos === '*')
                ? array_keys($modulos)
                : $modulosPermitidos;

            $filas = [];
            foreach ($listaModulos as $nombreMod) {
                $idMod = $moduloIds[$nombreMod] ?? null;
                if (!$idMod) {
                    continue;
                }
                $filas[] = [
                    'id_rol' => $idRol,
                    'id_modulo' => $idMod,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($filas !== []) {
                DB::table('modulo_rol')->insert($filas);
            }
        }
        $this->command->info('✅ Módulo ↔ Rol OK');

        // =====================================================================
        // 9. PERMISOS (formulario_permiso)
        //
        //    Distribución idéntica al dump de producción:
        //      Superadmin    → TODOS los formularios × 4 acciones
        //                      19 formularios × 4 = 76
        //      Administrador → Inicio + Configuracion + Vehiculos × 4
        //                      (Inicio 1 + Configuracion 9 + Vehiculos 2) × 4 = 48
        //      Usuario       → Inicio × [Ver]                        =  1
        //      Chofer        → Ventas + Encomiendas × 4             = 12
        //                                                             ────
        //                                                             145
        //
        //    Los formularios 'Reportes' y 'Reportes V.' se incluyen
        //    automáticamente al filtrar por nombre de módulo padre,
        //    así que no hay que listarlos explícitamente.
        // =====================================================================
        $planPermisos = [
            'Superadmin' => [
                'modulos' => '*',
                'acciones' => ['Ver', 'Crear', 'Editar', 'Eliminar'],
            ],
            'Administrador' => [
                'modulos' => ['Inicio', 'Configuracion', 'Vehiculos', 'Arqueo'],
                'acciones' => ['Ver', 'Crear', 'Editar', 'Eliminar'],
            ],
            'Usuario' => [
                'modulos' => ['Inicio'],
                'acciones' => ['Ver'],
            ],
            'Chofer' => [
                'modulos' => ['Ventas', 'Encomiendas'],
                'acciones' => ['Ver', 'Crear', 'Editar', 'Eliminar'],
            ],
        ];

        foreach ($planPermisos as $nombreRol => $plan) {
            $idRol = $rolIds[$nombreRol] ?? null;
            if (!$idRol) {
                continue;
            }

            DB::table('formulario_permiso')->where('id_rol', $idRol)->delete();

            $filas = [];

            foreach ($formularios as $nombreForm => $data) {
                $nombreMod = $data['modulo'];

                if (
                    $plan['modulos'] !== '*' &&
                    !in_array($nombreMod, $plan['modulos'], true)
                ) {
                    continue;
                }

                $idForm = $formularioIds[$nombreForm] ?? null;
                $idMod = $moduloIds[$nombreMod] ?? null;

                if (!$idForm || !$idMod) {
                    continue;
                }

                foreach ($plan['acciones'] as $nombreAccion) {
                    $idAccion = $accionIds[$nombreAccion] ?? null;
                    if (!$idAccion) {
                        continue;
                    }

                    $filas[] = [
                        'id_rol' => $idRol,
                        'id_modulo' => $idMod,
                        'id_formulario' => $idForm,
                        'id_accion' => $idAccion,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if ($filas !== []) {
                DB::table('formulario_permiso')->insert($filas);
            }

            $this->command->info("✅ {$nombreRol}: " . count($filas) . ' permisos');
        }

        // =====================================================================
        // 10. SELECTORES OCULTOS (formulario_accion)
        //
        //     Reglas de visibilidad UI por rol.
        //
        //     El frontend consulta esta tabla para decidir qué elementos
        //     HTML debe OCULTAR/deshabilitar para cada rol, incluso si el
        //     rol tiene permiso 'Ver' sobre el formulario.
        //
        //     Estado actual en producción:
        //       Chofer → Pasajes (.pasajes-resumen, .pasajes-crear)
        //                ambos OCULTOS (habilitado = false)
        // =====================================================================
        $idRolChofer = $rolIds['Chofer'] ?? null;
        $idFormPasajes = $formularioIds['Pasajes'] ?? null;

        if ($idRolChofer && $idFormPasajes) {
            /*
             * Limpiamos primero las reglas del rol para ese formulario
             * para evitar duplicados en corridas repetidas del seeder.
             * La tabla no tiene UNIQUE en (id_rol, id_formulario, selector_html),
             * así que el updateOrInsert no aplica aquí.
             */
            DB::table('formulario_accion')
                ->where('id_rol', $idRolChofer)
                ->where('id_formulario', $idFormPasajes)
                ->delete();

            DB::table('formulario_accion')->insert([
                [
                    'id_rol' => $idRolChofer,
                    'id_formulario' => $idFormPasajes,
                    'selector_html' => '.pasajes-resumen',
                    'habilitado' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id_rol' => $idRolChofer,
                    'id_formulario' => $idFormPasajes,
                    'selector_html' => '.pasajes-crear',
                    'habilitado' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $this->command->info('✅ Selectores ocultos Chofer/Pasajes OK (2)');
        } else {
            $this->command->warn('⚠️  No se pudieron sembrar selectores ocultos (Chofer o Pasajes no encontrado).');
        }

        // =====================================================================
        // 11. CATEGORÍAS DE VEHÍCULO
        // =====================================================================
        $categoriasVehiculo = [
            'Bus',
            'Minibús',
            'Surubí',
        ];

        foreach ($categoriasVehiculo as $categoria) {
            DB::table('categoria_vehiculo')->updateOrInsert(
                ['categoria' => $categoria],
                [
                    'categoria' => $categoria,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info(
            '✅ Categorías de vehículo OK (' . count($categoriasVehiculo) . ')'
        );

        // =====================================================================
        // 12. USUARIO ADMINISTRADOR
        // =====================================================================
        $idUser = DB::table('user')->where('usuario', 'admin')->value('id');

        if (!$idUser) {
            $idUser = DB::table('user')->insertGetId([
                'usuario' => 'admin',
                'password' => Hash::make('admin123'),
                'ci' => '00000001',
                'nombres' => 'Administrador',
                'primer_apellido' => 'Sistema',
                'genero' => 'Masculino',
                'expedido' => 'CBBA',
                'estado' => 'Activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("✅ Usuario 'admin' creado (id={$idUser})");
        } else {
            $this->command->info("ℹ️  Usuario 'admin' ya existe (id={$idUser})");
        }

        // Sucursal obligatoria para middleware CheckSucursal
        if ($idSucursal) {
            DB::table('user_sucursal')->updateOrInsert(
                ['id_user' => $idUser, 'id_sucursal' => $idSucursal],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Rol Superadmin
        $idSuperadmin = $rolIds['Superadmin'] ?? null;
        if ($idSuperadmin) {
            DB::table('user_rol')->updateOrInsert(
                ['id_user' => $idUser, 'id_rol' => $idSuperadmin],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        $this->command->newLine();
        $this->command->info('══════════════════════════════');
        $this->command->info('  usuario:  admin');
        $this->command->info('  password: admin123');
        $this->command->info('  empresa:  MetaSoft Bolivia');
        $this->command->info('  rol:      Superadmin');
        $this->command->info('══════════════════════════════');
        $this->command->info('🌱 Siembra completada exitosamente.');
    }
}