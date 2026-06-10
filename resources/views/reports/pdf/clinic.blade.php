<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório - {{ $clinic_type === 'POLICLINICA' ? 'Policlínica' : 'Clínica da Mulher' }}</title>
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

    <h1>Relatório de Atendimentos - {{ $clinic_type === 'POLICLINICA' ? 'Policlínica' : 'Clínica da Mulher' }}</h1>
    <h2>Período: {{ date('d/m/Y', strtotime($filters['date_start'])) }} a {{ date('d/m/Y', strtotime($filters['date_end'])) }}</h2>

    <table>
        <thead>
            <tr>
                <th>Data Agendada</th>
                <th>Cidadão</th>
                <th>Status</th>
                <th>Agendador</th>
                <th>Recepção</th>
                <th>Médico</th>
                <th>Avaliação</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                <td>{{ $row->scheduled_for?->format('d/m/Y H:i') }}</td>
                <td>{{ $row->citizen->full_name ?? '' }}</td>
                <td>{{ $row->status }}</td>
                <td>{{ $row->scheduler->name ?? '' }}</td>
                <td>{{ $row->reception->name ?? '' }}</td>
                <td>{{ $row->doctor->name ?? '' }}</td>
                <td>
                    @if($row->feedback_score)
                        Nota {{ $row->feedback_score }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
