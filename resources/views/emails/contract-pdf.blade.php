<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; font-size: 14px;">
    <p>Olá, {{ $contract->client->name }}!</p>

    <p>
        Segue em anexo o contrato de prestação de serviços de cerimonial referente ao evento do dia
        <strong>{{ $contract->event_date->format('d/m/Y') }}</strong>.
    </p>

    <p>
        Por favor, revise o documento, imprima, assine e devolva uma via para
        {{ config('cerimonial.company_name') }}.
    </p>

    <p>Qualquer dúvida, estamos à disposição.</p>

    <p>
        Atenciosamente,<br>
        {{ config('cerimonial.company_name') }}
    </p>
</body>
</html>
