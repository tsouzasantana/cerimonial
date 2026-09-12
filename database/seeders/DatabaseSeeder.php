<?php

namespace Database\Seeders;

use App\Models\OccurrenceType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('ADMIN_NAME', 'Administrador'),
                'password' => env('ADMIN_PASSWORD', 'password'),
            ]
        );

        $types = [
            ['name' => 'Orçamento enviado', 'contract_status' => null],
            ['name' => 'Contrato assinado', 'contract_status' => 'ativo'],
            ['name' => 'Evento realizado', 'contract_status' => 'concluido'],
            ['name' => 'Cancelamento', 'contract_status' => 'cancelado'],
        ];

        foreach ($types as $type) {
            OccurrenceType::firstOrCreate(['name' => $type['name']], $type);
        }

        $this->call(ServiceSeeder::class);
    }
}
