<?php

namespace App\Action\Organisation;

use App\Models\Organisation;
use Illuminate\Support\Facades\Cache;

class GetOrganisationsAction
{
    public function handle()
    {
        return Cache::remember('organisations_keyed', 600, function () {
            return Organisation::all()
                ->mapWithKeys(fn($org) => [
                    $org->id => [
                        'id' => $org->id,
                        'name' => $org->name,
                    ]
                ])
                ->toArray();
        });
    }
}
