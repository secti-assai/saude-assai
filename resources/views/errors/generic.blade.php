<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Saúde Assaí - Erro interno</title>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/favicon-16x16.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/favicon.ico') }}">
    <link rel="manifest" href="{{ asset('assets/site.webmanifest') }}">
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
