<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Editar Conteúdo do Portal</h2>
            <p class="sa-page-subtitle">Modificando o conteúdo selecionado.</p>
        </div>
    </x-slot>

    {{-- Bibliotecas do Trix Editor --}}
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/trix@2.0.8/dist/trix.css">
    <script type="text/javascript" src="https://unpkg.com/trix@2.0.8/dist/trix.umd.min.js"></script>

    <div class="sa-card sa-fade-in max-w-4xl mx-auto">
        <div class="sa-card-header">
            <h3 class="sa-card-title">Dados do Conteúdo</h3>
        </div>

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded m-4">
                <ul class="list-disc pl-4 text-sm mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- O enctype multipart/form-data é obrigatório para envio de arquivos --}}
        <form method="POST" action="{{ route('admin.portal.update', $content) }}" enctype="multipart/form-data"
            class="space-y-6 p-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="sa-label">Título *</label>
                    <input name="title" type="text" class="sa-input w-full" value="{{ old('title', $content->title) }}"
                        required>
                </div>

                <div>
                    <label class="sa-label">Tipo de Conteúdo *</label>
                    <select name="type" id="contentType" class="sa-input w-full" required onchange="toggleFields()">
                        <option value="Notícia" {{ $content->type == 'Notícia' ? 'selected' : '' }}>Notícia</option>
                        <option value="Aviso" {{ $content->type == 'Aviso' ? 'selected' : '' }}>Aviso</option>
                        <option value="Alerta" {{ $content->type == 'Alerta' ? 'selected' : '' }}>Alerta (Tarja Vermelha)
                        </option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="sa-label">Resumo Curto (Exibido na listagem de notícias ou como corpo do
                        aviso)</label>
                    <textarea name="excerpt" class="sa-input w-full" rows="2"
                        placeholder="Uma frase chamativa...">{{ old('excerpt', $content->excerpt) }}</textarea>
                </div>
            </div>

            {{-- Container da Imagem de Capa (Oculto via JS se não for Notícia) --}}
            <div id="coverImageContainer" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <label class="sa-label">Imagem de Capa</label>

                @if($content->cover_image)
                    <div class="mb-3 flex items-start gap-4">
                        <img src="{{ Storage::url($content->cover_image) }}" alt="Capa atual"
                            style="width: 150px; height: 100px; object-fit: cover; flex-shrink: 0;"
                            class="rounded shadow-sm border border-gray-300">
                        <div class="text-sm text-gray-500 pt-1">
                            <p class="font-medium text-gray-700">Imagem Atual</p>
                            <p>Envie um novo arquivo abaixo se desejar substituir a imagem atual.</p>
                        </div>
                    </div>
                @endif

                <input type="file" name="cover_image" accept="image/*"
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <div>
                <label class="sa-label mb-1 block">Corpo da Notícia/Texto</label>
                <input id="body_content" type="hidden" name="body" value="{{ old('body', $content->body) }}">
                <trix-editor input="body_content"
                    class="bg-white min-h-[300px] prose max-w-none border-gray-300 rounded-md shadow-sm"></trix-editor>
            </div>

            <div class="flex items-center bg-blue-50 p-4 rounded-lg border border-blue-100">
                <input type="hidden" name="published" value="0">
                <input type="checkbox" name="published" id="published" value="1"
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 h-5 w-5" {{ old('published', $content->published) ? 'checked' : '' }}>
                <label for="published" class="ml-3 text-sm font-medium text-blue-900 cursor-pointer">
                    Conteúdo Publicado (Visível no portal público)
                </label>
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-gray-200 mt-6">
                <a href="{{ route('admin.portal') }}" class="sa-btn-secondary px-6">Cancelar</a>
                <button type="submit" class="sa-btn-primary px-6">Atualizar Conteúdo</button>
            </div>
        </form>
    </div>

    {{-- Script de controle de interface e Upload do Trix --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            toggleFields();
        });

        function toggleFields() {
            const type = document.getElementById('contentType').value;
            const container = document.getElementById('coverImageContainer');
            if (type === 'Notícia') {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }

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

                    // A MÁGICA ESTÁ AQUI: Forçamos o Trix a gravar a imagem com 250px
                    // diretamente no elemento HTML, ignorando o CSS!
                    attachment.setAttributes({
                        url: response.url,
                        href: response.url,
                        width: 250
                    });
                }
            }
            xhr.send(form);
        }
    </script>
</x-app-layout>