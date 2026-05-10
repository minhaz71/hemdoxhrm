<?php

namespace App\Providers;

use App\Services\RoleService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class RoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RoleService::class);
    }

    public function boot(): void
    {
        // @role('admin') ... @endrole
        Blade::directive('role', function (string $expression) {
            return "<?php if(auth()->check() && auth()->user()->hasRole({$expression})): ?>";
        });

        Blade::directive('endrole', function () {
            return '<?php endif; ?>';
        });

        // @hasrole('admin','hr') — multi-role alias
        Blade::directive('hasrole', function (string $expression) {
            return "<?php if(auth()->check() && auth()->user()->hasRole([{$expression}])): ?>";
        });

        Blade::directive('endhasrole', function () {
            return '<?php endif; ?>';
        });
    }
}
