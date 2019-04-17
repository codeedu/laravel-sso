<?php


namespace CodeEdu\LaravelSso;

use CodeEdu\LaravelSso\Guard\SsoGuard;
use CodeEdu\LaravelSso\Sso\Broker;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class SSOServiceProvider extends ServiceProvider
{


    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        if (config('sso.type') == 'broker') {
            $this->app->singleton(Broker::class, function ($app) {
                $broker = new Broker(
                    config('sso.sso_server'),
                    config('sso.sso_broker_id'),
                    config('sso.sso_broker_secret')
                );
                return $broker;
            });
        }
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('sso.php'),
        ], 'config');
        if (config('sso.type') == 'server') {
            \Route::middleware('api')
                ->prefix(config('sso.server_prefix'))
                ->any('/', function (Request $request) {
                    $ssoServer = app(\App\Sso\Server::class);
                    $command = $request->get('command');

                    if (!$command || !method_exists($ssoServer, $command)) {
                        return response()->json(['error' => 'Unknown command'], 404);
                    }

                    return $ssoServer->$command();

                });
        } else {
            \Auth::extend('sso', function ($app, $name, array $config) {
                return new SsoGuard(
                    app(Broker::class), $config['model']
                );
            });
        }
    }
}
