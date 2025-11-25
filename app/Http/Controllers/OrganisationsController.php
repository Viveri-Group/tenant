<?php

namespace App\Http\Controllers;

use App\Http\Resources\Web\WebOrganisationsResource;
use App\Models\Organisation;
use Inertia\Inertia;

class OrganisationsController extends Controller
{
    public function index()
    {
        return Inertia::render(
            'Auth/Organisations/Index',
            [
                'organisations' => WebOrganisationsResource::collection(Organisation::all()),
            ]
        );
    }
}
