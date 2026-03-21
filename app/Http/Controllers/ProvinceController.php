<?php

namespace App\Http\Controllers;

use App\Models\Province;
use Illuminate\Http\JsonResponse;

class ProvinceController extends Controller
{
    public function cities(Province $province): JsonResponse
    {
        $cities = $province->cities()
            ->select('id', 'name', 'name_en')
            ->orderBy('name')
            ->get();

        return response()->json($cities);
    }
}
