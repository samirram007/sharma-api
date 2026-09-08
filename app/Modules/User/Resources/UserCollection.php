<?php

namespace Modules\User\Resources;

use App\Http\Resources\SuccessCollection;
use Illuminate\Http\Request;


class UserCollection extends SuccessCollection
{
    // /**
    //  * Transform the resource collection into an array.
    //  *
    //  * @return array<int|string, mixed>
    //  */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    //   public function __construct($resource, ?string $message = null)
    // {
    //     parent::__construct(
    //         UserResource::collection($resource),
    //         $message ?? 'User records fetched successfully'
    //     );
    // }
}
