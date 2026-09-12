<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dados da empresa de cerimonial
    |--------------------------------------------------------------------------
    |
    | Usados para preencher o cabeçalho do contrato em PDF e o rodapé dos
    | e-mails enviados aos clientes.
    |
    */

    'company_name' => env('COMPANY_NAME', config('app.name')),
    'company_document' => env('COMPANY_DOCUMENT'),
    'company_address' => env('COMPANY_ADDRESS'),
    'company_phone' => env('COMPANY_PHONE'),
    'company_email' => env('COMPANY_EMAIL'),
];
