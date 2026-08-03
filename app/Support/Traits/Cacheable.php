<?php

namespace App\Support\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait Cacheable
{
    protected bool $useCache = true;

    /**
     * Enable or disable cache for the current request/chain.
     */
    public function cache(bool $enabled = true): static
    {
        $this->useCache = $enabled;

        return $this;
    }

    /**
     * Disable cache for the current request/chain.
     */
    public function withoutCache(): static
    {
        return $this->cache(false);
    }

    /**
     * Get the cache key prefix based on the repository class name.
     */
    protected function getCachePrefix(): string
    {
        return strtolower(class_basename($this));
    }

    /**
     * Get the current cache version.
     */
    protected function getCacheVersion(): int
    {
        return Cache::get($this->getCachePrefix().'_version', 1);
    }

    /**
     * Generate a unique cache key for a method and its parameters.
     */
    protected function getCacheKey(string $method, array $params = []): string
    {
        return $this->getCachePrefix().
            '_v'.$this->getCacheVersion().
            '_'.$method.
            '_'.md5(json_encode($params));
    }

    /**
     * Execute the callback and cache the result if enabled.
     */
    protected function remember(string $key, \Closure $callback)
    {
        if (! $this->useCache) {
            $this->useCache = true; // Reset for next call

            return $callback();
        }

        $value = Cache::remember($key, env('CACHE_TTL', 3600), $callback);

        if ($this->hasIncompleteClass($value)) {
            Log::warning("Cache corruption detected (__PHP_Incomplete_Class) for key: {$key}. Clearing corrupted entry.");
            Cache::forget($key);

            return $callback();
        }

        return $value;
    }

    /**
     * Recursively check if the value or any of its children are __PHP_Incomplete_Class.
     */
    protected function hasIncompleteClass($value): bool
    {
        if ($value instanceof \__PHP_Incomplete_Class || (is_object($value) && get_class($value) === '__PHP_Incomplete_Class')) {
            return true;
        }

        if (is_iterable($value)) {
            foreach ($value as $item) {
                if ($this->hasIncompleteClass($item)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Invalidate all cache for this repository by incrementing the version.
     */
    public function clearCache(): void
    {
        Cache::increment($this->getCachePrefix().'_version');
    }
}
