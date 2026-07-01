<?php

namespace Modules\Menu\Resources;

use App\Http\Resources\SuccessCollection;
use Illuminate\Http\Request;

class MenuCollection extends SuccessCollection
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
