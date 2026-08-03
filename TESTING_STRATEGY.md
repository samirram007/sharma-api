# CRUD Chain Testing Strategy

## Architecture Overview

```
Controller
  → XxxServiceInterface (e.g., AccountGroupServiceInterface extends BaseServiceInterface)
    → XxxService extends BaseService (no CRUD overrides — all inherited)
      → BaseService::getById() / store() / update() / delete() / getAll()
        → getRepository() resolves via $repositoryFacadeClass → app() → Repository instance
          → BaseRepository::find() / create() / update() / delete() / all()
            → Cacheable trait (remember, clearCache, cache versioning)
              → Eloquent query
```

## Test Layers

### 1. BaseRepository Unit Tests

**File:** `tests/Unit/Support/BaseRepositoryTest.php`

| Test | Method | What to Assert |
|------|--------|----------------|
| `test_find_returns_model` | `find()` | Returns model, uses `findOrFail`, caches result |
| `test_find_uses_cache` | `find()` | Second call with same params hits cache (assert query runs once) |
| `test_find_with_eager_loading` | `find()` | Relations loaded on returned model |
| `test_create_creates_and_clears_cache` | `create()` | Model created via `Model::create()`, cache version increments |
| `test_update_updates_and_clears_cache` | `update()` | Model found, updated, cache cleared |
| `test_delete_deletes_and_clears_cache` | `delete()` | Model found, deleted, cache cleared |
| `test_all_returns_collection` | `all()` | Returns `Collection`, caches |
| `test_all_with_eager_loading` | `all()` | Relations loaded |
| `test_where_returns_filtered` | `where()` | Returns only matching records |
| `test_where_cached` | `where()` | Second call with same conditions hits cache |
| `test_paginate_returns_paginator` | `paginate()` | Returns `LengthAwarePaginator` |
| `test_paginate_with_eager_loading` | `paginate()` | Relations loaded on paginated items |
| `test_with_sets_eager_load` | `with()` | Stateful — subsequent terminal method uses set relations |
| `test_chained_search_filter_sort_getPaginated` | `search()->filter()->sortBy()->getPaginated()` | Full chain builds correct query, caches |
| `test_chained_search_sort_getAllFiltered` | `search()->sortBy()->getAllFiltered()` | Returns filtered + sorted Collection |
| `test_filter_respects_filterableFields` | `filter()` | Fields not in `$filterableFields` are skipped |
| `test_search_searches_searchableFields` | `search()` | Searches across `$searchableFields` |
| `test_query_state_resets_after_terminal` | `getPaginated()` | After call, `$searchQuery`, `$filterConditions`, `$sortOrders`, `$eagerLoad` all reset |
| `test_cache_cleared_on_write` | `create()` / `update()` / `delete()` | Cache version incremented after write operations |
| `test_cache_bypassed_when_disabled` | `cache(false)->find()` | No caching when disabled |
| `test_hasIncompleteClass_detection` | `remember()` | `__PHP_Incomplete_Class` detected and cache cleared |
| `test_empty_collection_returned` | `where()` | No matching records returns empty `Collection` |
| `test_find_throws_on_missing` | `find()` | `ModelNotFoundException` thrown for non-existent ID |
| `test_sortBy_resets_after_terminal` | `sortBy()->getAllFiltered()` | Sort orders don't leak to next call |
| `test_filter_with_in_condition` | `filter(['status' => ['active','pending']])` | Generates `WHERE IN` clause |
| `test_filter_with_like_condition` | `filter(['name' => '%john%'])` | Generates `LIKE` clause |

### 2. BaseService Unit Tests

**File:** `tests/Unit/Support/BaseServiceTest.php`

Use a test double service that extends `BaseService` with a mock repository.

| Test | Method | What to Assert |
|------|--------|----------------|
| `test_getById_delegates_to_repository` | `getById()` | Calls `$repo->find()` with correct ID and `$defaultResource` |
| `test_store_delegates_to_repository` | `store()` | Calls `$repo->create()` with data |
| `test_update_delegates_to_repository` | `update()` | Calls `$repo->update()` with data and ID |
| `test_delete_delegates_to_repository` | `delete()` | Calls `$repo->delete()` with ID |
| `test_getAll_without_pagination_returns_collection` | `getAll()` | No `?per_page` or `?search` → calls `$repo->all()` |
| `test_getAll_with_per_page_returns_paginated` | `getAll()` | `?per_page=10` → calls `$repo->search()->getPaginated()` |
| `test_getAll_with_search_returns_paginated` | `getAll()` | `?search=foo` → calls `$repo->search()->getPaginated()` |
| `test_getAll_with_per_page_and_search` | `getAll()` | Both params → paginated with search |
| `test_getPaginated_delegates_to_repository` | `getPaginated()` | Calls `$repo->with()->getPaginated()` |
| `test_searchAndPaginate_delegates_to_repository` | `searchAndPaginate()` | Calls `$repo->with()->search()->getPaginated()` |
| `test_getFiltered_delegates_to_repository` | `getFiltered()` | Calls `$repo->with()->search()->filter()->sortBy()->getPaginated()` |
| `test_no_repository_fallback_uses_eloquent` | `getById()` | When no `$repositoryFacadeClass` set, falls back to direct query |
| `test_getRepository_resolves_from_facade` | `getRepository()` | `$repositoryFacadeClass` set → `app(facade::getFacadeAccessor())` resolves correctly |
| `test_queryWithResource_applies_defaultResource` | `queryWithResource()` | Relations from `$defaultResource` are eager loaded |
| `test_empty_defaultResource` | `queryWithResource()` | No relations loaded when `$defaultResource` is empty |

### 3. Child Service Integration Tests (One Representative Module)

**File:** `tests/Feature/AccountGroupServiceTest.php` (use AccountGroup as the representative)

Test the full chain end-to-end for one module to validate that all wiring works.

| Test | What to Assert |
|------|----------------|
| `test_full_crud_cycle` | Create → Read → Update → Delete all succeed, cache invalidated on writes |
| `test_getAll_paginated` | Returns paginated results with `?per_page` |
| `test_getAll_search` | Returns filtered results with `?search` |
| `test_getById_returns_model` | Correct model returned with eager loaded relations |
| `test_service_implements_interface` | Service contract matches `BaseServiceInterface` expectations |
| `test_repository_facade_binding` | `AccountGroupRepositoryFacade::getFacadeAccessor()` resolves to bound implementation |

### 4. Cache Invalidation Tests

| Test | Scenario | Assertion |
|------|----------|-----------|
| `test_cache_cleared_on_create` | After `create()`, same `find()` query | Cache miss (fresh query) |
| `test_cache_cleared_on_update` | After `update()`, same `find()` query | Cache miss |
| `test_cache_cleared_on_delete` | After `delete()`, same `find()` query | Cache miss |
| `test_cache_hit_on_read` | Two sequential `find()` calls | Second call uses cache |
| `test_cache_version_incremented` | After write operation | `Cache::get('accountgrouprepository_version')` incremented |
| `test_different_params_different_cache` | `find(1)` vs `find(2)` | Different cache keys |
| `test_filter_cache_invalidated_on_write` | `create()` then same `getAllFiltered()` query | Cache miss (new result set) |

### 5. Edge Case Tests

| Test | Scenario | Assertion |
|------|----------|-----------|
| `test_getById_throws_on_missing` | Non-existent ID | `ModelNotFoundException` thrown |
| `test_delete_throws_on_missing` | Non-existent ID | `ModelNotFoundException` thrown |
| `test_store_returns_model_with_id` | New record | Returned model has `$model->id` set |
| `test_update_with_empty_data` | Empty data array | Model unchanged, no error |
| `test_getAll_empty_table` | No records | Empty `Collection` or paginator with zero total |
| `test_getPaginated_with_large_per_page` | `per_page=1000` | Returns all records without page splitting |
| `test_getPaginated_page_parameter` | `?page=2` | Returns second page |
| `test_search_with_special_characters` | `?search=O'Brien` | SQL injection safe, records found |
| `test_search_with_empty_string` | `?search=` | Treated as no search, returns all |
| `test_filter_with_null_value` | `filter(['field' => null])` | Filter skipped |
| `test_multiple_sort_orders` | `sortBy('name')->sortBy('id', 'desc')` | Both ORDER BY clauses applied |

### 6. Performance Tests

| Test | Scenario | Assertion |
|------|----------|-----------|
| `test_cached_read_is_faster` | Time `find()` first call vs second call | Second call faster (cache hit) |
| `test_pagination_does_not_load_all` | `paginate(15)` with 1000 records | Only 15 records loaded in query |
| `test_eager_loading_prevents_n+1` | `with(['relation'])->all()` with related data | Only 2 queries executed (main + relation) |

## How to Run

```bash
# Unit tests only (fast)
cd sharma-api && php artisan test --testsuite=Unit --filter=BaseRepository
cd sharma-api && php artisan test --testsuite=Unit --filter=BaseService

# Feature tests (requires DB)
cd sharma-api && php artisan test --testsuite=Feature --filter=AccountGroup

# All CRUD chain tests
cd sharma-api && php artisan test --filter="BaseRepository|BaseService|AccountGroup"

# Full test suite
cd sharma-api && composer run test
```

## Test File Structure

```
tests/
  Unit/
    Support/
      BaseRepositoryTest.php
      BaseServiceTest.php
  Feature/
    AccountGroup/
      AccountGroupServiceTest.php
```

## Implementation Priority

1. **P0 (Critical):** BaseRepository → `find()`, `create()`, `update()`, `delete()`, cache invalidation
2. **P0 (Critical):** BaseService → delegation to repository, `getAll()` auto-pagination
3. **P1 (Important):** BaseRepository → `search()`, `filter()`, `sortBy()`, state reset
4. **P1 (Important):** Edge cases — missing records, empty results, special characters
5. **P2 (Nice to have):** One representative feature test (AccountGroup full cycle)
6. **P2 (Nice to have):** Performance benchmarks for caching

## Key Mocking Strategy

```php
// Mock the facade in BaseService tests
$mockRepo = Mockery::mock(AccountGroupRepositoryInterface::class);
$mockRepo->shouldReceive('with')->andReturnSelf();
$mockRepo->shouldReceive('find')->with(1, Argument::any())->andReturn($model);

// Bind to container (facade resolves from this)
$this->app->instance(AccountGroupRepositoryInterface::class, $mockRepo);

// Service resolves repo via: app(Facade::getFacadeAccessor())
```

## Risks & Gotchas

| Risk | Mitigation |
|------|------------|
| `BaseRepository::find()` throws `ModelNotFoundException` instead of returning null (behavior differs from `Model::find()`) | Tests must use `expectException()` or call within `try/catch`. Document this disparity. |
| Cache persists between test runs | Use `Cache::shouldReceive()` mock in unit tests, or `RefreshDatabase` trait in feature tests |
| `request()` helper in `BaseService::getAll()` requires HTTP context | Use `Request::shouldReceive()` mock or `$request->merge(['per_page' => 10])` in feature tests |
| `$repositoryFacadeClass` not set → falls back to direct Eloquent | Test both paths (with and without facade) |
| `buildFilteredQuery()` resets `$eagerLoad` via `getWith()` but `getAllFiltered()` also calls `buildFilteredQuery()` which reads `$this->eagerLoad` | Important `eagerLoad` state lifecycle is tricky — test this explicitly |
