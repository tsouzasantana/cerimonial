<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Contrato assinado',
            'Documento pessoal',
            'Comprovante de pagamento',
            'Inspiração / referência visual',
            'Contrato de outro fornecedor',
            'Proposta comercial',
            'Outro',
        ];

        foreach ($types as $name) {
            DocumentType::firstOrCreate(['name' => $name]);
        }
    }
}
