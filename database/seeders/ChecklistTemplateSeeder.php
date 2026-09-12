<?php

namespace Database\Seeders;

use App\Models\ChecklistTemplate;
use Illuminate\Database\Seeder;

class ChecklistTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $tasks = [
            ['name' => 'Reunião de briefing inicial com os noivos', 'days_offset' => 90],
            ['name' => 'Definição do roteiro da cerimônia', 'days_offset' => 60],
            ['name' => 'Confirmação de todos os fornecedores', 'days_offset' => 30],
            ['name' => 'Reunião de alinhamento final', 'days_offset' => 7],
            ['name' => 'Ensaio geral', 'days_offset' => 3],
            ['name' => 'Conferência de itens e equipe', 'days_offset' => 1],
            ['name' => 'Execução do evento', 'days_offset' => 0],
            ['name' => 'Envio de agradecimento aos noivos', 'days_offset' => -7],
        ];

        foreach ($tasks as $task) {
            ChecklistTemplate::firstOrCreate(['name' => $task['name']], $task);
        }
    }
}
