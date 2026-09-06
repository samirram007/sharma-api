<?php

namespace Modules\StockGroup\Services;

use App\Support\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockGroup\Contracts\StockGroupServiceInterface;
use Modules\StockGroup\Facades\StockGroupRepositoryFacade;
use Modules\StockGroup\Models\StockGroup;

class StockGroupService extends BaseService implements StockGroupServiceInterface
{
    protected string $modelClass = StockGroup::class;

    protected array $defaultResource = [
        'parent',
    ];

    protected string $repositoryFacadeClass = StockGroupRepositoryFacade::class;

    public function __construct() {}

    /**
     * Get all stock groups — optionally restricted to a single status via
     * the ?status= query parameter (values: active | inactive). When the
     * parameter is absent (or any other value), all records are returned,
     * preserving the behaviour expected by select/dropdown consumers.
     *
     * Search (?search=) and pagination (?per_page=) behave exactly as in
     * the parent implementation.
     */
    public function getAll(): Collection|LengthAwarePaginator
    {
        $perPage = request()->integer('per_page', 0);
        $search = request()->input('search', '');
        $status = request()->query('status', '');

        $statusFilter = in_array($status, ['active', 'inactive'], true)
            ? $status
            : null;

        $repo = $this->getRepository();

        if ($repo) {
            $query = $repo->with($this->defaultResource);

            if ($statusFilter !== null) {
                $query->filter(['status' => $statusFilter]);
            }

            if ($search !== '') {
                $query->search($search, []);
            }

            if ($perPage > 0 || $search !== '') {
                return $query->getPaginated($perPage > 0 ? $perPage : 15);
            }

            return $query->getAllFiltered();
        }

        // Fallback path when no repository facade is bound.
        $query = $this->queryWithResource();

        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }

        if ($perPage > 0 || $search !== '') {
            if ($search !== '') {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            return $query->paginate($perPage > 0 ? $perPage : 15);
        }

        return $query->get();
    }
}
