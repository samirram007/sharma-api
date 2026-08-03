# Project knowledge — sharma-api

This file gives Freebuff context about the **AIPT backend** (Laravel API). See the repo-root `knowledge.md` for the full-stack overview; this file is backend-specific.

## What is this?

Laravel 13 API for **AIPT** (Accounts | Inventory | Payroll | Tax). Modular monolith: ~110 self-contained domain modules under `app/Modules/` (110 controller files). Serves the `sharma-frontend` React SPA.

## Quickstart / Commands

| Action | Command |
|--------|---------|
| Install | `composer install` |
| Setup | `composer run setup` (install + copy .env + key:generate + migrate --force + npm build) |
| Dev | `composer run dev` (concurrently: `php artisan serve` + `queue:listen` + Vite) |
| Test | `composer run test` (config:clear then `php artisan test`, Pest 4) |
| Lint | `./vendor/bin/pint` |
| Static analysis | `composer run phpstan` (PHPStan 2.x, `phpstan.neon` + baseline) |
| Regen PHPStan baseline | `composer run phpstan-baseline` |
| Build assets | `npm run build` (Vite + Tailwind v4) |
| Scaffold service | `php artisan make:module-service {Name}` (`--model=` / `--force`) |

**Stack (from composer.json):** PHP ^8.3, `laravel/framework ^13.8`, `php-open-source-saver/jwt-auth ^2.9`, `laravel/reverb ^1.10`, `laravel/sanctum ^4.0`, `laravel/socialite ^5.28`. Dev: Pest ^4.7, PHPStan ^2.2, Larastan ^3.10, Pint ^1.27, `laravel/pao` (debug toolbar), `laravel/pail` (logs).

## Architecture

### Key directories
```
app/
  Modules/       — ~110 domain modules (the core)
  Enums/         — PHP enums (GstType, CostingMethod, StorageUnitType, TypeOfSupply…)
  Events/        — AppNotificationCreated, etc.
  Helpers/       — ApiErrorResponse
  Traits/        — Blameable, ApiResponseTrait, HasPolymorphicResource, CamelCaseResource
  Support/
    Services/    — BaseService + BaseServiceInterface
    Repositories/— BaseRepository + BaseRepositoryInterface
    Traits/      — Cacheable, ScopesCompany
    ModuleConnectionResolver.php — per-module DB routing
  Console/Commands/ — MakeModuleService
  Http/
    Controllers/Api/ — utility only (Enum, File); domain logic lives in Modules
    Middleware/  — JWTFromCookie, NormalizeQueryParameters, NormalizeRequestKeys
    Resources/   — SuccessResource, SuccessCollection
  Providers/     — ModuleServiceLoader (auto-discovers modules), AppServiceProvider (morph map)
config/          — incl. module-database.php, jwt.php, reverb.php, sanctum.php
routes/api.php   — utility routes only (clear, reload, enums, cookie-test)
```

### Module structure
Each of the ~110 modules follows:
```
app/Modules/{Module}/
  Controllers/Api/{Module}Controller.php
  Models/{Module}.php
  Requests/{Module}Request.php
  Resources/{Module}Resource.php + {Module}Collection.php
  Services/{Module}Service.php + Contracts/{Module}ServiceInterface.php
  Repositories/ (optional) + Contracts/RepositoryInterface.php
  Facades/ (optional)
  Routes/api.php
  Providers/{Module}ServiceProvider.php
  Database/Migrations/ + Tests/
```
**Auto-discovery:** `ModuleServiceLoader` scans `app/Modules/*` and registers each `*ServiceProvider` — never add module providers to `config/app.php`.

### Middleware (global + alias)
| Middleware | Registered | Purpose |
|-----------|------------|---------|
| `NormalizeRequestKeys` | **global** (`$middleware->use()`) | Recursively converts camelCase body keys → snake_case |
| `NormalizeQueryParameters` | **global** (`$middleware->use()`) | Converts camelCase query params → snake_case |
| `HandleCors` | global | CORS (see config below) |
| `jwt.cookies` | alias | Per-route auth (see below) |

Note: the two Normalize* middleware run on **all** requests (web too), not just API — keep that in mind when adding web routes.

### Auth (JWT via httpOnly cookie + bearer fallback)
- **Login:** `POST /api/auth/login` → `AuthService@login` → JWT set as httpOnly cookie (`token`; secure, SameSite=None) **and** returned in the JSON body (`'token' => $token`). The frontend also stores it in localStorage — this is a known security issue (see Gotchas).
- **`JWTFromCookie` middleware:** `bearerToken() ?? cookie('token')` → `JWTAuth::authenticate()` → `Auth::login($user)`. Throws `AuthenticationException` (401) if missing/invalid/expired. Applied per-module in each module's `Routes/api.php`.
- **Refresh:** `POST /api/auth/refresh` → `Auth::refresh()` (blacklists old token; `JWT_BLACKLIST_ENABLED=true`). Frontend auto-calls on 401 and retries.
- **Logout:** `POST /api/auth/logout` → invalidate + clear cookie.
- **Social:** `GET /api/auth/{provider}` (google|github) + `/callback` via Socialite, `findOrCreateSocialUser`.
- **Profile:** `GET /api/auth/me|/profile|/user` → `UserResource` with roles/permissions/fiscal year loaded.
- Public auth routes: `register`, `login`, `forgot-password`, `clean_logout` (GET+POST), social redirect/callback. Everything else is `jwt.cookies`-protected.

### Repository / Interface / Service setup

Every module uses the same layered stack — **Route → Controller → Service → (optional) Repository → Model** — with an interface per layer and DI wiring in the module provider:

```
app/Modules/{Module}/
  Contracts/{Module}ServiceInterface.php     — extends BaseServiceInterface
  Contracts/{Module}RepositoryInterface.php  — extends BaseRepositoryInterface (only with a repository)
  Services/{Module}Service.php               — extends BaseService, implements the ServiceInterface
  Repositories/{Module}Repository.php        — extends BaseRepository (83 modules have one)
  Facades/{Module}Facade.php + {Module}RepositoryFacade.php — optional facades
  Providers/{Module}ServiceProvider.php      — register(): binds both interfaces
```

**Wiring rules:**
- **Provider** — in `register()`: `$this->app->singleton({Module}ServiceInterface::class, {Module}Service::class);` (same for the repository interface). `ModuleServiceLoader` auto-registers every module provider — never add providers to `config/app.php`.
- **Interfaces** extend the base contracts, so consumers can type-hint the interface and receive the concrete service/repository.
- **Controllers** either constructor-inject the *interface* (e.g., `AuthController`, `DashboardController`) or call the *facade* (`{Module}Facade::method()`). Never inject the concrete service.
- **Services** may delegate data access to the repository through its facade by setting `protected string $repositoryFacadeClass = {Module}RepositoryFacade::class;` (see `VoucherEntryService`) — otherwise `BaseService` queries the model directly. `AccountGroup` is the proof-of-concept: service → `AccountGroupRepositoryFacade` → cached repository.

**BaseService** (`app/Support/Services/BaseService.php`) — **~76 services** extend it, implementing `BaseServiceInterface` (`getAll/getById/store/update/delete`). Children set `$modelClass` + `$defaultResource`, override methods for specific return types, and add custom methods.
- Delegation rule (avoids infinite recursion with the overridden children): **public** interface methods → delegate to **protected** helpers (`getAllRecords()`, `findOrFail()`, `createRecord()`, `updateRecord()`, `deleteRecord()`); the protected helpers → query directly.

**BaseRepository** (`app/Support/Repositories/BaseRepository.php`) — implements `BaseRepositoryInterface` with caching via the `Cacheable` trait. Child repositories constructor-inject their Eloquent model (`__construct(AccountGroup $model) { parent::__construct($model); }` — the container auto-resolves it against `BaseRepository::__construct(Model $model, bool $cacheable = true)`; pass `false` to disable caching for that instance):
- Methods: `all / find / where / paginate / create / update / delete / with / cache / withoutCache / query / search / filter / sortBy / getPaginated / getAllFiltered`
- **Caching:** read methods wrap results in `remember()` keyed by method + params (TTL via `CACHE_TTL`, default 3600s); `clearCache()` bumps a per-repository version so every write auto-invalidates; bypass per query with `cache(false)` / `withoutCache()`.
- Children configure `$searchableFields` / `$filterableFields` and can use `remember()` + `getCacheKey()` for custom cached queries (see `AccountGroupRepository::getCurrentLiabilityGroups()`).

**Scaffolding:** `php artisan make:module-service {Name}` generates the Service + ServiceInterface (add `--with-repository` to also generate the Repository + RepositoryInterface and the Facades dir). It does **not** generate the provider bindings, facade classes, or controller — wire those manually.

### Facade pattern (audited)
Most modules ship a `{Module}Facade` (→ `{Module}ServiceInterface`) and optionally a `{Module}RepositoryFacade` (→ `{Module}RepositoryInterface`); each provider `bind`/`singleton`s both interfaces and `ModuleServiceLoader` auto-registers it. A full audit (Aug 2026) verified all **185 facades resolve in the container** — accessor class exists, `make()` + `getFacadeRoot()` succeed, naming conventions hold, no duplicate accessors, no unbound bindings. Cleanup performed: removed 4 dead facades referenced nowhere (`DashboardFacade` — `DashboardController` constructor-injects the interface; `EmployeeRepositoryFacade`, `SupplierRepositoryFacade`, `ReceiptNoteReportRepositoryFacade` — not wired via `BaseService::$repositoryFacadeClass`) and the empty `VoucherNo/Facades/` dir. Intentionally facade-less: `Auth` (special auth module, constructor injection). Note: `VoucherNoServiceInterface` is bound but never consumed — dormant service.

### Response contract
All responses use the unified envelope via `SuccessResource`/`SuccessCollection`:
```json
{ "success": true, "code": 200, "message": "…", "data": … }
```
- **~107 resources** use the `CamelCaseResource` trait (snake_case DB attrs → camelCase automatically). 9 skipped (don't extend `SuccessResource`/`SuccessCollection`).
- **Delete responses:** 102 controllers use `$this->deletedResponse($result, 'EntityName')` from `ApiResponseTrait` (no old `status`/204 pattern).
- Controllers with custom endpoints return `SuccessResource`/`SuccessCollection` per-method (e.g., AccountLedger's `ledger_balance`, `purchase_ledgers`; Freight returns `VoucherCollection`).
- Never return `JsonResponse` from service methods that declare a specific return type — throw exceptions (e.g., `AuthenticationException`) and let controllers format them.

### Multi-database support
`ModuleConnectionResolver` + `config/module-database.php`: `default` connection for all modules, `map` array for per-module overrides (e.g., `'Analytics' => 'mysql'`).

### FiscalYearClose / FiscalYearOpen (year-end close & open workflow)
Two dedicated workflow modules, **not** BaseService CRUD — they orchestrate vouchers/stock journals. Full design notes: `docs/PLAN-fiscal-year-close-open.md`.

**`app/Modules/FiscalYearClose/`** — `close()`, `reopen()`, `preview()` on `FiscalYearCloseService` (uses `HasItemAverageRate` + `ScopesCompany`; injects VoucherEntry/StockJournal/StockJournalEntry/UserFiscalYear services; `DB::transaction` + rollback on failure). **Multi-company scoped:** every entry point (`preview`/`close`/`reopen`) calls `validateCompanyAccess()` (via `App\Support\Traits\ScopesCompany`), which derives the current user's company from `UserFiscalYear → fiscal_year.company_id` and throws if the target FY belongs to another company. Created vouchers carry `company_id`.
- `close()`: creates a **CLSAC** closing-account voucher (P&L `INC`/`EXP` ledgers → Capital/EQY ledger; Balance Sheet ledgers recorded on the same voucher for audit + carry-forward), then a **CLSSK** closing-stock voucher (StockJournal type `CLOSING`, net qty per item/godown/unit, IN/OUT via `MovementType`), then sets `status='inactive'` + `closed_at`/`closed_by`.
- `reopen()`: deletes CLSAC/CLSSK vouchers (entries → godown entries → stock journal → voucher) and restores the FY to active.
- `preview()`: counts vouchers, ledgers with balance, stock items, godowns, `is_closed`.

**`app/Modules/FiscalYearOpen/`** — `preview()`, `open()`, `createOpeningJournalVoucher()` on `FiscalYearOpenService` (also uses `ScopesCompany`); plus `OpeningEntryReportController` (`show` + `groupedByLedger`):
- `open()`: requires previous FY closed + new FY active + no existing opening voucher; **validates both FYs belong to the current user's company**; creates a **single unified `OPNJL`** voucher carrying forward Balance Sheet ledgers (AST → debit, LIA/EQY → credit) **and** stock (StockJournal type `OPENING`, godown-level detail, avg-rate valuation); then points all `UserFiscalYear` rows **of that company** at the new FY.

**System voucher-type codes:** `CLSAC`, `CLSSK`, `OPNJL` (+ legacy `OPNAC`/`OPNSK` kept only for idempotency checks). Seeded by `VoucherCategorySeeder` + `VoucherTypeSeeder` (both wired into `DatabaseSeeder`); `FiscalYearCloseVoucherTypeSeeder` is an idempotent standalone fallback with the same codes.

**Routes** (all `jwt.cookies`, kebab-case `fiscal-years/...`):
| Method | URI |
|---|---|
| GET | `/api/fiscal-years/{fiscalYear}/close-preview` |
| POST | `/api/fiscal-years/{fiscalYear}/close` |
| POST | `/api/fiscal-years/{fiscalYear}/reopen` |
| GET | `/api/fiscal-years/{newFiscalYear}/open-preview/{previousFiscalYear}` |
| POST | `/api/fiscal-years/open` (body: `new_fiscal_year_id`, `previous_fiscal_year_id`) |
| GET | `/api/fiscal-years/{fiscalYear}/opening-entry-report` |
| GET | `/api/fiscal-years/{fiscalYear}/opening-entry-report/grouped-by-ledger` |

Related: `app/Modules/OpeningBalance/` also creates `OPNJL` vouchers (manual opening-balance UI) and reads `CLSAC`/`CLSSK`/`OPNJL` for status/prefill — keep codes in sync.

## API Endpoint Inventory

The full inventory of **609 API routes** (auth, utility, ~95 CRUD resources, custom endpoints per module) lives in the repo-root `knowledge.md` under **"API Endpoint Inventory"** — regenerate with `php artisan route:list --path=api`. Highlights for this backend:
- ~95 `apiResource` CRUD resources (5 routes each) + ~9 modules with **no `jwt.cookies` protection** (public CRUD — see Gotchas).
- Custom endpoints per module: AccountLedger filter lists, Freight report views (`freights_*_wise`), StockSummary stock reports, Menu bulk ops, FiscalYearClose/Open workflow, OpeningBalance, ReceiptNoteReport grouped views, Dashboard widgets, PhysicalStockCount workflow.

## Conventions
- **Module naming:** PascalCase singular folders (`StockItemBrand`), snake_case plural route URIs (`stock_items`, `voucher_types`).
- **API route naming:** snake_case plural, but some modules use kebab-case (`app-notifications`, `fiscal-years`, `opening-balance`) — known inconsistency, see Gotchas.
- **Formatting:** Laravel Pint (PSR-12).
- **Validation:** dedicated FormRequest per module; validation rules reference PHP Enums (`TypeOfSupply`, `GstType`, etc.).
- **Enums:** exposed via `GET /api/enums/{enumName}`.
- **Model traits:** `Blameable` for `created_by`/`updated_by`; `HasPolymorphicResource` for polymorphic relations; `BaseModel` for shared casts.
- **Testing:** Pest 4; `phpunit.xml` uses SQLite `:memory:` for tests.

## Gotchas & Known Issues (verified during audit — needs fixing)
1. **9 route files have NO `jwt.cookies`** → fully public CRUD: `AppMaintenance`, `Country`, `Currency`, `Journal`, `Language`, `Module`, `Post`, `Setting`, `State`. High risk (Journals + Settings are sensitive).
2. **No server-side authorization/RBAC.** Frontend gates by permissions, backend enforces none — any authenticated user can hit `users`/`roles`/`permissions` CRUD directly (IDOR risk).
3. **CORS:** `config/cors.php` uses `allowed_origins => ['*']` with `supports_credentials => true` — invalid combo (wildcard + credentials is rejected by browsers). Should be explicit origins.
4. **Utility routes:** `/api/clear` (any authenticated user can clear caches) and `/api/reload` (`migrate:refresh --seed` — **drops all tables**; only local-env guarded). `/api/cookie-test` echoes the JWT cookie.
5. **Token exposure:** JWT returned in JSON body **and** logged via `Log::info('Login token generated', ['token' => $token])`; frontend stores in localStorage. Should be cookie-only.
6. **No rate limiting:** no `RateLimiter::for` definitions; login/register/forgot-password are unthrottled (brute-force risk).
7. **Route naming inconsistency:** `fiscal_years` (apiResource) vs `fiscal-years/...` (custom) both exist; `saleble_stock` typo; `freights/freight` nested duplication.
8. **Dead code:** commented-out `DayBook` apiResource; dummy public `POST /auth/user-profile`; several backend endpoints with no frontend consumer (`freights_*`, `stock_summaries/net_stock`, `purchase_order_outstanding`, etc.).
9. **Env:** `JWT_SECRET` must be generated via `php artisan jwt:secret` (not by setup). `APP_ENV=local`, `APP_DEBUG=true` in `.env.example`. DB driver is **mariadb** by default (not MySQL/SQLite).
10. ~~**FiscalYearClose seeder mismatch**~~ — **Fixed:** `database/seeders/FiscalYearCloseVoucherTypeSeeder.php` now seeds `CLSAC`/`CLSSK`/`OPNJL` (aligned with the services) and reuses the module's `ACC`/`INV` categories via `firstOrCreate`. Kept as an idempotent standalone fallback; the source of truth remains `app/Modules/VoucherType/Database/Seeders/VoucherTypeSeeder.php`.
11. ~~**Seeders not wired for fresh DBs**~~ — **Fixed:** `VoucherCategorySeeder::class` + `VoucherTypeSeeder::class` are enabled in `database/seeders/DatabaseSeeder.php`, so a fresh `php artisan migrate --seed` creates `CLSAC`/`CLSSK`/`OPNJL` (plus the full voucher-type catalog) and close/open services resolve their types.
12. **FiscalYearClose/Open hardening:** multi-company scoping is **done** (see `ScopesCompany` trait — `validateCompanyAccess()` on all close/open/preview/reopen entry points; created vouchers carry `company_id`; covered by the "belongs to a different company" test). Still open: no role/permission gate on the close/open endpoints (consistent with the API-wide RBAC gap, item 2). Tests: `tests/Feature/FiscalYearCloseTest.php` covers `createClosingStockVoucher` + company scoping with mocked services; `tests/Feature/FiscalYearCloseOpenEndToEndTest.php` covers the full `close()`/`reopen()`/`open()` transaction paths end-to-end with the real service chain (RefreshDatabase).

## Things to avoid
- Do NOT manually register module providers in `config/app.php` — `ModuleServiceLoader` auto-discovers them.
- Do NOT use Sanctum token auth — the app uses JWT via cookies (`jwt.cookies` middleware).
- Do NOT remove `jwt.cookies` from module routes.
- Do NOT return `JsonResponse` from services with typed returns — throw exceptions.
- Do NOT use the old `'status' => 'success'` / `'status' => $result` destroy patterns — use the unified envelope + `deletedResponse()`.
- Do NOT manually map snake_case → camelCase in resources — use `CamelCaseResource`.
- Do NOT disable caching globally — `Cacheable` handles invalidation.
- Do NOT call repositories directly from controllers — go through the Service layer.
