<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel de Chamadas</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-900 text-white h-screen flex flex-col">

<h1 class="text-4xl font-bold text-center mb-6">
    Painel - {{ $unit->name }}
</h1>

<!-- 🔵 BLOCO PRINCIPAL -->
<div id="current-call" class="flex-1 flex items-center justify-center p-10">
    <div class="text-center">
        <div id="current-name" class="text-6xl font-bold mb-4">
            Aguardando chamada...
        </div>

        <div id="current-info" class="text-2xl text-gray-300">
            —
        </div>

        <div id="current-room" class="text-5xl font-bold text-green-400 mt-6">
            —
        </div>
    </div>
</div>

<!-- 🟣 HISTÓRICO -->
<div class="bg-gray-800 p-6">
    <div id="previous-calls" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
    </div>
</div>

<script>
let lastCallId = null;

async function loadCalls() {
    try {
        const response = await fetch(`/api/calls/{{ $unit->id }}`);;
        const data = await response.json();

        const current = data.current;
        const previous = data.previous;

        // 🔵 ATUAL
        if (current) {
            document.getElementById('current-name').textContent =
                current.attendance.citizen.full_name;

            document.getElementById('current-info').textContent =
                `${current.type} • ${current.room ?? 'Sem sala'}`;

            document.getElementById('current-room').textContent =
                current.room ?? '--';

            // 🔊 detectar nova chamada
            if (lastCallId !== current.id) {
                lastCallId = current.id;

                // 🔔 (placeholder pra som depois)
                console.log('Nova chamada!');
            }
        }

        // 🟣 HISTÓRICO
        const container = document.getElementById('previous-calls');
        container.innerHTML = '';

        previous.forEach(call => {
            const el = document.createElement('div');

            el.className = "bg-gray-700 p-4 rounded-lg";

            el.innerHTML = `
                <div class="text-sm font-semibold truncate">
                    ${call.attendance.citizen.full_name}
                </div>

                <div class="text-xs text-gray-300">
                    ${call.type}
                </div>

                <div class="text-lg font-bold text-green-400 mt-1">
                    ${call.room ?? '--'}
                </div>
            `;

            container.appendChild(el);
        });

    } catch (e) {
        console.error('Erro ao carregar chamadas');
    }
}

// inicial
loadCalls();

// atualização
setInterval(loadCalls, 3000);
</script>

</body>
</html>