# Agent Guidelines for Octopus

This skeleton project is a PHP 8.4+ web application using League\Container for dependency injection and League/Plates for templating.

## Architecture

```
src/
├── Authentication/      # Core authentication functionality
│   ├── Controller/       # HTTP controllers (Login, Registration)
│   ├── Service/          # Business logic (Account, Authentication, Password)
│   ├── Table/            # Database access (AccountTable)
│   ├── Validator/        # Value validation
│   └── Exception/        # Domain-specific exceptions
├── Base/                # Cross-cutting concerns
│   ├── Command/         # Console commands
│   ├── Controller/      # Generic controllers (Index, Json)
│   ├── Factory/         # Connection and logger factories
│   ├── Http/            # Response classes (Html, Json)
│   ├── Interface/       # Service abstractions
│   ├── Service/         # Base services (Cache, CSRF, Alert, Translation)
│   └── PlatesExtension/ # Plates template extensions
└── Software.php         # Application metadata (TRANSLATIONS_DIR constant)

config/
├── container.php        # DI container configuration
└── routes.php           # URL routing configuration

template/                      # League/Plates templates
├── index.php
├── pageBase.php
└── element/
    └── alert.php
```

## Data Flow Conventions
1. **Request → Route**: `config/routes.php` maps URLs to controller methods.
2. **Controller → Service**: Controllers receive dependencies via constructor injection from the DI container.
3. **Service → Table**: Services must delegate all data operations to Table repositories.
4. **Table → Doctrine**: Tables utilize `Doctrine\DBAL\QueryBuilder` and *must* use parameter binding for all query executions.
5. **Response**: Controllers return standardized responses (`HtmlResponse` or `JsonResponse`).


1. **Request → Route**: `config/routes.php` maps URLs to controller methods
2. **Controller → Service**: Controllers receive dependencies from DI container
3. **Service → Table**: Services delegate data operations to Table repositories
4. **Table → Doctrine**: Tables use `Doctrine\DBAL\QueryBuilder` with parameter binding
5. **Response**: Controllers return `Laminas\Diactoros\Response` or custom `HtmlResponse`/`JsonResponse`

# Project Constraints & Dependencies
*   **PHP Version**: Requires PHP 8.4+ (per `composer.json`).
*   **Core Frameworks**: Relies heavily on League\Container for DI, League/Plates for templating, and Doctrine\DBAL for ORM/DBAL interactions.
*   **Mandatory Extensions**: Requires `ext-pdo` for database connectivity.
*   **Environment**: Uses `symfony/dotenv` to initialize the environment before accessing `$_ENV` variables.


```bash
# 1. Copy environment template
cp .env.example .env

# 2. Configure .env with database credentials
#    Required variables:
#    - SOFTWARE_TIMEZONE
#    - SOFTWARE_PRODUCTION (false for dev, true for prod)
#    - SOFTWARE_LOGLEVEL (DEBUG|INFO|NOTICE|WARNING|ERROR|CRITICAL|ALERT|EMERGENCY)
#    - SOFTWARE_HOST (fetched via $_ENV['SOFTWARE_HOST'])
#    - DB_HOST, DB_NAME, DB_USER, DB_PASSWORD

# 3. Install dependencies
composer install

# 4. Ensure database is created and configured
```

## Build, Test, & Execution Commands
*   **Install Dependencies**:
    *   `composer install`: Installs required packages listed in `composer.json`.
    *   `composer update`: Updates all project dependencies.
*   **Run Tests**:
    *   `./vendor/bin/phpunit [Test File]`: Executes PHPUnit tests. Ensure test files target the correct scope.
*   **Clear Cache**:
    *   `php src/Base/Command/CacheClearCommand.php`: Must be run explicitly to clear application caches.
*   **Deployment/Runtime**:
    *   (No explicit build command found; project appears to run directly from `public/index.php`.)


```bash
# Install dependencies
composer install

# Update dependencies
composer update

# Run PHPUnit tests
./vendor/bin/phpunit [Test File]

# Clear cached files
php src/Base/Command/CacheClearCommand.php
```

## Architecture / Data Flow Mechanics
*   **Dependency Injection (DI)**: The container is central. Services receive dependencies through constructor injection.
    *   *Example (Service Registration)*: `container->add(SomeService::class)->addArgument(Dependency1::class)->addArgument(Dependency2::class);`
    *   *Example (DB Wiring)*: The Doctrine Connection is provided via the container using `DatabaseFactory`.
*   **Controllers**:
    *   **HTTP Controller**: Must extend `\App\Base\Controller\HtmlController` or `JsonController`.
    *   **Dependency Injection**: Controllers receive templating engines (`Engine`) and core services (e.g., `AlertService`) via constructor injection.
*   **Service Logic**:
    *   **Separation of Concerns**: Business logic belongs in `src/Authentication/Service/` or `src/Base/Service/`. Data access MUST be handled by a `Table` class.
    *   **Data Retrieval**: Use explicit methods like `findByUuid` or `findByEmail` on `Table` objects rather than constructing raw queries.
*   **Template Layer (Plates)**: Extensions (CSRF, Alert, Translator) are auto-loaded/managed by the container; do not manually re-register them.


### Service Registration
```php
// Services receive dependencies via constructor injection
$container->add(SomeService::class)
    ->addArgument(Dependency1::class)
    ->addArgument(Dependency2::class);

// Database connection is wired to containers
$container->add(\Doctrine\DBAL\Connection::class, 
    new \App\Base\Factory\DatabaseFactory()->connect());
```

### Controller Dependencies
Controllers receive two types of arguments:
1. **Leagve\Plates\Engine** - Template rendering (only HTML controllers)
2. **Service implementations** - Authentication, CSRF, Alert, Translation services

### Controller Types
- **HtmlController** (`IndexController`): Returns `\App\Base\Http\HtmlResponse`
- **JsonController** (`IndexController`): Returns `\App\Base\Http\JsonResponse`
- **Specific controllers** (Login, Registration): Receive all required services as arguments

## Naming Conventions
*   **Namespaces**: Strictly follow the pattern `App\[Domain]\[Type]\[Name]`.
    *   **Domain**: `Authentication`, `Base`
    *   **Type**: `Service`, `Controller`, `Table`, `Model`, `Command`, `Exception`, `DTO`, `Validator`
*   **Exceptions**: Followes the pattern `{Resource}{Problem}Exception` (e.g., `AccountEmailIsAlreadyUsedException`).
*   **DTOs**: Must be prefixed by purpose (e.g., `CreateAccountDTO`, `LoginAccountDTO`).
*   **Plates templates**: Keep names simple (e.g., `index.php`, `pageBase.php`).
*   **Environment variables**: Core system variables use the `SOFTWARE_*` prefix.


- **Namespaces**: `App\[Domain]\[Type]\[Name]`
  - Domain: `Authentication`, `Base`
  - Type: `Service`, `Controller`, `Table`, `Model`, `Command`, `Exception`, `DTO`, `Validator`
- **Exceptions**: `{Resource}{Problem}Exception` (e.g., `AccountEmailIsAlreadyUsedException`)
- **DTOs**: `{Purpose}AccountDTO` (e.g., `CreateAccountDTO`, `LoginAccountDTO`)
- **Plates templates**: Simple filenames (`index.php`, `pageBase.php`)
- **Environment variables**: `SOFTWARE_*` prefix

## Testing Approach
*   **Tooling**: Use PHPUnit 13+ (`./vendor/bin/phpunit`).
*   **Pattern**: Tests must utilize dependency injection heavily, mocking external dependencies (like Services or Table connectors) to ensure unit isolation.
*   **Database**: Database operations require careful setup, likely involving transaction rollback mechanisms or dedicated, isolated test/sandbox databases. Raw SQL execution in tests should be avoided in favor of ORM/DBAL builder patterns.


- PHPUnit 13+ (`vendor/bin/phpunit`)
- Test files should mirror structure in `tests/` directory
- Use dependency injection for mockable dependencies
- Database tests may need transaction rollback or test database

## Code Organization Principles

1. **Dependency Inversion**: Controllers depend on interfaces/containers, not implementations
2. **Separation of Concerns**: Business logic in Services, data access in Tables, presentation in Templates
3. **Fail Fast**: Exceptions thrown in Services with descriptive messages, logged via Monolog\Logger

## Key Technical Gotchas (Non-Obvious Knowledge)
*   **Environment Variables**:
    *   **Route Dependency**: Routes *directly* consume `$_ENV['SOFTWARE_HOST']`. The system relies on `symfony/dotenv` running before any route processing occurs to populate this variable.
    *   **Language Context**: The *default* language for translation is loaded from `$_ENV['APP_DEFAULT_LANGUAGE']`, *not* from `SOFTWARE_*` variables.
*   **Database Security**:
    *   **SQL Injection Prevention**: **Never** build queries using string concatenation. Always use parameter binding (`setParameter`) provided by `Doctrine\DBAL\QueryBuilder`.
*   **Object Lifecycle**:
    *   **Services**: When creating/updating services, always reference the full, correct *interface* in the container binding, not just a concrete class, to maximize DI flexibility.
    *   **Plates Extensions**: Extensions are automatically managed; attempting to manually instantiate or re-register them outside of `container.php` is prone to breakage.
*   **System Context**:
    *   The application is bootstrapped via `public/index.php` and routing is done via `config/routes.php`. All primary actions flow from this entry point.


### Environment Variables
- Routes use `$_ENV['SOFTWARE_HOST']` directly (not from `.env` until loaded by Dotenv)
- Ensure `symfony/dotenv` is loaded before accessing `$_ENV` variables
- Missing `.env` file will throw `EnvironmentException`

### Plates Extensions
- Csrf, Alerts, and Translator extensions are auto-loaded onto the Engine in `container.php`
- Extensions receive their services as constructor arguments
- Don't manually re-register extensions - they're container-managed

### Database Tables
- Tables extend `AbstractTable` which provides the Doctrine\Connection
- Always use parameter binding (`setParameter`) to prevent SQL injection
- Use `findByUuid`/`findByEmail` instead of raw queries when possible

### Logging
- Logger instance is passed to all services via container
- Use appropriate RFC5424 levels (DEBUG, INFO, WARNING, ERROR, etc.)
- Production: change `SOFTWARE_LOGLEVEL=Warning` and `SOFTWARE_PRODUCTION=true`

### Translation Service
- Default language loaded from `$_ENV['APP_DEFAULT_LANGUAGE']` (not SOFTWARE_*)
- Translation files stored in `translations/` directory as PHP arrays
- Extension registered on League\Plates\Engine for template access

## Security Notes

- CSRF protection via `CsrfProtectionService` integrated into Plates extension
- Account creation validates email format, password strength, uniqueness
- Password verification uses constant-time comparison (built into `PasswordService`)
- All database queries use parameterized statements
- Production mode disables detailed error messages

## Common Patterns

### Creating a new Service
```php
// 1. Define in src/\[Domain]/Service/
class MyService
{
    public function __construct(Logger $logger) {}
}

// 2. Register in config/container.php
$container->add(App\[Domain]\Service\[Name]Service::class)
    ->addArgument(Logger::class);
```

### Creating a new Controller
```php
// 1. Add route in config/routes.php
$router->post('/endpoint', '\App\[Domain]\Controller\[Name]Controller:[action]')
    ->setHost($_ENV['SOFTWARE_HOST']);

// 2. Create controller with dependencies
class NameController extends \App\Base\Controller\HtmlController
{
    public function __construct(
        Engine $engine,
        Service $service,
        AlertService $alertService
    ) {}
}
```

### Database Operation
```php
// Use Table repository pattern
$accountTable = $container->get(AccountTable::class);
$account = new Account($uuid, $email, $password);
$accountTable->insert($account); // returns bool
$accountTable->findByEmail($email); // returns ?Account
```

## Deployment & Maintenance
*   **File Locations**:
    *   Templates: `template/*.php`
    *   Translation files: `translations/*.php`
    *   Log files: `data/log/*.log`
    *   Caches: `data/cache/*.cache`
    *   Static assets: `public/`
    *   Entry point: `public/index.php` (Primary entry point)
*   **Database Schema**:
    *   Primary keys MUST use UUIDs (`doctrine/dbal` handles this). Do not use auto-incrementing integers for primary identifiers.
    *   The core `accounts` table contains: UUID (PK), email, password hash, and timestamps.


| Purpose | Location |
|---------|----------|
| Templates | `template/*.php` |
| Translation files | `translations/*.php` |
| Log files | `data/log/*.log` |
| Caches | `data/cache/*.cache` |
| Static assets | `public/` |
| Entry point | `public/index.php`, `public/.htaccess` |

## Database Schema

See `database.sql` for the initial schema. Key table:
- **accounts**: UUID, email, password hash, timestamps
- Always use UUIDs for primary keys (better than auto-increment)
