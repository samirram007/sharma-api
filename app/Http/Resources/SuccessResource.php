<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class SuccessResource extends JsonResource
{
    protected string $message;

    protected int $successCode;

    public function __construct(
        $resource,
        ?string $message = null,
        int $successCode = 200
    ) {
        parent::__construct($resource);
        $this->message = $message ?? 'Record processed successfully';
        $this->successCode = $successCode;
    }

    public function toArray(Request $request): array
    {
        return is_null($this->resource)
        ? []
        : (is_array($this->resource)
            ? $this->resource
            : $this->resource->toArray());
    }

    public function with(Request $request): array
    {
        return [
            'success' => true,
            'code' => $this->successCode,
            'message' => $this->message,
        ];
    }

    /**
     * Create a new anonymous resource collection.
     *
     * Delegates to the framework so each item is wrapped in a resource
     * (standard `XResource::collection($this->whenLoaded('relation'))`
     * behaviour). Guard against null so callers don't crash on an empty
     * relation; `MissingValue` is passed through untouched — the parent
     * resource's filter removes it when the relation wasn't eager-loaded.
     */
    public static function collection($resource): AnonymousResourceCollection
    {
        return parent::collection(
            is_null($resource) ? collect() : $resource,
        );
    }
}
