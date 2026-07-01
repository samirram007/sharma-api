# Project knowledge

This file gives Freebuff context about your project: goals, commands, conventions, and gotchas.

## Quickstart
- **Setup:** `composer run setup` (installs deps, creates .env, generates app key, runs migrations, builds frontend)
- **Dev:** `composer run dev` (serves Laravel + queue listener + Vite concurrently)
- **Test:** `composer run test` (runs `php artisan config:clear` then `php artisan test`)
- **Build frontend:** `npm run build` (Vite build with Tailwind CSS v4)
- **Lint:** `./vendor/bin/pint` (Laravel Pint)

## Architecture
- **Framework:** Laravel 13.x, PHP ^8.3
- **Auth:** JWT via `php-open-source-saver/jwt-auth` — token stored in cookie, read by `JWTFromCookie` middleware
- **Testing:** Pest PHP ^4.7 with `pest-plugin-laravel`; phpunit.xml uses SQLite `:memory:` for testing
- **Database:** Custom DB connection configured in `.env` (likely MySQL/SQLite per env)

### Modular structure (`Modules\/`)
Each module (e.g., `Voucher`, `StockItemBrand`, `Supplier`) follows a strict convention:
```
ModuleName/
  Contracts/     ModuleNameServiceInterface.php
  Models/        ModuleName.php
  Providers/     ModuleNameServiceProvider.php
  Requests/      ModuleNameRequest.php
  Resources/     ModuleNameCollection.php, ModuleNameResource.php
  Routes/        api.php
  Services/      ModuleNameService.php
```
- Modules auto-register via `ModuleServiceLoader` which scans `Modules\/*` and loads each `*ServiceProvider`.
- Each module has a Service Interface + Service class bound in its ServiceProvider.
- Routes are namespaced under the module route file.

### Repository pattern
- `BaseRepository` implements `BaseRepositoryInterface` with CRUD (`all`, `find`, `where`, `paginate`, `create`, `update`, `delete`).
- Repository uses `Cacheable` trait for automatic caching (per-tenant key prefix, version-based invalidation).
- Connect model to a repository via a Service class (not directly in controllers).

### Key traits
- `ApiResponseTrait` — provides `successResponse()`, `errorResponse()`, `resourceResponse()`, `collectionResponse()` for consistent JSON responses.
- `Blameable` — adds created_by/updated_by tracking.
- `Cacheable` — per-tenant cache layer with version-based invalidation and `__PHP_Incomplete_Class` corruption detection.
- `HasPolymorphicResource` / `HasPolymorphicResourceB` — polymorphic relationship helpers.

### Other patterns
- `BaseModel` extends Eloquent Model with auto-merged `$baseCasts`.
- `SuccessResource` / `SuccessCollection` — standardized API resource wrappers (from `app/Http/Resources/`).
- Morph map defined in `AppServiceProvider::boot()` for polymorphic relations (agent, customer, godown, supplier, etc.).
- Enums under `app/Enums/` used throughout the codebase.

## Conventions
- **Formatting:** Laravel Pint (PSR-12 style) — run `./vendor/bin/pint` before committing.
- **Naming:** Modules are PascalCase singular (e.g., `StockItemBrand`, not `stock_item_brands`).
- **Database:** Confirm DB connection in `.env`. No `.env` is committed — copy from `.env.example`.
- **Validation:** Each module has a dedicated FormRequest class for validation.
- **API format:** All responses follow `{ success, code, message, data }` structure via `ApiResponseTrait`.
- **Routes:** Module routes are prefixed under their module namespace in `Routes/api.php`.
- **Cache:** Repositories are cacheable by default. Call `->withoutCache()` on the repository to bypass cache for a specific query. Cache busts automatically on create/update/delete.

## Things to avoid
- Do NOT manually register module service providers in `config/app.php` — `ModuleServiceLoader` handles auto-discovery.
- Do NOT use Sanctum's token-based auth — the project uses JWT from cookies (`php-open-source-saver/jwt-auth`).
- Do NOT disable cache globally unless debugging — the `Cacheable` trait handles invalidation.
- Do NOT bypass the Service → Repository flow; controllers should call Service methods, not repositories directly.
