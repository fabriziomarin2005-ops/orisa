<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReporteIncidente;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MetricaController extends Controller
{
    public function index(): JsonResponse
    {
        $totalIncidentes = ReporteIncidente::count();
        $pendientes = ReporteIncidente::where('estado', 'pendiente')->count();
        $enRevision = ReporteIncidente::where('estado', 'en_revision')->count();
        $atendidos = ReporteIncidente::where('estado', 'atendido')->count();

        $criticosPendientes = ReporteIncidente::whereIn('nivel_riesgo', ['critico', 'alto'])
            ->where('estado', '!=', 'atendido')
            ->count();

        $porRiesgoRaw = ReporteIncidente::select('nivel_riesgo', DB::raw('COUNT(*) as total'))
            ->groupBy('nivel_riesgo')
            ->pluck('total', 'nivel_riesgo');

        $porRiesgo = [
            'bajo'    => (int) ($porRiesgoRaw['bajo'] ?? 0),
            'medio'   => (int) ($porRiesgoRaw['medio'] ?? 0),
            'alto'    => (int) ($porRiesgoRaw['alto'] ?? 0),
            'critico' => (int) ($porRiesgoRaw['critico'] ?? 0),
        ];

        $porZona = ReporteIncidente::select('lugar', DB::raw('COUNT(*) as total'))
            ->groupBy('lugar')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'lugar' => $row->lugar ?: 'Sin Zona',
                'total' => (int) $row->total,
            ])
            ->values();

        $zonaMasAfectada = $porZona->first()['lugar'] ?? 'Sin registros';

        $tendenciaDiaria = collect(range(6, 0))->map(function ($daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);
            $count = ReporteIncidente::whereDate('fecha', $date->toDateString())->count();
            return [
                'fecha' => $date->format('d/m'),
                'total' => $count,
            ];
        });

        return response()->json([
            'total_incidentes'    => $totalIncidentes,
            'pendientes'          => $pendientes,
            'en_revision'         => $enRevision,
            'atendidos'           => $atendidos,
            'criticos_pendientes' => $criticosPendientes,
            'zona_mas_afectada'   => $zonaMasAfectada,
            'por_riesgo'          => $porRiesgo,
            'por_zona'            => $porZona,
            'tendencia_diaria'    => $tendenciaDiaria,
        ]);
    }
}
