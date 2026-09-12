<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; font-size: 14px;">
    <p>Olá!</p>

    <p>
        O cliente <strong>{{ $contract->client->name }}</strong> fez uma alteração no contrato
        <strong>#{{ $contract->id }}</strong> pelo link público de acesso:
    </p>

    <p>
        <strong>{{ \App\Models\AuditLog::actionOptions()[$log->action] ?? $log->action }}:</strong>
        {{ $log->subjectLabel() }} &mdash; {{ $log->auditable_label }}
    </p>

    @if ($log->changes)
        <ul>
            @foreach ($log->changes as $field => $value)
                <li>{{ $field }}: {{ is_scalar($value) ? $value : json_encode($value) }}</li>
            @endforeach
        </ul>
    @endif

    <p>
        Data/hora: {{ $log->created_at->format('d/m/Y H:i') }}
    </p>

    <p>
        Atenciosamente,<br>
        {{ config('cerimonial.company_name') }}
    </p>
</body>
</html>
