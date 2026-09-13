<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('db:backup')->daily()->withoutOverlapping();

// Processa os e-mails enfileirados (PDF do contrato, documentos, notificações
// de atividade do cliente) a cada minuto. Usa --stop-when-empty em vez de um
// worker persistente porque hospedagem compartilhada (cPanel) normalmente não
// permite processos de longa duração — o schedule:run já roda a cada minuto.
Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping();
