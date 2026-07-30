<?php

namespace App\Support\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Automatically converts snake_case model attributes to camelCase in API resource responses.
 *
 * Usage in a resource class:
 * ```php
 * class MyResource extends SuccessResource
 * {
 *     use CamelCaseResource;
 *
 *     public function toArray(Request $request): array
 *     {
 *         return array_merge($this->toCamelCaseArray($request), [
 *             // Only explicitly define computed fields and nested relations
 *             'nestedRelation' => RelatedResource::make($this->whenLoaded('relation')),
 *             'computedField' => $this->some_computed_attribute,
 *         ]);
 *     }
 * }
 * ```
 */
trait CamelCaseResource
{
    /**
     * Fields to exclude from camelCase conversion entirely.
     * Override in the resource to customize.
     */
    protected function getCamelCaseExcludeFields(): array
    {
        return [
            'laravel_through_key',
        ];
    }

    /**
     * Get all model attributes with snake_case keys converted to camelCase.
     *
     * Call this from the resource's toArray() method and merge with
     * explicitly defined computed fields and relation resources.
     */
    protected function toCamelCaseArray(Request $request): array
    {
        if (is_null($this->resource)) {
            return [];
        }

        // Get base model attributes (all snake_case)
        $attributes = parent::toArray($request);

        if (! is_array($attributes)) {
            return [];
        }

        $converted = [];
        foreach ($attributes as $key => $value) {
            // Skip excluded fields
            if (in_array($key, $this->getCamelCaseExcludeFields(), true)) {
                continue;
            }

            $camelKey = Str::camel($key);

            // Recursively convert nested arrays
            $converted[$camelKey] = is_array($value)
                ? $this->recursiveCamelCase($value)
                : $value;
        }

        return $converted;
    }

    /**
     * Recursively convert all snake_case keys in a nested array to camelCase.
     */
    protected function recursiveCamelCase(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $camelKey = Str::camel((string) $key);
            $result[$camelKey] = is_array($value)
                ? $this->recursiveCamelCase($value)
                : $value;
        }

        return $result;
    }
}
