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

    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Cidadão</th>
                <th>Nível</th>
                <th>Validação</th>
                <th>Status</th>
                <th>Categoria</th>
                <th>Qtd.</th>
                <th>Atendente</th>
                <th>Recepção</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                <td>{{ $row->created_at?->format('d/m/Y H:i') }}</td>
                <td>{{ $row->citizen->full_name ?? '' }}</td>
                <td>{{ $row->gov_assai_level ?? 'N/A' }}</td>
                <td>{{ ($row->citizen->pharmacy_lock_flag ?? false) ? 'PENDENTE' : 'REGULARIZADO' }}</td>
                <td>{{ $row->status }}</td>
                <td>{{ $row->medication_name }}</td>
                <td>{{ $row->quantity }}</td>
                <td>{{ $row->attendant_display_name ?? '' }}</td>
                <td>{{ $row->reception->name ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
