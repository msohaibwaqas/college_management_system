<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

$output = "# SCMS - Complete User Guide\n\n";

// --- Project Overview ---
$output .= "## PROJECT OVERVIEW\n\n";
$output .= "- **Project Name:** SCMS (School/College Management System)\n";
$output .= "- **Purpose:** Manage admissions, academics, HR, finance, and library for an educational institution.\n";
$output .= "- **Business Objectives:** Streamline administrative tasks, provide portals for students/parents/staff, and maintain accurate financial and academic records.\n";
$output .= "- **Target Users:** Super Admins, Admins, Principals, Teachers, Clerks, Accountants, Students, Parents.\n";
$output .= "- **Technology Stack:** Laravel, PHP, MySQL, Blade, Vue/React (if applicable), Bootstrap.\n";
$output .= "- **Laravel Version:** " . app()->version() . "\n";
$output .= "- **PHP Version:** " . phpversion() . "\n";
$output .= "- **Database:** " . config('database.default') . "\n";
$output .= "\n### Folder Structure Overview\n";
$output .= "- `app/`: Contains core models, controllers, services, middleware, and commands.\n";
$output .= "- `config/`: Configuration files.\n";
$output .= "- `database/`: Migrations, seeders, and factories.\n";
$output .= "- `public/`: Publicly accessible assets.\n";
$output .= "- `resources/`: Blade views and raw front-end assets.\n";
$output .= "- `routes/`: Route definitions (web, api, console).\n";
$output .= "- `storage/`: Uploads, logs, and compiled views.\n\n";

// --- System Architecture ---
$output .= "## SYSTEM ARCHITECTURE\n\n";
$output .= "### Overall Architecture\n";
$output .= "The application follows a modular monolith architecture based on the MVC (Model-View-Controller) pattern, heavily supplemented by a Service Layer to handle complex business logic.\n\n";
$output .= "### Request Lifecycle\n";
$output .= "1. **Browser** sends an HTTP request.\n";
$output .= "2. **Route** definition matches the URL and assigns it to a Controller, passing through assigned Middleware.\n";
$output .= "3. **Middleware** handles authentication (`auth`), authorization (`role:*`), and session limits (`EnforceMaxSessionDuration`).\n";
$output .= "4. **Controller** receives the request, validates input, and delegates complex operations to the **Service Layer**.\n";
$output .= "5. **Service Layer** executes business rules (e.g., fee calculation, merit list generation) and interacts with **Models**.\n";
$output .= "6. **Model** interacts with the **Database** via Eloquent ORM.\n";
$output .= "7. **Response** is returned (HTML View, JSON, or Redirect).\n\n";
$output .= "```mermaid\ngraph TD\n    Browser --> Route\n    Route --> Middleware\n    Middleware --> Controller\n    Controller --> Validation\n    Validation --> Service\n    Service --> Model\n    Model --> Database\n    Database --> Model\n    Model --> Service\n    Service --> Controller\n    Controller --> Response\n    Response --> Browser\n```\n\n";

// --- Installation ---
$output .= "## INSTALLATION\n\n";
$output .= "- **Requirements:** PHP >= 8.2, Composer, MySQL/MariaDB, Node.js & NPM.\n";
$output .= "- **Installation Steps:**\n";
$output .= "  1. Clone the repository.\n";
$output .= "  2. Run `composer install`.\n";
$output .= "  3. Run `npm install` and `npm run build`.\n";
$output .= "  4. Copy `.env.example` to `.env` and configure environment variables (DB, Mail, Google Auth).\n";
$output .= "  5. Run `php artisan key:generate`.\n";
$output .= "  6. Run `php artisan migrate --seed`.\n";
$output .= "  7. Run `php artisan storage:link`.\n\n";

// --- Database Documentation (Models & Schema) ---
$output .= "## DATABASE DOCUMENTATION\n\n";
$modelsPath = app_path('Models');
$models = [];
if (is_dir($modelsPath)) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modelsPath));
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php' && $file->getFilename() !== 'UpdatePhoto.php') {
            $class = 'App\\Models\\' . str_replace('/', '\\', substr($file->getPathname(), strlen($modelsPath) + 1, -4));
            if (class_exists($class) && is_subclass_of($class, 'Illuminate\Database\Eloquent\Model')) {
                $models[] = $class;
            }
        }
    }
}

foreach ($models as $modelClass) {
    try {
        $model = new $modelClass;
        $table = $model->getTable();
        $output .= "### Table: `$table` (Model: `$modelClass`)\n\n";
        
        $columns = Schema::getColumnListing($table);
        if (!empty($columns)) {
            $output .= "| Column | Type |\n| --- | --- |\n";
            foreach ($columns as $column) {
                $type = Schema::getColumnType($table, $column);
                $output .= "| `$column` | $type |\n";
            }
        } else {
            $output .= "*(Schema not currently available or table does not exist)*\n";
        }
        $output .= "\n";
    } catch (\Exception $e) {
        // Skip models that fail to instantiate
    }
}

// --- Routes & Endpoints ---
$output .= "## SCREEN BY SCREEN DOCUMENTATION (ROUTES)\n\n";
$routes = Route::getRoutes();
$output .= "| Method | URI | Name | Action | Middleware |\n";
$output .= "| --- | --- | --- | --- | --- |\n";
foreach ($routes as $route) {
    if (Str::startsWith($route->uri(), '_ignition')) continue;
    $methods = implode('|', array_diff($route->methods(), ['HEAD']));
    $uri = $route->uri();
    $name = $route->getName() ?: '';
    $action = $route->getActionName();
    if ($action === 'Closure') $action = 'Closure';
    else $action = class_basename($action);
    $middleware = implode(', ', $route->middleware() ?? []);
    
    $output .= "| $methods | `$uri` | $name | $action | $middleware |\n";
}
$output .= "\n";

// --- Controllers ---
$output .= "## CONTROLLERS SUMMARY\n\n";
$controllersPath = app_path('Http/Controllers');
if (is_dir($controllersPath)) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllersPath));
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $output .= "- `" . $file->getFilename() . "`\n";
        }
    }
}
$output .= "\n";

// --- Workflows ---
$output .= "## WORKFLOWS & BUSINESS RULES\n\n";
$output .= "### Student Admission\n";
$output .= "1. Applicant submits form via `/public/apply`.\n";
$output .= "2. System generates `AdmissionApplication` and calculates initial fee via `FeeCalculationService`.\n";
$output .= "3. Admin verifies documents and updates status to `under_review` -> `approved`.\n";
$output .= "4. Fee voucher is issued and marked as `fee_paid` once payment is confirmed.\n";
$output .= "5. `MeritListService` runs, migrating application to `Student` and `Admissions` models, and assigns a roll number.\n\n";

$output .= "### Fee Collection\n";
$output .= "1. Vouchers are generated monthly/term-wise by iterating active students.\n";
$output .= "2. Students download vouchers from portal or receive them physically.\n";
$output .= "3. Payment is made at the bank or online.\n";
$output .= "4. Accountant confirms payment in system, changing voucher status to `paid`.\n\n";

$output .= "### Role Switching\n";
$output .= "1. User logs in with primary credentials.\n";
$output .= "2. `RoleSwitcherController` detects multiple roles (e.g., Teacher and Admin).\n";
$output .= "3. User selects an active role, which is saved to the session (`active_role`).\n";
$output .= "4. `CheckRole` middleware asserts route permissions against the `active_role`.\n\n";

// --- Artisan Commands ---
$output .= "## ARTISAN COMMANDS & SCHEDULING\n\n";
$output .= "Custom Commands found in `app/Console/Commands`:\n";
$commandsPath = app_path('Console/Commands');
if (is_dir($commandsPath)) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($commandsPath));
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $output .= "- `" . $file->getFilename() . "`\n";
        }
    }
}
$output .= "\n**Scheduled Tasks:**\n";
$output .= "- `students:update-status` runs daily at 01:00 AM to promote Class 11 students to Class 12 or mark Class 12 as Graduated based on their admission year.\n\n";

// --- Security & Best Practices ---
$output .= "## SECURITY & ERROR HANDLING\n\n";
$output .= "- **Authentication:** Managed by Laravel Sanctum for API and session-based auth for web. Google OAuth integration via Socialite.\n";
$output .= "- **Authorization:** Strict role-checking middleware (`CheckRole`). Super Admins have universal access unless masquerading as a lower role.\n";
$output .= "- **Concurrency:** Pessimistic locking (`lockForUpdate()`) is used during critical operations like Voucher Generation and Application Number assignment.\n";
$output .= "- **Data Integrity:** `SoftDeletes` applied to almost all tables to prevent accidental permanent deletion.\n";
$output .= "- **Session Limits:** `EnforceMaxSessionDuration` automatically invalidates sessions older than 5 hours to prevent unauthorized access via abandoned terminals.\n\n";

// Write to file
file_put_contents(__DIR__ . '/USER_GUIDE.md', $output);
echo "Documentation generated successfully in USER_GUIDE.md\n";
