<?php

namespace Database\Seeders;

use App\Models\VendorServiceType;
use Illuminate\Database\Seeder;

class VendorServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Buffet',
            'Decoração',
            'Fotografia',
            'Vídeo',
            'Música / DJ',
            'Convites e papelaria',
            'Doces e bolo',
            'Espaço / salão',
            'Transporte',
            'Iluminação',
        ];

        foreach ($types as $name) {
            VendorServiceType::firstOrCreate(['name' => $name]);
        }
    }
}
