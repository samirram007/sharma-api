<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

class SuccessCollection extends ResourceCollection
{
    protected string $message;

    protected int $successCode;

    public function __construct(
        $resource,
        ?string $message = null,
        int $successCode = 200
    ) {
        parent::__construct($resource);
        $this->message = $message ?? 'Records fetched successfully';
        $this->successCode = $successCode;
    }

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }

    public function with(Request $request): array
    {
        $recordCount = $this->collection->count();

        return [
            'success' => true,
            'code' => $this->successCode,
            'message' => $this->message.' ('.$recordCount.' record(s))',
            'meta' => $this->paginationMeta(),
        ];
    }

    /**
     * Return minimal pagination metadata when the resource is a paginator.
     * Only the fields needed for server-side pagination are exposed;
     * the `from`/`to` range can be derived client-side from current_page/per_page/total.
     */
    protected function paginationMeta(): ?array
    {
        if (! $this->resource instanceof AbstractPaginator) {
            return null;
        }

        return [
            'current_page' => $this->resource->currentPage(),
            'last_page' => $this->resource->lastPage(),
            'per_page' => $this->resource->perPage(),
            'total' => $this->resource->total(),
        ];
    }
}
