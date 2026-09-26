<?php

namespace IamAudit;

use Aws\Iam\IamClient;
use IamAudit\Commands\AuditCommand;
use IamAudit\Commands\PolicyCommand;
use Illuminate\Support\ServiceProvider;

class IamAuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Load our default config, so it works even if the user never publishes it
        $this->mergeConfigFrom(__DIR__.'/../config/iam-audit.php', 'iam-audit');

        $this->app->bind('iam-audit.client', function ($app) {
            $config = $app['config']['iam-audit'];

            $options = [
                'version' => 'latest',
                'region' => $config['region'],
            ];

            if ($config['key'] && $config['secret']) {
                $options['credentials'] = [
                    'key' => $config['key'],
                    'secret' => $config['secret'],
                ];
            } elseif ($config['profile']) {
                $options['profile'] = $config['profile'];
            }

            // If neither is set, the SDK uses the default credential chain

            return new IamClient($options);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Allow: php artisan vendor:publish --tag=iam-audit-config
            $this->publishes([
                __DIR__.'/../config/iam-audit.php' => config_path('iam-audit.php'),
            ], 'iam-audit-config');

            $this->commands([
                AuditCommand::class,
                PolicyCommand::class,
            ]);
        }
    }
}
