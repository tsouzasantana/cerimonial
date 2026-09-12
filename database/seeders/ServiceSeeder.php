<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Assessoria completa do dia do evento', 'code' => 'CER-001', 'price' => 3500],
            ['name' => 'Coordenação de cerimônia', 'code' => 'CER-002', 'price' => 1800],
            ['name' => 'Assessoria de mesa dos convidados', 'code' => 'CER-003', 'price' => 900],
            ['name' => 'Reunião de alinhamento pré-evento', 'code' => 'CER-004', 'price' => 300],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(['code' => $service['code']], $service);
        }
    }
}
