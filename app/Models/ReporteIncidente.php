<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReporteIncidente extends Model
{
    use HasFactory;

    protected $table = 'reportes_incidentes';

    protected $fillable = [
        'codigo_incidente',
        'zona_id',
        'lugar',
        'titulo',
        'descripcion',
        'causas',
        'nivel_riesgo',
        'soluciones',
        'fecha',
        'encargado',
        'foto_path',
        'estado',
        'observaciones_cierre',
        'revisado_en',
        'atendido_en',
    ];

    protected $casts = [
        'soluciones'  => 'array',
        'fecha'       => 'datetime',
        'revisado_en' => 'datetime',
        'atendido_en' => 'datetime',
    ];

    protected $appends = ['foto_url'];

    public function getFotoUrlAttribute(): ?string
    {
        if (!$this->foto_path) {
            return null;
        }

        if (str_starts_with($this->foto_path, 'http')) {
            return $this->foto_path;
        }

        return '/storage/' . ltrim($this->foto_path, '/');
    }

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }
}
