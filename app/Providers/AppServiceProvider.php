<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Detect if running in cms_core with public_html as sibling (Live Server structure)
        if (basename(base_path()) === 'cms_core' && is_dir(base_path('../public_html'))) {
            $this->app->usePublicPath(base_path('../public_html'));
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useBootstrapFive();
        \App\Models\StudentTeacherSubject::observe(\App\Observers\StudentTeacherSubjectObserver::class);
        \App\Models\HeTeacherAssignment::observe(\App\Observers\HeTeacherAssignmentObserver::class);

        // Register Licensing Credits Directive
        \Illuminate\Support\Facades\Blade::directive('cmsCredits', function () {
            if (file_exists(base_path('vendor/Sohaib/CmsCore/ProtectionService.php'))) {
                return "<?php echo \\Sohaib\\CmsCore\\ProtectionService::renderCredits(); ?>";
            }
            return "";
        });
    }
}
