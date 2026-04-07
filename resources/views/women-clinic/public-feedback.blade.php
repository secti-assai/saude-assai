<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliacao de Consulta - Clinica da Mulher</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.public-notification-style')
</head>
<body class="public-page">
    <main class="public-shell">
        <section class="public-card">
            <header class="public-header">
                <span class="public-kicker">Saude Assai | Clinica da Mulher</span>
                <h1 class="public-title">Avaliacao de atendimento da consulta</h1>
                <p class="public-subtitle">Sua opiniao ajuda a equipe a melhorar o acolhimento e a qualidade do atendimento.</p>
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
                            <dt>Consulta</dt>
                            <dd>{{ $appointment->scheduled_for?->format('d/m/Y H:i') ?? 'Nao informada' }}</dd>
                        </div>
                        <div>
                            <dt>Status</dt>
                            <dd>{{ $appointment->status }}</dd>
                        </div>
                        <div>
                            <dt>Formulario</dt>
                            <dd>Retorno do cidadao</dd>
                        </div>
                    </dl>
                </section>

                @if($appointment->feedback_submitted_at)
                    <div class="public-alert public-alert-info">
                        Esta consulta ja possui avaliacao registrada em {{ $appointment->feedback_submitted_at?->format('d/m/Y H:i') }}.
                    </div>
                @else
                    <form method="POST" action="{{ request()->fullUrl() }}" class="public-content" style="padding:0; gap: 0.95rem;">
                        @csrf

                        <div class="public-grid">
                            <div class="public-field">
                                <label for="cpf">CPF *</label>
                                <input id="cpf" type="text" name="cpf" value="{{ old('cpf') }}" placeholder="000.000.000-00" maxlength="14" autocomplete="off" required>
                            </div>

                            <div class="public-field">
                                <label for="birth_date">Data de nascimento *</label>
                                <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date') }}" required>
                            </div>
                        </div>

                        <div class="public-field">
                            <label>Nota do atendimento (1 a 5) *</label>
                            <div class="public-rating">
                                @for ($i = 1; $i <= 5; $i++)
                                    <label>
                                        <input type="radio" name="feedback_score" value="{{ $i }}" @checked((string) old('feedback_score') === (string) $i) required>
                                        <span>{{ $i }}</span>
                                    </label>
                                @endfor
                            </div>
                        </div>

                        <div class="public-field">
                            <label for="feedback_comment">Comentario (opcional)</label>
                            <textarea id="feedback_comment" name="feedback_comment" maxlength="1000" placeholder="Conte de forma breve como foi seu atendimento.">{{ old('feedback_comment') }}</textarea>
                            <p class="public-hint">Limite de 1000 caracteres.</p>
                        </div>

                        <div class="public-actions">
                            <button type="submit" class="public-btn public-btn-primary">Enviar avaliacao</button>
                        </div>
                    </form>
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
