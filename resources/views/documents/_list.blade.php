@php
    $isPublic = $isPublic ?? false;
    $token = $token ?? null;
    $downloadUrl = fn ($document) => $isPublic
        ? route('public.documents.download', ['token' => $token, 'document' => $document])
        : route('documents.download', $document);
@endphp

<div class="border border-gray-200 rounded-lg p-4">
    <h4 class="text-sm font-medium text-gray-900 mb-3">Documentos enviados</h4>
    @if ($documents->isEmpty())
        <p class="text-sm text-gray-500">Nenhum documento cadastrado.</p>
    @else
        <ul class="divide-y divide-gray-100">
            @foreach ($documents as $document)
                <li class="py-2 flex justify-between items-center gap-3">
                    <div class="min-w-0">
                        @if ($document->isLink())
                            <a href="{{ $document->url }}" target="_blank" rel="noopener" class="text-brand-600 hover:text-brand-800">
                                {{ $document->title }} ↗
                            </a>
                        @else
                            <a href="{{ $downloadUrl($document) }}" class="text-brand-600 hover:text-brand-800">
                                {{ $document->title }}
                            </a>
                        @endif
                        <span class="text-xs text-gray-500">({{ $document->documentType->name }})</span>
                        <span class="text-xs text-gray-400">&middot; enviado por {{ $document->uploaded_by_type === 'client' ? 'cliente' : ($document->uploader->name ?? 'admin') }}</span>
                    </div>
                    @unless ($isPublic)
                        <div class="flex items-center gap-3 shrink-0">
                            <form method="POST" action="{{ route('documents.send-email', $document) }}">
                                @csrf
                                <button type="submit" class="text-brand-600 hover:text-brand-800 text-sm">Enviar e-mail</button>
                            </form>
                            <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Inativar este documento?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Inativar</button>
                            </form>
                        </div>
                    @endunless
                </li>
            @endforeach
        </ul>
    @endif
</div>
