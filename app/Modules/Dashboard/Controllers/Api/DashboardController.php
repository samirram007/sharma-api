<?php

namespace Modules\Dashboard\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use Modules\Dashboard\Contracts\DashboardServiceInterface;

class DashboardController extends Controller
{
    public function __construct(protected DashboardServiceInterface $service) {}

    public function summary(): SuccessResource
    {
        $data = $this->service->summary();

        return new SuccessResource($data, 'Dashboard summary fetched successfully');
    }

    public function zoneWise(): SuccessCollection
    {
        $data = $this->service->zoneWise();

        return new SuccessCollection($data, 'Zone-wise dashboard fetched successfully');
    }

    public function godownWise(): SuccessCollection
    {
        $data = $this->service->godownWise();

        return new SuccessCollection($data, 'Godown-wise dashboard fetched successfully');
    }

    public function transporterWise(): SuccessCollection
    {
        $data = $this->service->transporterWise();

        return new SuccessCollection($data, 'Transporter-wise dashboard fetched successfully');
    }

    public function userWise(): SuccessCollection
    {
        $data = $this->service->userWise();

        return new SuccessCollection($data, 'User-wise dashboard fetched successfully');
    }
}
