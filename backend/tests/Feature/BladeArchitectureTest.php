<?php

namespace Tests\Feature;

use Illuminate\Database\PostgresConnection;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use LogicException;
use Tests\TestCase;

class BladeArchitectureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            'session.driver' => 'array',
            'cache.default' => 'array',
            'logging.default' => 'null',
            'database.default' => 'blocked',
            'database.connections.blocked' => ['driver' => 'blocked'],
        ]);

        DB::extend('blocked', fn () => new PostgresConnection(
            fn () => throw new LogicException('Las pruebas de vistas no pueden abrir conexiones a bases de datos.'),
            'unused',
        ));
    }

    public function test_home_renders_blade_with_public_assets_and_web_middleware(): void
    {
        $this->get(route('inicio'))
            ->assertOk()
            ->assertViewIs('welcome')
            ->assertSee('Clínica Vida Salud')
            ->assertSee('<html lang="es">', false)
            ->assertSee(asset('css/app.css'), false)
            ->assertSee(asset('js/app.js'), false)
            ->assertSee('name="csrf-token"', false)
            ->assertDontSee('id="root"', false);

        $route = Route::getRoutes()->getByName('inicio');
        $middleware = app('router')->gatherRouteMiddleware($route);

        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertContains(StartSession::class, $middleware);
        $this->assertContains(ShareErrorsFromSession::class, $middleware);
        $this->assertContains(PreventRequestForgery::class, $middleware);
        $this->assertFileExists(public_path('css/app.css'));
        $this->assertFileExists(public_path('js/app.js'));
    }

    public function test_view_packages_match_the_existing_nineteen_use_case_directories(): void
    {
        $controllerRoot = app_path('Http/Controllers/Paquetes');
        $viewRoot = resource_path('views/Paquetes');
        $controllerPackages = glob($controllerRoot.'/*', GLOB_ONLYDIR);
        $viewPackages = glob($viewRoot.'/*', GLOB_ONLYDIR);
        $caseCount = 0;

        $this->assertCount(7, $controllerPackages);
        $this->assertSame(array_map('basename', $controllerPackages), array_map('basename', $viewPackages));

        foreach ($controllerPackages as $package) {
            $controllerCases = array_map('basename', glob($package.'/CU*', GLOB_ONLYDIR));
            $viewCases = array_map('basename', glob($viewRoot.'/'.basename($package).'/CU*', GLOB_ONLYDIR));
            $this->assertSame($controllerCases, $viewCases);

            foreach ($viewCases as $case) {
                $this->assertFileExists($viewRoot.'/'.basename($package).'/'.$case.'/.gitkeep');
            }

            $caseCount += count($viewCases);
        }

        $this->assertSame(19, $caseCount);
        $this->assertDirectoryDoesNotExist(base_path('../frontend'));
    }

    public function test_form_component_adds_csrf_and_method_spoofing_only_when_needed(): void
    {
        $token = str_repeat('a', 40);
        $this->withSession(['_token' => $token]);

        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $html = Blade::render('<x-form action="/solo-pruebas" :method="$method"><button>Enviar</button></x-form>', ['method' => $method]);

            $this->assertStringContainsString('method="post"', $html);
            $this->assertStringContainsString('name="_token"', $html);
            $this->assertStringContainsString('value="'.$token.'"', $html);

            if ($method === 'POST') {
                $this->assertStringNotContainsString('name="_method"', $html);
            } else {
                $this->assertStringContainsString('name="_method" value="'.$method.'"', $html);
            }
        }

        $html = Blade::render('<x-form action="/solo-pruebas" method="get"><button>Consultar</button></x-form>');
        $this->assertStringContainsString('method="get"', $html);
        $this->assertStringNotContainsString('name="_token"', $html);
        $this->assertStringNotContainsString('name="_method"', $html);
    }

    public function test_session_messages_and_validation_errors_are_escaped(): void
    {
        $status = '<script>alert("prueba")</script>';
        $error = '<b>Correo no válido</b>';

        // Usar una redirección real verifica también la serialización JSON de la sesión.
        Route::middleware('web')->get('/__tests/messages', fn () => redirect()
            ->route('inicio')
            ->with('status', $status)
            ->withErrors(['correo' => $error]));

        $this->followingRedirects()
            ->get('/__tests/messages')
            ->assertOk()
            ->assertSee('role="status"', false)
            ->assertSee('role="alert"', false)
            ->assertSee($status)
            ->assertSee($error)
            ->assertDontSee($status, false)
            ->assertDontSee($error, false);
    }

    public function test_web_posts_reject_missing_or_incorrect_csrf_tokens_and_accept_the_session_token(): void
    {
        $this->enforceCsrfDuringTests();
        Route::middleware('web')->post('/__tests/csrf', fn () => response()->noContent());
        $token = str_repeat('b', 40);

        $this->withSession(['_token' => $token])->post('/__tests/csrf')->assertStatus(419);
        $this->withSession(['_token' => $token])->post('/__tests/csrf', ['_token' => 'incorrecto'])->assertStatus(419);
        $this->withSession(['_token' => $token])->post('/__tests/csrf', ['_token' => $token])->assertNoContent();
    }

    public function test_method_spoofing_reaches_a_web_route_with_csrf_protection(): void
    {
        $this->enforceCsrfDuringTests();
        Route::middleware('web')->put('/__tests/method', fn () => response()->noContent());
        $token = str_repeat('c', 40);

        $this->withSession(['_token' => $token])
            ->post('/__tests/method', ['_method' => 'PUT', '_token' => $token])
            ->assertNoContent();
    }

    private function enforceCsrfDuringTests(): void
    {
        // Laravel omite CSRF en pruebas por defecto; aquí se activa para verificarlo de verdad.
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    }
}
