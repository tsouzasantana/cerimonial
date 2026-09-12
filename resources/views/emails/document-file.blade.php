<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; font-size: 14px;">
    <p>Olá!</p>

    @if ($document->isLink())
        <p>
            Segue o link do documento <strong>{{ $document->title }}</strong>:<br>
            <a href="{{ $document->url }}">{{ $document->url }}</a>
        </p>
    @else
        <p>Segue em anexo o documento <strong>{{ $document->title }}</strong>.</p>
    @endif

    <p>
        Atenciosamente,<br>
        {{ config('cerimonial.company_name') }}
    </p>
</body>
</html>
