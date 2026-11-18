<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte {{ $reporte->codigo_incidente }}</title>
    <style>
        @page { margin: 28px 34px; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1A1A1A;
            font-size: 11.5px;
            line-height: 1.45;
            margin: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0F233C;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .logo-img {
            height: 68px;
            width: auto;
        }
        .doc-title {
            text-align: right;
            vertical-align: middle;
        }
        .doc-title h1 {
            margin: 0;
            font-size: 16px;
            color: #0F233C;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .doc-code {
            font-size: 13px;
            font-weight: bold;
            color: #E86C1F;
            margin-top: 4px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .meta-table td {
            border: 1px solid #D1D5DB;
            padding: 8px 10px;
            width: 25%;
            vertical-align: top;
        }
        .meta-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #6B7280;
            font-weight: bold;
            display: block;
            margin-bottom: 3px;
        }
        .meta-value {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
        }
        .badge-riesgo {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #0F233C;
            color: #FFFFFF;
        }
        .badge-critico, .badge-alto {
            background-color: #E86C1F;
        }
        .section-box {
            border: 1px solid #D1D5DB;
            margin-bottom: 14px;
            border-radius: 3px;
        }
        .section-header {
            background-color: #F3F4F6;
            border-bottom: 1px solid #D1D5DB;
            padding: 6px 12px;
            font-size: 10.5px;
            font-weight: bold;
            color: #0F233C;
            text-transform: uppercase;
        }
        .section-body {
            padding: 10px 12px;
        }
        .incident-main-title {
            font-size: 14px;
            font-weight: bold;
            color: #0F233C;
            margin: 0 0 12px 0;
        }
        .soluciones-list {
            margin: 0;
            padding-left: 18px;
        }
        .soluciones-list li {
            margin-bottom: 5px;
        }
        .foto-container {
            text-align: center;
            padding: 10px;
            background-color: #F9FAFB;
        }
        .foto-evidencia {
            max-width: 100%;
            max-height: 250px;
            border: 1px solid #9CA3AF;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #D1D5DB;
            padding-top: 6px;
            font-size: 9px;
            color: #6B7280;
            text-align: center;
        }
    </style>
</head>
<body>

<!-- Encabezado con Logo ORISA -->
<table class="header-table">
    <tr>
        <td style="width: 45%;">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="ORISA Logo" class="logo-img">
            @else
                <strong style="font-size: 20px; color: #0F233C;">ORISA</strong><br>
                <span style="font-size: 10px; color: #6B7280;">Intelligent HSE Inspector</span>
            @endif
        </td>
        <td class="doc-title" style="width: 55%;">
            <h1>Acta Técnica de Incidencia Industrial</h1>
            <div class="doc-code">FOLIO: {{ $reporte->codigo_incidente }}</div>
            <div style="font-size: 10px; color: #4B5563; margin-top: 3px;">
                Generado el {{ now()->format('d/m/Y H:i') }}
            </div>
        </td>
    </tr>
</table>

<!-- Título del Incidente -->
<div class="incident-main-title">
    {{ $reporte->titulo }}
</div>

<!-- Cuadrícula de Metadatos -->
<table class="meta-table">
    <tr>
        <td>
            <span class="meta-label">Zona / Área</span>
            <span class="meta-value">{{ $reporte->lugar }}</span>
        </td>
        <td>
            <span class="meta-label">Encargado en Sitio</span>
            <span class="meta-value">{{ $reporte->encargado }}</span>
        </td>
        <td>
            <span class="meta-label">Fecha de Captura</span>
            <span class="meta-value">{{ $reporte->fecha ? $reporte->fecha->format('Y-m-d H:i:s') : 'N/A' }}</span>
        </td>
        <td>
            <span class="meta-label">Nivel de Riesgo / Estado</span>
            <span class="badge-riesgo badge-{{ strtolower($reporte->nivel_riesgo) }}">
                    {{ $reporte->nivel_riesgo }}
                </span>
            <div style="font-size: 9.5px; margin-top: 4px; text-transform: uppercase;">
                Estado: <strong>{{ str_replace('_', ' ', $reporte->estado) }}</strong>
            </div>
        </td>
    </tr>
</table>

<!-- Descripción Visual -->
<div class="section-box">
    <div class="section-header">1. Descripción Visual del Entorno (Análisis Gemini Vision)</div>
    <div class="section-body">
        {{ $reporte->descripcion }}
    </div>
</div>

<!-- Causas Identificadas -->
<div class="section-box">
    <div class="section-header">2. Causas Raíz Identificadas (Transcripción & Diagnóstico)</div>
    <div class="section-body">
        {{ $reporte->causas }}
    </div>
</div>

<!-- Protocolo de Soluciones -->
<div class="section-box">
    <div class="section-header">3. Protocolo de Acciones Correctivas Recomendadas</div>
    <div class="section-body">
        @if(is_array($reporte->soluciones) && count($reporte->soluciones) > 0)
            <ul class="soluciones-list">
                @foreach($reporte->soluciones as $solucion)
                    <li>{{ $solucion }}</li>
                @endforeach
            </ul>
        @else
            <span>Sin acciones registradas.</span>
        @endif
    </div>
</div>

<!-- Evidencia Fotográfica -->
<div class="section-box">
    <div class="section-header">4. Evidencia Fotográfica Capturada en Campo (ESP32-CAM OV2640)</div>
    <div class="foto-container">
        @if($fotoBase64)
            <img src="{{ $fotoBase64 }}" alt="Evidencia del incidente" class="foto-evidencia">
        @else
            <span style="color: #6B7280; font-style: italic;">
                    Reporte registrado sin captura fotográfica adjunta.
                </span>
        @endif
    </div>
</div>

<!-- Pie de página -->
<div class="footer">
    ORISA — Intelligent HSE Inspector | Plataforma Central de Auditoría y Seguridad Industrial
</div>

</body>
</html>
