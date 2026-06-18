<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Importar Estoque (Custos)
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">
                <h1 class="text-2xl font-bold mb-6 text-gray-800">Importação Analítica de Estoque</h1>
                
                <p class="text-gray-600 mb-6 text-sm">
                    Faça o upload do arquivo CSV "Entrada de produtos - Analítico". 
                    O sistema lerá os lotes e valores de entrada e registrará o custo total e unitário no estoque.
                </p>

                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                <form action="{{ route('import.stock.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="file" class="block text-sm font-medium text-gray-700 mb-2">Arquivo CSV de Entradas</label>
                        <input type="file" name="file" id="file" accept=".csv" required
                            class="block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-full file:border-0
                            file:text-sm file:font-semibold
                            file:bg-blue-50 file:text-blue-700
                            hover:file:bg-blue-100
                            border border-gray-300 rounded-lg p-2
                            focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('file')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                            Importar Dados
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
