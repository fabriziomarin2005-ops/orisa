<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZonaController extends Controller
{
    public function index(): JsonResponse
    {
        $zonas = Zona::withCount('reportes')->orderBy('nombre', 'asc')->get();
        return response()->json($zonas);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre'      => ['required', 'string', 'max:255', 'unique:zonas,nombre'],
            'codigo'      => ['nullable', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string'],
        ]);

        $zona = Zona::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Zona industrial creada correctamente.',
            'data'    => $zona->loadCount('reportes'),
        ], 201);
    }
}
