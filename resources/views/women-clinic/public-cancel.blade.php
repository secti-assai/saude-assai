<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelamento de Consulta - Clinica da Mulher</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.public-notification-style')
</head>
<body class="public-page">
    <main class="public-shell">
        <section class="public-card">
            <header class="public-header">
                <span class="public-kicker">Saude Assai | Clinica da Mulher</span>
                <h1 class="public-title">Cancelar consulta com seguranca</h1>
                <p class="public-subtitle">Use este formulario para confirmar o cancelamento. A validacao exige CPF e data de nascimento para proteger seus dados.</p>
            </header>

            <div class="public-content">
                @if ($errors->any())
                    <div class="public-alert public-alert-danger">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('status'))
                    <div class="public-alert public-alert-success">{{ session('status') }}</div>
                @endif

                <section class="public-panel">
                    <dl>
                        <div>
                            <dt>Data da consulta</dt>
                            <dd>{{ $appointment->scheduled_for?->format('d/m/Y H:i') ?? 'Nao informada' }}</dd>
                        </div>
                        <div>
                            <dt>Status atual</dt>
                            <dd>{{ $appointment->status }}</dd>
                        </div>
                        <div>
                            <dt>Seguranca do link</dt>
                            <dd>Assinado e com expiracao automatica</dd>
                        </div>
                    </dl>
                </section>

                @if($appointment->status === 'AGENDADO')
                    <form method="POST" action="{{ request()->fullUrl() }}" class="public-content" style="padding:0; gap: 0.95rem;">
                        @csrf

                        <div class="public-grid">
                            <div class="public-field">
                                <label for="cpf">CPF *</label>
                                <input id="cpf" type="text" name="cpf" value="{{ old('cpf') }}" placeholder="000.000.000-00" maxlength="14" autocomplete="off" required>
                                <p class="public-hint">Digite o mesmo CPF utilizado no agendamento.</p>
                            </div>

                            <div class="public-field">
                                <label for="birth_date">Data de nascimento *</label>
                                <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date') }}" required>
                                <p class="public-hint">Formato aceito: dia/mes/ano.</p>
                            </div>
                        </div>

                        <div class="public-alert public-alert-info">
                            Ao confirmar, o status da consulta sera alterado para CANCELADO e a vaga podera ser reutilizada pela equipe.
                        </div>

                        <div class="public-actions">
                            <button type="submit" class="public-btn public-btn-danger">Confirmar cancelamento</button>
                        </div>
                    </form>
                @else
                    <div class="public-alert public-alert-info">
                        Esta consulta nao esta mais em status AGENDADO. O cancelamento por este link nao esta disponivel.
                    </div>
                @endif
            </div>
        </section>
    </main>

    <script>
        (function () {
            const cpfInput = document.getElementById('cpf');
            if (!cpfInput) {
                return;
            }

            cpfInput.addEventListener('input', function () {
                let value = this.value.replace(/\D/g, '').slice(0, 11);
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                this.value = value;
            });
        })();
    </script>
</body>
</html>
