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

## Data Flow

1. **Request → Route**: `config/routes.php` maps URLs to controller methods
2. **Controller → Service**: Controllers receive dependencies from DI container
3. **Service → Table**: Services delegate data operations to Table repositories
4. **Table → Doctrine**: Tables use `Doctrine\DBAL\QueryBuilder` with parameter binding
5. **Response**: Controllers return `Laminas\Diactoros\Response` or custom `HtmlResponse`/`JsonResponse`

## Installation & Setup

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

## Essential Commands

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

## DI Container Patterns

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

- **Namespaces**: `App\[Domain]\[Type]\[Name]`
  - Domain: `Authentication`, `Base`
  - Type: `Service`, `Controller`, `Table`, `Model`, `Command`, `Exception`, `DTO`, `Validator`
- **Exceptions**: `{Resource}{Problem}Exception` (e.g., `AccountEmailIsAlreadyUsedException`)
- **DTOs**: `{Purpose}AccountDTO` (e.g., `CreateAccountDTO`, `LoginAccountDTO`)
- **Plates templates**: Simple filenames (`index.php`, `pageBase.php`)
- **Environment variables**: `SOFTWARE_*` prefix

## Testing Approach

- PHPUnit 13+ (`vendor/bin/phpunit`)
- Test files should mirror structure in `tests/` directory
- Use dependency injection for mockable dependencies
- Database tests may need transaction rollback or test database

## Code Organization Principles

1. **Dependency Inversion**: Controllers depend on interfaces/containers, not implementations
2. **Separation of Concerns**: Business logic in Services, data access in Tables, presentation in Templates
3. **Fail Fast**: Exceptions thrown in Services with descriptive messages, logged via Monolog\Logger

## Important Gotchas

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

## File Locations

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
