<?php

namespace App\Http\Resources\Backend\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class AdminLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return Arr::only($this->resource->attributesToArray(), ['id', 'administrator_id', 'actor', 'action', 'subject', 'ip', 'user_agent', 'changes', 'created_at']);
    }
}
