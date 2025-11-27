<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrganisationResource;
use App\Models\Organisation;

class OrganisationController extends Controller
{
    public int $paginationAmount = 50;

    public function index()
    {
        return OrganisationResource::collection(Organisation::all()->paginate($this->paginationAmount));
    }
}
