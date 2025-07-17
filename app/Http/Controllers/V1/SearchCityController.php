<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Services\Dadata\DadataService;
use Illuminate\Http\Request;

class SearchCityController extends Controller
{
    public function __invoke(Request $request, DadataService $dadata)
    {
        $query = $request->input('s');

        if (!$query) {
            return response()->json(['message' => 'Missing parameter: s'], 422);
        }

        return response()->json($dadata->searchCities($query));
    }
}