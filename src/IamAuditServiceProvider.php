<?php

namespace IamAudit;

use IamAudit\Commands\AuditCommand;
use Illuminate\Support\ServiceProvider;

class IamAuditServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AuditCommand::class,
            ]);
        }
    }
}
