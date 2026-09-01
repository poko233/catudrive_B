<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Modules\Auth\Services\PermissionService;
use App\Modules\Permiso\Services\PermisoService;
use App\Shared\Models\Accion;
use App\Shared\Models\Formulario;
use App\Shared\Models\FormularioPermiso;
use App\Shared\Models\Modulo;
use App\Shared\Models\Rol;
use App\Shared\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PermissionCacheInvalidationTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Rol $rol;

    private Modulo $modulo;

    private Formulario $formulario;

    private Accion $accionVer;

    private Accion $accionEliminar;

    private PermissionService $permissionService;

    private PermisoService $permisoService;

    protected function setUp(): void
    {
        parent::setUp();

        /*
        |--------------------------------------------------------------------------
        | Cache temporal
        |--------------------------------------------------------------------------
        */

        config()->set(
            'cache.default',
            'array'
        );

        config()->set(
            'app_cache.prefix',
            'metasoft_test'
        );

        config()->set(
            'app_cache.ttl',
            60
        );

        Cache::clear();

        /*
        |--------------------------------------------------------------------------
        | Servicios
        |--------------------------------------------------------------------------
        */

        $this->permissionService =
            app(
                PermissionService::class
            );

        $this->permisoService =
            app(
                PermisoService::class
            );

        /*
        |--------------------------------------------------------------------------
        | Datos de prueba
        |--------------------------------------------------------------------------
        */

        $this->crearEstructuraRBAC();
    }

    /*
    |--------------------------------------------------------------------------
    | Agregar permiso invalida cache
    |--------------------------------------------------------------------------
    */

    public function test_agregar_permiso_invalida_cache_del_usuario(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Estado inicial: solamente Ver
        |--------------------------------------------------------------------------
        */

        FormularioPermiso::query()
            ->create([
                'id_rol' =>
                    $this->rol->id,

                'id_modulo' =>
                    $this->modulo->id,

                'id_formulario' =>
                    $this->formulario->id,

                'id_accion' =>
                    $this->accionVer->id,
            ]);

        /*
         * Limpiamos cualquier cache previa del setup.
         */

        $this->permissionService
            ->forgetPermisos(
                (int) $this->user->id
            );

        /*
        |--------------------------------------------------------------------------
        | Primera lectura
        |--------------------------------------------------------------------------
        |
        | Esto genera y almacena la cache del usuario.
        |
        */

        $antes =
            $this->permissionService
                ->getPermisos(
                    $this->user
                );

        $this->assertContains(
            'Ver',
            $antes[
                $this->modulo->modulo
            ][
                $this->formulario->formulario
            ]
        );

        $this->assertNotContains(
            'Eliminar',
            $antes[
                $this->modulo->modulo
            ][
                $this->formulario->formulario
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Agregar permiso Eliminar
        |--------------------------------------------------------------------------
        |
        | Esta operación debe invalidar automáticamente
        | la cache de todos los usuarios asociados al rol.
        |
        */

        $this->permisoService
            ->addPermiso(
                (int) $this->rol->id,
                [
                    'id_modulo' =>
                        (int) $this->modulo->id,

                    'id_formulario' =>
                        (int) $this->formulario->id,

                    'id_accion' =>
                        (int) $this->accionEliminar->id,
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Segunda lectura
        |--------------------------------------------------------------------------
        |
        | Si la invalidación funciona, PermissionService ya no debe
        | entregar la versión antigua almacenada en cache.
        |
        */

        $despues =
            $this->permissionService
                ->getPermisos(
                    $this->user->fresh()
                );

        $this->assertContains(
            'Ver',
            $despues[
                $this->modulo->modulo
            ][
                $this->formulario->formulario
            ]
        );

        $this->assertContains(
            'Eliminar',
            $despues[
                $this->modulo->modulo
            ][
                $this->formulario->formulario
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Quitar permiso también invalida cache
    |--------------------------------------------------------------------------
    */

    public function test_eliminar_permiso_invalida_cache_del_usuario(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Estado inicial
        |--------------------------------------------------------------------------
        |
        | Ver + Eliminar.
        |
        */

        FormularioPermiso::query()
            ->create([
                'id_rol' =>
                    $this->rol->id,

                'id_modulo' =>
                    $this->modulo->id,

                'id_formulario' =>
                    $this->formulario->id,

                'id_accion' =>
                    $this->accionVer->id,
            ]);

        FormularioPermiso::query()
            ->create([
                'id_rol' =>
                    $this->rol->id,

                'id_modulo' =>
                    $this->modulo->id,

                'id_formulario' =>
                    $this->formulario->id,

                'id_accion' =>
                    $this->accionEliminar->id,
            ]);

        $this->permissionService
            ->forgetPermisos(
                (int) $this->user->id
            );

        /*
        |--------------------------------------------------------------------------
        | Generar cache
        |--------------------------------------------------------------------------
        */

        $antes =
            $this->permissionService
                ->getPermisos(
                    $this->user
                );

        $this->assertContains(
            'Eliminar',
            $antes[
                $this->modulo->modulo
            ][
                $this->formulario->formulario
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Eliminar acción
        |--------------------------------------------------------------------------
        */

        $eliminados =
            $this->permisoService
                ->removeByParams(
                    (int) $this->rol->id,
                    (int) $this->formulario->id,
                    (int) $this->accionEliminar->id
                );

        $this->assertGreaterThan(
            0,
            $eliminados
        );

        /*
        |--------------------------------------------------------------------------
        | Nueva lectura
        |--------------------------------------------------------------------------
        */

        $despues =
            $this->permissionService
                ->getPermisos(
                    $this->user->fresh()
                );

        $this->assertContains(
            'Ver',
            $despues[
                $this->modulo->modulo
            ][
                $this->formulario->formulario
            ]
        );

        $this->assertNotContains(
            'Eliminar',
            $despues[
                $this->modulo->modulo
            ][
                $this->formulario->formulario
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Construir estructura RBAC
    |--------------------------------------------------------------------------
    */

    private function crearEstructuraRBAC(): void
    {
        $unique =
            Str::lower(
                Str::random(
                    12
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Usuario
        |--------------------------------------------------------------------------
        */

        $this->user =
            User::query()
                ->create([
                    'usuario' =>
                        "cache_permission_{$unique}",

                    'password' =>
                        Hash::make(
                            'Test-Security-123!'
                        ),

                    'ci' =>
                        '6'
                        . random_int(
                            1000000,
                            9999999
                        ),

                    'nombres' =>
                        'Usuario',

                    'primer_apellido' =>
                        'Cache',

                    'segundo_apellido' =>
                        'Permission',

                    'genero' =>
                        'Masculino',

                    'fecha_nac' =>
                        '1990-01-01',

                    'email' =>
                        "{$unique}@cachepermission.test",

                    'telefono' =>
                        '4455667',

                    'celular' =>
                        '70000003',

                    'direccion' =>
                        'Testing',

                    'expedido' =>
                        'CBBA',

                    'codigo_qr' =>
                        "CACHE-PERMISSION-{$unique}",

                    'verificacion' =>
                        0,

                    'foto' =>
                        null,

                    'estado' =>
                        'Activo',
                ]);

        /*
        |--------------------------------------------------------------------------
        | Rol
        |--------------------------------------------------------------------------
        */

        $this->rol =
            Rol::query()
                ->create([
                    'rol' =>
                        "Rol Cache {$unique}",

                    'descripcion' =>
                        'Rol creado por test automático.',

                    'estado' =>
                        'Activo',
                ]);

        /*
        |--------------------------------------------------------------------------
        | Módulo
        |--------------------------------------------------------------------------
        */

        $this->modulo =
            Modulo::query()
                ->create([
                    'modulo' =>
                        "Modulo Cache {$unique}",

                    'descripcion' =>
                        'Módulo temporal para test.',

                    'icono' =>
                        'shield',

                    'estado' =>
                        'Activo',
                ]);

        /*
        |--------------------------------------------------------------------------
        | Formulario
        |--------------------------------------------------------------------------
        */

        $this->formulario =
            Formulario::query()
                ->create([
                    'formulario' =>
                        "Formulario Cache {$unique}",

                    'descripcion' =>
                        'Formulario temporal para test.',

                    'ruta' =>
                        "/cache-security/{$unique}",

                    'estado' =>
                        'Activo',
                ]);

        /*
        |--------------------------------------------------------------------------
        | Acciones oficiales
        |--------------------------------------------------------------------------
        */

        $this->accionVer =
            Accion::query()
                ->firstOrCreate([
                    'accion' =>
                        'Ver',
                ]);

        $this->accionEliminar =
            Accion::query()
                ->firstOrCreate([
                    'accion' =>
                        'Eliminar',
                ]);

        /*
        |--------------------------------------------------------------------------
        | formulario_modulo
        |--------------------------------------------------------------------------
        */

        DB::table(
            'formulario_modulo'
        )
            ->insert([
                'id_formulario' =>
                    $this->formulario->id,

                'id_modulo' =>
                    $this->modulo->id,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        /*
        |--------------------------------------------------------------------------
        | modulo_rol
        |--------------------------------------------------------------------------
        */

        DB::table(
            'modulo_rol'
        )
            ->insert([
                'id_rol' =>
                    $this->rol->id,

                'id_modulo' =>
                    $this->modulo->id,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        /*
        |--------------------------------------------------------------------------
        | user_rol
        |--------------------------------------------------------------------------
        */

        $this->user
            ->roles()
            ->attach(
                $this->rol->id
            );
    }
}