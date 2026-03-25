<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Gerenciamento do Portal</h2>
            <p class="sa-page-subtitle">Cadastro de notícias, avisos e alertas exibidos no portal público</p>
        </div>
    </x-slot>

    {{-- Bibliotecas do Trix Editor para formatação de texto e upload de imagens no corpo --}}
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/trix@2.0.8/dist/trix.css">
    <script type="text/javascript" src="https://unpkg.com/trix@2.0.8/dist/trix.umd.min.js"></script>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- COLUNA ESQUERDA: FORMULÁRIOS DE CRIAÇÃO --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200">
                    {{-- Navegação das Abas (Separadas nas pontas) --}}
                    <nav class="flex justify-between -mb-px px-2" aria-label="Tabs">
                        <button onclick="switchTab('news-tab')" id="btn-news-tab" type="button"
                            class="py-4 px-4 text-center border-b-2 font-medium text-sm border-assai-primary text-assai-primary focus:outline-none">
                            Criar Notícia
                        </button>
                        <button onclick="switchTab('alert-tab')" id="btn-alert-tab" type="button"
                            class="py-4 px-4 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none">
                            Criar Aviso / Alerta
                        </button>
                    </nav>
                </div>

                <div class="p-6 text-gray-900">
                    @if(session('status'))
                        <div
                            class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm font-medium">{{ session('status') }}</span>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                            <ul class="list-disc pl-4 text-sm mt-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- FORMULÁRIO 1: NOTÍCIAS --}}
                    <form id="news-tab" method="POST" action="{{ route('admin.portal.store') }}"
                        enctype="multipart/form-data" class="space-y-4 block">
                        @csrf
                        <input type="hidden" name="type" value="Notícia">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Título da Notícia *</label>
                            <input name="title" type="text"
                                class="w-full border-gray-300 focus:border-assai-primary rounded-md shadow-sm"
                                placeholder="Ex: Nova campanha de vacinação" value="{{ old('title') }}" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Imagem de Capa
                                (Opcional)</label>
                            <input type="file" name="cover_image" accept="image/*"
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Resumo Curto (Aparece na página
                                inicial)</label>
                            <textarea name="excerpt"
                                class="w-full border-gray-300 focus:border-assai-primary rounded-md shadow-sm" rows="2"
                                placeholder="Uma frase chamativa...">{{ old('excerpt') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Corpo da Notícia</label>
                            <input id="body_news" type="hidden" name="body" value="{{ old('body') }}">
                            <trix-editor input="body_news"
                                class="bg-white min-h-[200px] prose max-w-none border-gray-300 rounded-md shadow-sm"></trix-editor>
                        </div>

                        <button type="submit"
                            class="w-full inline-flex justify-center items-center px-4 py-2 bg-assai-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-assai-secondary transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Publicar Notícia
                        </button>
                    </form>

                    {{-- FORMULÁRIO 2: AVISOS E ALERTAS --}}
                    <form id="alert-tab" method="POST" action="{{ route('admin.portal.store') }}"
                        class="space-y-4 hidden">
                        @csrf

                        <div class="bg-yellow-50 p-3 rounded text-sm text-yellow-800 flex gap-2 mb-2">
                            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <strong>Aviso:</strong> Bloco de texto lateral na página inicial.<br>
                                <strong>Alerta:</strong> Tarja vermelha no topo de todo o portal.
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Evento *</label>
                            <select name="type"
                                class="w-full border-gray-300 focus:border-assai-primary rounded-md shadow-sm" required>
                                <option value="Aviso">Aviso Comum</option>
                                <option value="Alerta">Alerta Crítico (Vermelho)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                            <input name="title" type="text"
                                class="w-full border-gray-300 focus:border-assai-primary rounded-md shadow-sm"
                                placeholder="Ex: Farmácia fechada para inventário" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem do Aviso/Alerta</label>
                            <textarea name="body"
                                class="w-full border-gray-300 focus:border-assai-primary rounded-md shadow-sm" rows="4"
                                placeholder="Detalhes curtos e diretos..."></textarea>
                        </div>

                        <button type="submit"
                            class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Publicar Aviso / Alerta
                        </button>
                    </form>

                </div>
            </div>
        </div>

        {{-- COLUNA DIREITA: LISTAGEM DOS CONTEÚDOS --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 pb-0">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-gray-900">Conteúdos Publicados</h3>
                        <span
                            class="bg-gray-100 text-gray-700 py-1 px-3 rounded-full text-xs font-bold">{{ count($contents) }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto pb-6 px-6">
                    <table class="w-full whitespace-nowrap text-left text-sm">
                        <thead class="bg-gray-50 text-gray-500 rounded-lg">
                            <tr>
                                <th class="px-4 py-3 font-semibold rounded-l-lg">Título</th>
                                <th class="px-4 py-3 font-semibold">Tipo</th>
                                <th class="px-4 py-3 font-semibold">Data</th>
                                <th class="px-4 py-3 font-semibold text-center">Status</th>
                                <th class="px-4 py-3 font-semibold text-right rounded-r-lg">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($contents as $content)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-4 font-medium text-gray-900 max-w-[200px] truncate"
                                        title="{{ $content->title }}">{{ $content->title }}</td>
                                    <td class="px-4 py-4">
                                        @if(str_contains(strtolower($content->type), 'alerta'))
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                {{ $content->type }}
                                            </span>
                                        @elseif(str_contains(strtolower($content->type), 'aviso'))
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                {{ $content->type }}
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $content->type }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-gray-500">
                                        {{ $content->published_at ? $content->published_at->format('d/m/Y H:i') : '-' }}
                                    </td>

                                    {{-- COLUNA DE STATUS COM O BOTÃO ON/OFF --}}
                                    <td class="px-4 py-4 text-center">
                                        <form method="POST" action="{{ route('admin.portal.toggle', $content) }}"
                                            class="inline m-0 p-0">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="focus:outline-none transition-transform hover:scale-105"
                                                title="Clique para Ligar/Desligar">
                                                @if($content->published)
                                                    <span
                                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200">
                                                        <div class="w-2 h-2 rounded-full bg-green-500 mr-1.5 animate-pulse">
                                                        </div> ON
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-500 border border-gray-200">
                                                        <div class="w-2 h-2 rounded-full bg-gray-400 mr-1.5"></div> OFF
                                                    </span>
                                                @endif
                                            </button>
                                        </form>
                                    </td>

                                    <td class="px-4 py-4 text-right">
                                        <div class="flex space-x-3 justify-end items-center">
                                            <a href="{{ route('admin.portal.edit', $content) }}"
                                                class="text-blue-600 hover:text-blue-900 font-medium">Editar</a>
                                            <form method="POST" action="{{ route('admin.portal.destroy', $content) }}"
                                                class="inline m-0 p-0"
                                                onsubmit="return confirm('Certeza que deseja remover?')">
                                                @csrf
                                                <button type="submit"
                                                    class="text-red-500 hover:text-red-700 font-medium focus:outline-none">Remover</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">Nenhum conteúdo publicado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($contents->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $contents->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- SCRIPTS DA PÁGINA --}}
    <script>
        // Função para alternar as abas
        function switchTab(tabId) {
            // Esconder os formulários
            document.getElementById('news-tab').classList.add('hidden');
            document.getElementById('alert-tab').classList.add('hidden');

            // Repor os estilos inativos (Removido o w-1/2)
            const inactiveClass = "py-4 px-4 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none";
            document.getElementById('btn-news-tab').className = inactiveClass;
            document.getElementById('btn-alert-tab').className = inactiveClass;

            // Mostrar o formulário selecionado e aplicar o estilo ativo (Removido o w-1/2)
            document.getElementById(tabId).classList.remove('hidden');
            const activeClass = "py-4 px-4 text-center border-b-2 font-medium text-sm border-assai-primary text-assai-primary focus:outline-none";
            document.getElementById('btn-' + tabId).className = activeClass;
        }

        // Configuração do Trix para efetuar o upload de imagens arrastadas/inseridas
        document.addEventListener("trix-attachment-add", function (event) {
            if (event.attachment.file) {
                uploadFileAttachment(event.attachment);
            }
        });

        function uploadFileAttachment(attachment) {
            var file = attachment.file;
            var form = new FormData();
            form.append("image", file);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "{{ route('admin.portal.upload-image') }}", true);

            // Obter o token CSRF obrigatório no Laravel
            var csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                xhr.setRequestHeader("X-CSRF-TOKEN", csrfToken.getAttribute('content'));
            }

            xhr.upload.onprogress = function (event) {
                var progress = event.loaded / event.total * 100;
                attachment.setUploadProgress(progress);
            }

            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    var response = JSON.parse(xhr.responseText);
                    // Coloca a imagem efetivamente dentro do editor
                    attachment.setAttributes({
                        url: response.url,
                        href: response.url
                    });
                }
            }
            xhr.send(form);
        }
    </script>
</x-app-layout>