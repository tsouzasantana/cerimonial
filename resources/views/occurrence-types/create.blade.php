<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo tipo de ocorrência</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('occurrence-types.store') }}">
                    @csrf
                    @include('occurrence-types._form')

                    <div class="mt-6 flex justify-end gap-3">
                        <a href="{{ route('occurrence-types.index') }}" class="text-sm text-gray-600 hover:text-gray-900 self-center">Cancelar</a>
                        <x-primary-button>Salvar</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
