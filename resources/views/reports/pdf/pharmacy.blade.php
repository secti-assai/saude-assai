<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório - Farmácia Central</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
        th { background-color: #f4f4f4; }
        h1 { font-size: 16px; margin-bottom: 5px; }
        h2 { font-size: 12px; color: #555; margin-top: 0; }
        .footer { position: fixed; bottom: -20px; width: 100%; text-align: center; font-size: 9px; color: #777; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="footer">
        Relatório gerado em {{ now()->format('d/m/Y H:i') }}
    </div>

    <h1>Relatório de Atendimentos - Farmácia Central</h1>
    <h2>Período: {{ date('d/m/Y', strtotime($filters['date_start'])) }} a {{ date('d/m/Y', strtotime($filters['date_end'])) }}</h2>

    @if(isset($limit_reached) && $limit_reached)
        <div style="background-color: #fff3cd; color: #856404; padding: 10px; margin-bottom: 10px; border: 1px solid #ffeeba; border-radius: 4px; font-weight: bold; text-align: center;">
            ATENÇÃO: Este relatório possui um volume massivo de dados. Para evitar travamentos, o PDF foi limitado aos primeiros 1.000 registros ({{ $total_rows_omitted }} registros foram ocultados). Por favor, utilize o botão "Exportar CSV" para baixar a planilha com a base completa.
        </div>
    @endif

    <table style="table-layout: fixed;">
        <thead>
            <tr>
                <th style="width: 35%;">Cidadão</th>
                <th style="width: 15%;">Situação</th>
                <th style="width: 10%;">Qtd</th>
                <th style="width: 15%;">Horário</th>
                <th style="width: 25%;">Medicamento</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            @php
                $statusLabel = $row->bypass_detected ? 'NÃO REGULARIZADO' : 'REGULARIZADO';
                $citizenName = $row->citizen ? $row->citizen->full_name : $row->customer_name_raw;
            @endphp
            <tr>
                <td>{{ $citizenName }}</td>
                <td>{{ $statusLabel }}</td>
                <td>{{ $row->quantity ?? '-' }}</td>
                <td>{{ $row->dispensed_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                <td>{{ $row->medication_name_raw ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
