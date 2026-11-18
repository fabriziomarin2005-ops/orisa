<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Zona;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@orisa.local'],
            [
                'name'     => 'Supervisor HSE',
                'password' => Hash::make('orisa2026'),
            ]
        );

        $zonasBase = [
            ['nombre' => 'Expo Umma', 'codigo' => 'ZN-EXPO', 'descripcion' => 'Área de exhibición y pruebas de campo IoT.'],
            ['nombre' => 'Línea de Ensamble 1', 'codigo' => 'ZN-ENS01', 'descripcion' => 'Línea principal de integración mecánica.'],
            ['nombre' => 'Almacén General', 'codigo' => 'ZN-ALM01', 'descripcion' => 'Resguardo de insumos, químicos y refacciones.'],
            ['nombre' => 'Área de Maquinado CNC', 'codigo' => 'ZN-CNC02', 'descripcion' => 'Tornos y centros de maquinado de alta precisión.'],
        ];

        foreach ($zonasBase as $z) {
            Zona::firstOrCreate(['nombre' => $z['nombre']], $z);
        }
    }
}
