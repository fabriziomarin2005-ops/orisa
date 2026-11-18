<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReporteIncidenteRequest;
use App\Models\ReporteIncidente;
use App\Models\Zona;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteIncidenteController extends Controller
{
    private function buildFilteredQuery(Request $request): Builder
    {
        $query = ReporteIncidente::with('zona')->orderBy('fecha', 'desc')->orderBy('id', 'desc');

        if ($request->filled('lugar')) {
            $query->where('lugar', $request->string('lugar'));
        }

        if ($request->filled('zona_id')) {
            $query->where('zona_id', $request->integer('zona_id'));
        }

        if ($request->filled('nivel_riesgo')) {
            $query->where('nivel_riesgo', $request->string('nivel_riesgo'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->string('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('codigo_incidente', 'like', $search)
                    ->orWhere('titulo', 'like', $search)
                    ->orWhere('encargado', 'like', $search)
                    ->orWhere('lugar', 'like', $search);
            });
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha', '>=', $request->string('fecha_inicio'));
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha', '<=', $request->string('fecha_fin'));
        }

        return $query;
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->buildFilteredQuery($request);
        $perPage = $request->integer('per_page', 15);

        return response()->json($query->paginate($perPage));
    }

    public function storeFromIot(StoreReporteIncidenteRequest $request): JsonResponse
    {
        $configuredToken = env('IOT_API_TOKEN');
        if (!empty($configuredToken)) {
            $incomingToken = $request->header('X-API-Token');
            if ($incomingToken !== $configuredToken) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Token de hardware IoT inválido.',
                ], 401);
            }
        }

        $validated = $request->validated();

        return DB::transaction(function () use ($request, $validated) {
            $reporte = $this->persistReporte($validated, $request->file('foto'));

            Cache::put('orisa_last_iot_heartbeat', now()->toIso8601String(), now()->addDays(7));

            return response()->json([
                'status'  => 'success',
                'message' => 'Reporte de incidente registrado en plataforma ORISA.',
                'data'    => $reporte->load('zona'),
            ], 201);
        });
    }

    public function simularIot(Request $request): JsonResponse
    {
        $escenarios = [
            [
                'titulo'       => 'Fuga menor en válvula neumática de presión',
                'descripcion'  => 'Se observa condensación y escape intermitente en el sello del acoplamiento rápido de la línea secundaria.',
                'causas'       => 'Desgaste por fatiga térmica en empaque de nitrilo (O-ring) y vibración mecánica continua.',
                'nivel_riesgo' => 'medio',
                'lugar'        => 'Línea de Ensamble 1',
                'encargado'    => 'Ing. Brandon Matus',
                'soluciones'   => [
                    'Purgar la línea neumática secundaria y bloquear suministro (LOTO)',
                    'Reemplazar sello elastomérico de 3/8" y aplicar cinta de politetrafluoroetileno',
                    'Verificar presión de trabajo en manómetro regulador a 90 PSI',
                ],
            ],
            [
                'titulo'       => 'Derrame de líquido refrigerante en pasillo técnico',
                'descripcion'  => 'Acumulación superficial de fluido soluble sobre piso epóxico junto a la bandeja recolectora del torno.',
                'causas'       => 'Saturación del filtro de retorno de viruta metálica en depósito de bombeo.',
                'nivel_riesgo' => 'alto',
                'lugar'        => 'Área de Maquinado CNC',
                'encargado'    => 'Ing. Fabrizio Aguilar',
                'soluciones'   => [
                    'Delimitar el perímetro con conos de seguridad y aplicar absorbente industrial granulado',
                    'Limpiar rejilla de retorno del tanque de soluble y destapar ducto de drenaje',
                    'Inspeccionar nivel de fluido y estado de la bomba centrífuga',
                ],
            ],
            [
                'titulo'       => 'Obstrucción parcial en sensor óptico de barrera',
                'descripcion'  => 'Residuos de empaque plástico interfieren con el haz infrarrojo de protección perimetral.',
                'causas'       => 'Desprendimiento de película estirable durante maniobra de estibado.',
                'nivel_riesgo' => 'critico',
                'lugar'        => 'Expo Umma',
                'encargado'    => 'Ing. Brandon Matus',
                'soluciones'   => [
                    'Retirar material obstructor de inmediato y limpiar lente del emisor/receptor',
                    'Ejecutar prueba de paro de emergencia por interrupción de cortina óptica',
                    'Registrar evento en bitácora de seguridad funcional del turno',
                ],
            ],
        ];

        $seleccion = $escenarios[array_rand($escenarios)];
        $seleccion['fecha'] = now()->format('Y-m-d H:i:s');

        return DB::transaction(function () use ($seleccion) {
            $reporte = $this->persistReporte($seleccion, null);

            Cache::put('orisa_last_iot_heartbeat', now()->toIso8601String(), now()->addDays(7));

            return response()->json([
                'status'  => 'success',
                'message' => 'Paquete de simulación IoT inyectado correctamente.',
                'data'    => $reporte->load('zona'),
            ], 201);
        });
    }

    private function persistReporte(array $data, $archivoFoto = null): ReporteIncidente
    {
        $nombreLugar = trim($data['lugar']);
        $codigoSugerido = 'ZN-' . strtoupper(Str::slug(Str::words($nombreLugar, 2, ''), ''));

        $zona = Zona::firstOrCreate(
            ['nombre' => $nombreLugar],
            [
                'codigo'      => substr($codigoSugerido, 0, 15),
                'descripcion' => 'Zona registrada automáticamente por terminal IoT ORISA.',
            ]
        );

        $fechaReporte = Carbon::parse($data['fecha']);
        $anio = $fechaReporte->format('Y');

        $ultimoReporte = ReporteIncidente::whereYear('fecha', $anio)
            ->lockForUpdate()
            ->orderBy('id', 'desc')
            ->first();

        $consecutivo = 1;
        if ($ultimoReporte && preg_match('/INC-\d{4}-(\d+)/', $ultimoReporte->codigo_incidente, $matches)) {
            $consecutivo = intval($matches[1]) + 1;
        } else {
            $consecutivo = ReporteIncidente::whereYear('fecha', $anio)->count() + 1;
        }

        $codigoIncidente = sprintf('INC-%s-%04d', $anio, $consecutivo);

        $fotoPath = null;
        if ($archivoFoto) {
            $fotoPath = $archivoFoto->store('incidentes', 'public');
        }

        return ReporteIncidente::create([
            'codigo_incidente' => $codigoIncidente,
            'zona_id'          => $zona->id,
            'lugar'            => $nombreLugar,
            'titulo'           => $data['titulo'],
            'descripcion'      => $data['descripcion'],
            'causas'           => $data['causas'],
            'nivel_riesgo'     => $data['nivel_riesgo'],
            'soluciones'       => $data['soluciones'],
            'fecha'            => $fechaReporte->format('Y-m-d H:i:s'),
            'encargado'        => $data['encargado'],
            'foto_path'        => $fotoPath,
            'estado'           => 'pendiente',
        ]);
    }

    public function iotStatus(): JsonResponse
    {
        $lastHeartbeat = Cache::get('orisa_last_iot_heartbeat');

        if (!$lastHeartbeat) {
            $ultimoRegistro = ReporteIncidente::latest('created_at')->first();
            if ($ultimoRegistro) {
                $lastHeartbeat = $ultimoRegistro->created_at->toIso8601String();
            }
        }

        if (!$lastHeartbeat) {
            return response()->json([
                'online'            => false,
                'last_heartbeat'    => null,
                'minutos_inactivo'  => null,
                'texto_telemetria'  => 'NODO ESP32-S3: SIN TRANSMISIONES RECIENTES',
            ]);
        }

        $fechaLatido = Carbon::parse($lastHeartbeat);
        $minutos = (int) abs(now()->diffInMinutes($fechaLatido));
        $enLinea = $minutos <= 15;

        $tiempoTexto = $minutos === 0 ? 'HACE INSTANTES' : "HACE {$minutos} MIN";
        $estadoNodo = $enLinea ? 'EN LÍNEA' : 'STANDBY';

        return response()->json([
            'online'           => $enLinea,
            'last_heartbeat'   => $fechaLatido->format('Y-m-d H:i:s'),
            'minutos_inactivo' => $minutos,
            'texto_telemetria' => "NODO ESP32-S3: {$estadoNodo} / ÚLTIMO PAQUETE {$tiempoTexto}",
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $reporte = ReporteIncidente::with('zona')->findOrFail($id);
        return response()->json($reporte);
    }

    public function updateEstado(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'estado'               => ['required', 'string', Rule::in(['pendiente', 'en_revision', 'atendido'])],
            'observaciones_cierre' => ['nullable', 'string', 'max:2000'],
        ]);

        $reporte = ReporteIncidente::findOrFail($id);
        $nuevoEstado = $validated['estado'];

        $reporte->estado = $nuevoEstado;

        if (array_key_exists('observaciones_cierre', $validated)) {
            $reporte->observaciones_cierre = $validated['observaciones_cierre'];
        }

        if ($nuevoEstado === 'en_revision' && !$reporte->revisado_en) {
            $reporte->revisado_en = now();
        }

        if ($nuevoEstado === 'atendido') {
            if (!$reporte->revisado_en) {
                $reporte->revisado_en = now();
            }
            $reporte->atendido_en = now();
        }

        $reporte->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Estado operativo y trazabilidad actualizados.',
            'data'    => $reporte->load('zona'),
        ]);
    }

    public function exportarCsv(Request $request): StreamedResponse
    {
        $reportes = $this->buildFilteredQuery($request)->get();
        $filename = 'Bitacora_ORISA_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($reportes) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 para compatibilidad directa de acentos y ñ en Microsoft Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Folio',
                'Fecha',
                'Zona',
                'Título',
                'Nivel de Riesgo',
                'Encargado',
                'Estado',
                'Descripción',
                'Causas',
                'Observaciones de Cierre',
            ]);

            foreach ($reportes as $item) {
                fputcsv($handle, [
                    $item->codigo_incidente,
                    $item->fecha ? $item->fecha->format('Y-m-d H:i:s') : '',
                    $item->lugar,
                    $item->titulo,
                    strtoupper($item->nivel_riesgo),
                    $item->encargado,
                    strtoupper($item->estado),
                    $item->descripcion,
                    $item->causas,
                    $item->observaciones_cierre ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function descargarPdf(int $id)
    {
        $reporte = ReporteIncidente::with('zona')->findOrFail($id);

        $logoPath = public_path('images/logo-orisa.jpg');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $fotoBase64 = null;
        if ($reporte->foto_path) {
            $rutaFisicaFoto = storage_path('app/public/' . ltrim($reporte->foto_path, '/'));
            if (file_exists($rutaFisicaFoto)) {
                $typeFoto = pathinfo($rutaFisicaFoto, PATHINFO_EXTENSION);
                $dataFoto = file_get_contents($rutaFisicaFoto);
                $fotoBase64 = 'data:image/' . $typeFoto . ';base64,' . base64_encode($dataFoto);
            }
        }

        $pdf = Pdf::loadView('pdf.reporte_incidente', [
            'reporte'    => $reporte,
            'logoBase64' => $logoBase64,
            'fotoBase64' => $fotoBase64,
        ])->setPaper('letter', 'portrait');

        return $pdf->download("Reporte_{$reporte->codigo_incidente}.pdf");
    }
}
