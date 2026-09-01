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
        // 2. ACCIONES (Ver, Crear, Editar, Eliminar)
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
        // 4. ROLES (Superadmin, Administrador, Usuario)
        //    NOTA: estos nombres deben coincidir con config('rbac.super_roles')
        // =====================================================================
        $roles = [
            ['rol' => 'Superadmin', 'descripcion' => 'Acceso total al sistema'],
            ['rol' => 'Administrador', 'descripcion' => 'Acceso administrativo'],
            ['rol' => 'Usuario', 'descripcion' => 'Acceso básico de consulta'],
        ];
        foreach ($roles as $rol) {
            DB::table('rol')->updateOrInsert(
                ['rol' => $rol['rol']],
                [
                    'descripcion' => $rol['descripcion'],
                    'estado' => 'Activo',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        $rolIds = DB::table('rol')->pluck('id', 'rol');
        $this->command->info('✅ Roles OK');

        // =====================================================================
        // 5. MÓDULOS (Dashboard, Configuracion)
        // =====================================================================
        $modulos = [
            ['modulo' => 'Dashboard', 'icono' => 'dashboard', 'descripcion' => 'Panel principal del sistema'],
            ['modulo' => 'Configuracion', 'icono' => 'settings', 'descripcion' => 'Panel de administración del sistema'],
        ];
        foreach ($modulos as $mod) {
            DB::table('modulo')->updateOrInsert(
                ['modulo' => $mod['modulo']],
                [
                    'descripcion' => $mod['descripcion'],
                    'icono' => $mod['icono'],
                    'estado' => 'Activo',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        $moduloIds = DB::table('modulo')->pluck('id', 'modulo');
        $this->command->info('✅ Módulos OK');

        // =====================================================================
        // 6. FORMULARIOS
        //    Nombres exactos que espera el middleware permiso y el frontend
        // =====================================================================
        $formularios = [
            ['formulario' => 'Inicio', 'ruta' => '/dashboard'],
            ['formulario' => 'Roles', 'ruta' => '/roles'],
            ['formulario' => 'Modulos', 'ruta' => '/modulos'],
            ['formulario' => 'Formularios', 'ruta' => '/formularios'],
            ['formulario' => 'Permisos', 'ruta' => '/permisos'],
            ['formulario' => 'Empresas', 'ruta' => '/empresa'],
            ['formulario' => 'Sucursales', 'ruta' => '/sucursales'],
            ['formulario' => 'Usuarios', 'ruta' => '/usuarios'],
            ['formulario' => 'Form -> Módulo', 'ruta' => '/formulario_modulo'],
            ['formulario' => 'Módulo -> Rol', 'ruta' => '/modulo_rol'],
        ];
        foreach ($formularios as $f) {
            DB::table('formulario')->updateOrInsert(
                ['formulario' => $f['formulario']],
                [
                    'ruta' => $f['ruta'],
                    'estado' => 'Activo',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        $formularioIds = DB::table('formulario')->pluck('id', 'formulario')->toArray();
        $this->command->info('✅ Formularios OK');

        // =====================================================================
        // 7. ASIGNAR FORMULARIOS A MÓDULOS
        // =====================================================================
        $idDashboard = $moduloIds['Dashboard'] ?? null;
        $idConfiguracion = $moduloIds['Configuracion'] ?? null;

        $asignaciones = [
            $idDashboard => ['Inicio'],
            $idConfiguracion => [
                'Roles',
                'Modulos',
                'Formularios',
                'Permisos',
                'Empresas',
                'Sucursales',
                'Usuarios',
                'Form -> Módulo',
                'Módulo -> Rol',
            ],
        ];

        foreach ($asignaciones as $idModulo => $nombres) {
            if (!$idModulo) {
                continue;
            }
            foreach ($nombres as $nombre) {
                $idForm = $formularioIds[$nombre] ?? null;
                if (!$idForm) {
                    continue;
                }
                DB::table('formulario_modulo')->updateOrInsert(
                    ['id_modulo' => $idModulo, 'id_formulario' => $idForm],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
        $this->command->info('✅ Formulario ↔ Módulo OK');

        // =====================================================================
        // 8. ASIGNAR MÓDULOS A ROLES (modulo_rol)
        // =====================================================================
        $idSuperadmin = $rolIds['Superadmin'] ?? null;
        $idAdministrador = $rolIds['Administrador'] ?? null;
        $idUsuario = $rolIds['Usuario'] ?? null;

        // Superadmin: todos los módulos
        foreach ([$idDashboard, $idConfiguracion] as $idMod) {
            if ($idMod && $idSuperadmin) {
                DB::table('modulo_rol')->updateOrInsert(
                    ['id_rol' => $idSuperadmin, 'id_modulo' => $idMod],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
        // Administrador: igual que Superadmin (tiene acceso a ambos módulos)
        foreach ([$idDashboard, $idConfiguracion] as $idMod) {
            if ($idMod && $idAdministrador) {
                DB::table('modulo_rol')->updateOrInsert(
                    ['id_rol' => $idAdministrador, 'id_modulo' => $idMod],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
        // Usuario: solo Dashboard
        if ($idDashboard && $idUsuario) {
            DB::table('modulo_rol')->updateOrInsert(
                ['id_rol' => $idUsuario, 'id_modulo' => $idDashboard],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
        $this->command->info('✅ Módulo ↔ Rol OK');

        // =====================================================================
        // 9. PERMISOS (formulario_permiso)
        // =====================================================================
        $todosFormularios = $formularioIds;
        // Superadmin: todas las acciones en todos los formularios
        if ($idSuperadmin) {
            DB::table('formulario_permiso')->where('id_rol', $idSuperadmin)->delete();
            $permisos = [];
            foreach ($todosFormularios as $nombre => $idForm) {
                // Determinar el módulo al que pertenece el formulario
                $idModulo = in_array($nombre, ['Inicio']) ? $idDashboard : $idConfiguracion;
                if (!$idModulo) {
                    continue;
                }
                foreach ($accionIds as $idAccion) {
                    $permisos[] = [
                        'id_rol' => $idSuperadmin,
                        'id_modulo' => $idModulo,
                        'id_formulario' => $idForm,
                        'id_accion' => $idAccion,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            DB::table('formulario_permiso')->insert($permisos);
            $this->command->info("✅ Superadmin: " . count($permisos) . " permisos");
        }

        // Administrador: Dashboard solo Ver; Configuracion todas las acciones
        if ($idAdministrador) {
            DB::table('formulario_permiso')->where('id_rol', $idAdministrador)->delete();
            $permisosAdmin = [];
            $idVer = $accionIds['Ver'] ?? null;

            // Dashboard -> Inicio (solo Ver)
            if ($idDashboard && $idVer && isset($todosFormularios['Inicio'])) {
                $permisosAdmin[] = [
                    'id_rol' => $idAdministrador,
                    'id_modulo' => $idDashboard,
                    'id_formulario' => $todosFormularios['Inicio'],
                    'id_accion' => $idVer,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Configuracion -> todos los formularios de ese módulo, con todas las acciones
            $formulariosConfig = array_diff_key($todosFormularios, ['Inicio' => true]);
            foreach ($formulariosConfig as $nombre => $idForm) {
                foreach ($accionIds as $idAccion) {
                    $permisosAdmin[] = [
                        'id_rol' => $idAdministrador,
                        'id_modulo' => $idConfiguracion,
                        'id_formulario' => $idForm,
                        'id_accion' => $idAccion,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if ($permisosAdmin) {
                DB::table('formulario_permiso')->insert($permisosAdmin);
            }
            $this->command->info("✅ Administrador: " . count($permisosAdmin) . " permisos");
        }

        // Usuario: Dashboard solo Ver
        if ($idUsuario) {
            DB::table('formulario_permiso')->where('id_rol', $idUsuario)->delete();
            $permisosUser = [];
            if ($idDashboard && $idVer && isset($todosFormularios['Inicio'])) {
                $permisosUser[] = [
                    'id_rol' => $idUsuario,
                    'id_modulo' => $idDashboard,
                    'id_formulario' => $todosFormularios['Inicio'],
                    'id_accion' => $idVer,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if ($permisosUser) {
                DB::table('formulario_permiso')->insert($permisosUser);
            }
            $this->command->info("✅ Usuario: " . count($permisosUser) . " permisos");
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
                'estado' => 'Activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("✅ Usuario 'admin' creado (id={$idUser})");
        } else {
            $this->command->info("ℹ️  Usuario 'admin' ya existe (id={$idUser})");
        }

        // Asignar sucursal Central al usuario (obligatorio para middleware CheckSucursal)
        if ($idSucursal) {
            DB::table('user_sucursal')->updateOrInsert(
                ['id_user' => $idUser, 'id_sucursal' => $idSucursal],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Asignar rol Superadmin al usuario admin
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