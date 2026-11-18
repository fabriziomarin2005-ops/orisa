<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReporteIncidenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Si el ESP32 envía un campo 'json' o 'payload' dentro de un multipart/form-data
        if ($this->has('payload') && is_string($this->input('payload'))) {
            $decoded = json_decode($this->input('payload'), true);
            if (is_array($decoded)) {
                $this->merge($decoded);
            }
        }

        // Si 'soluciones' viene como string JSON en lugar de array nativo (típico en multipart)
        if ($this->has('soluciones') && is_string($this->input('soluciones'))) {
            $decodedSoluciones = json_decode($this->input('soluciones'), true);
            if (is_array($decodedSoluciones)) {
                $this->merge(['soluciones' => $decodedSoluciones]);
            }
        }

        // Normalizar nivel_riesgo a minúsculas sin acentos por seguridad
        if ($this->has('nivel_riesgo') && is_string($this->input('nivel_riesgo'))) {
            $riesgo = strtolower(trim($this->input('nivel_riesgo')));
            $riesgo = str_replace('í', 'i', $riesgo);
            $this->merge(['nivel_riesgo' => $riesgo]);
        }
    }

    public function rules(): array
    {
        return [
            'titulo'        => ['required', 'string', 'max:255'],
            'descripcion'   => ['required', 'string'],
            'causas'        => ['required', 'string'],
            'nivel_riesgo'  => ['required', 'string', Rule::in(['bajo', 'medio', 'alto', 'critico'])],
            'soluciones'    => ['required', 'array', 'min:1'],
            'soluciones.*'  => ['required', 'string'],
            'lugar'         => ['required', 'string', 'max:255'],
            'fecha'         => ['required', 'date'],
            'encargado'     => ['required', 'string', 'max:255'],
            'foto'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
