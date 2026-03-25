<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erro interno</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen flex items-center justify-center" style="background: var(--sa-paper);">
    <div class="sa-card max-w-lg w-full text-center">
        <h1 class="text-2xl font-bold text-gray-800">Erro interno temporario</h1>
        <p class="text-gray-600 mt-3">
            Ocorreu uma falha inesperada. Nossa equipe foi notificada para tratar o problema.
        </p>
        <a href="{{ url('/') }}" class="sa-btn-primary mt-6 inline-flex">Voltar ao inicio</a>
    </div>
</body>
</html>
