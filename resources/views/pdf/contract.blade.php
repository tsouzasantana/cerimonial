<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Contrato de Prestação de Serviços de Cerimonial #{{ $contract->id }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        h2 { font-size: 13px; margin-top: 18px; margin-bottom: 6px; border-bottom: 1px solid #d1d5db; padding-bottom: 2px; }
        .header { text-align: center; margin-bottom: 16px; }
        .header p { margin: 2px 0; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table th, table td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; font-size: 11px; }
        table th { background-color: #f3f4f6; }
        .totals { width: 40%; margin-left: auto; margin-top: 6px; }
        .totals td { border: none; padding: 2px 6px; }
        .clause { margin-bottom: 8px; text-align: justify; }
        .signatures { margin-top: 60px; }
        .signature-line { margin-top: 50px; border-top: 1px solid #1f2937; width: 70%; text-align: center; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('cerimonial.company_name') }}</h1>
        @if (config('cerimonial.company_document'))
            <p>{{ config('cerimonial.company_document') }}</p>
        @endif
        @if (config('cerimonial.company_address'))
            <p>{{ config('cerimonial.company_address') }}</p>
        @endif
        <p>
            @if (config('cerimonial.company_phone')) {{ config('cerimonial.company_phone') }} @endif
            @if (config('cerimonial.company_email')) &middot; {{ config('cerimonial.company_email') }} @endif
        </p>
    </div>

    <h1>CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE CERIMONIAL</h1>
    <p style="text-align:center;color:#6b7280;">Contrato nº {{ $contract->id }}</p>

    <h2>1. Partes</h2>
    <p class="clause">
        <strong>CONTRATADA:</strong> {{ config('cerimonial.company_name') }}, doravante denominada CONTRATADA.
    </p>
    <p class="clause">
        <strong>CONTRATANTE:</strong> {{ $contract->client->name }},
        @if ($contract->client->document) inscrito(a) sob o documento nº {{ $contract->client->document }}, @endif
        @if ($contract->client->email) e-mail {{ $contract->client->email }}, @endif
        @if ($contract->client->phone) telefone {{ $contract->client->phone }}, @endif
        doravante denominado(a) CONTRATANTE.
    </p>

    <h2>2. Objeto e Evento</h2>
    <p class="clause">
        O presente contrato tem por objeto a prestação de serviços de cerimonial para o evento a ser
        realizado em <strong>{{ $contract->event_date->format('d/m/Y') }}</strong>
        @if ($contract->event_location) no local: <strong>{{ $contract->event_location }}</strong>. @else . @endif
    </p>

    <h2>3. Serviços Contratados</h2>
    <table>
        <thead>
            <tr>
                <th>Serviço</th>
                <th>Qtde</th>
                <th>Valor unitário</th>
                <th>Valor total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($contract->items as $item)
                <tr>
                    <td>{{ $item->service->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td>R$ {{ number_format($item->total_price, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal:</td><td>R$ {{ number_format($contract->subtotal, 2, ',', '.') }}</td></tr>
        <tr><td>Desconto:</td><td>R$ {{ number_format($contract->discount, 2, ',', '.') }}</td></tr>
        <tr><td><strong>Valor total:</strong></td><td><strong>R$ {{ number_format($contract->total, 2, ',', '.') }}</strong></td></tr>
    </table>

    <h2>4. Condições de Pagamento</h2>
    @if ($contract->installments->isEmpty())
        <p class="clause">As condições de pagamento serão definidas em comum acordo entre as partes.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Parcela</th>
                    <th>Vencimento</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($contract->installments as $installment)
                    <tr>
                        <td>{{ $installment->number }}</td>
                        <td>{{ $installment->due_date->format('d/m/Y') }}</td>
                        <td>R$ {{ number_format($installment->amount, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>5. Obrigações das Partes</h2>
    <p class="clause">
        A CONTRATADA se compromete a prestar os serviços descritos na cláusula 3 com zelo e profissionalismo,
        na data e local indicados. O CONTRATANTE se compromete a efetuar os pagamentos nas datas
        acordadas e a fornecer as informações necessárias para a execução dos serviços.
    </p>

    <h2>6. Cancelamento e Rescisão</h2>
    <p class="clause">
        Em caso de cancelamento por qualquer das partes, aplicam-se as condições de reembolso e multa
        conforme acordado entre CONTRATANTE e CONTRATADA, observada a legislação vigente.
    </p>

    <h2>7. Foro</h2>
    <p class="clause">
        Fica eleito o foro da comarca de domicílio da CONTRATADA para dirimir quaisquer dúvidas
        oriundas deste contrato.
    </p>

    @if ($contract->notes)
        <h2>8. Observações</h2>
        <p class="clause">{{ $contract->notes }}</p>
    @endif

    <p class="clause" style="margin-top:24px;">
        E por estarem justas e contratadas, as partes assinam o presente instrumento.
    </p>

    <p style="margin-top:16px;">{{ config('cerimonial.company_address') ? \Illuminate\Support\Str::before(config('cerimonial.company_address'), ',') : '____________________' }}, {{ now()->format('d/m/Y') }}.</p>

    <div class="signatures">
        <div class="signature-line">{{ config('cerimonial.company_name') }} (CONTRATADA)</div>
        <div class="signature-line">{{ $contract->client->name }} (CONTRATANTE)</div>
    </div>
</body>
</html>
