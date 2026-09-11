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
        // =====================================================================
        $roles = [
            'Superadmin' => 'Acceso total al sistema',
            'Administrador' => 'Acceso administrativo',
            'Usuario' => 'Acceso básico de consulta',
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
        $this->command->info('✅ Roles OK');

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
            'Form -> Módulo' => ['ruta' => '/formulario_modulo', 'descripcion' => null, 'modulo' => 'Configuracion'],
            'Módulo -> Rol' => ['ruta' => '/modulo_rol', 'descripcion' => null, 'modulo' => 'Configuracion'],

            // ─── Dominio ─────────────────────────────────────────────
            'Recursos Humanos' => ['ruta' => '/recursoshumanos', 'descripcion' => 'Gestión de Usuarios y su información', 'modulo' => 'Recursos Humanos'],
            'Choferes' => ['ruta' => '/choferes', 'descripcion' => 'Gestión de la información de los Choferes', 'modulo' => 'Choferes'],
            'Rutas' => ['ruta' => '/rutas', 'descripcion' => 'Gestión de las Rutas de transporte', 'modulo' => 'Rutas'],
            'Vehiculos' => ['ruta' => '/vehiculos', 'descripcion' => 'Gestión de vehículos', 'modulo' => 'Vehiculos'],
            'Asignaciones' => ['ruta' => '/asignaciones-vehiculos', 'descripcion' => 'Asignaciones de vehículo, choferes y rutas', 'modulo' => 'Asignaciones'],
            'Pasajes' => ['ruta' => '/venta', 'descripcion' => 'Módulo de ventas de pasajes', 'modulo' => 'Ventas'],
            'Encomiendas' => ['ruta' => '/encomiendas', 'descripcion' => null, 'modulo' => 'Encomiendas'],
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
        //    Distribución real del dump:
        //      Superadmin    → los 9 módulos
        //      Administrador → Inicio, Configuracion, Vehiculos
        //      Usuario       → Inicio
        // =====================================================================
        $planModuloRol = [
            'Superadmin' => '*',
            'Administrador' => ['Inicio', 'Configuracion', 'Vehiculos'],
            'Usuario' => ['Inicio'],
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
        //      Superadmin    → TODOS los formularios × 4 acciones         = 68
        //      Administrador → Inicio + Configuracion + Vehiculos × 4    = 44
        //      Usuario       → Inicio × [Ver]                            = 1
        //                                                                   ───
        //                                                                   113
        // =====================================================================
        $planPermisos = [
            'Superadmin' => [
                'modulos' => '*',
                'acciones' => ['Ver', 'Crear', 'Editar', 'Eliminar'],
            ],
            'Administrador' => [
                'modulos' => ['Inicio', 'Configuracion', 'Vehiculos'],
                'acciones' => ['Ver', 'Crear', 'Editar', 'Eliminar'],
            ],
            'Usuario' => [
                'modulos' => ['Inicio'],
                'acciones' => ['Ver'],
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
        // 10. USUARIO ADMINISTRADOR
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