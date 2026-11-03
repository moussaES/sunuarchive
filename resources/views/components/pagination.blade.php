@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" style="display: flex; align-items: center; justify-content: center; gap: 6px; flex-wrap: wrap;">
        {{-- Flèche Précédent (<) --}}
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" aria-label="Page précédente" class="btn btn-secondary" style="min-height: 38px; padding: 6px 12px; font-weight: 800; cursor: not-allowed;">
                &lt;
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Page précédente" class="btn btn-secondary" style="min-height: 38px; padding: 6px 12px; font-weight: 800; text-decoration: none;">
                &lt;
            </a>
        @endif

        {{-- Numéros de pages (1 2 3 ...) --}}
        @foreach ($elements as $element)
            {{-- Séparateur "..." --}}
            @if (is_string($element))
                <span aria-disabled="true" style="padding: 6px 10px; font-weight: 800;">{{ $element }}</span>
            @endif

            {{-- Tableau de liens numérotés --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="btn btn-primary" style="min-height: 38px; padding: 6px 14px; font-weight: 900; cursor: default;">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}" aria-label="Aller à la page {{ $page }}" class="btn btn-secondary" style="min-height: 38px; padding: 6px 14px; font-weight: 700; text-decoration: none;">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Flèche Suivant (>) --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Page suivante" class="btn btn-secondary" style="min-height: 38px; padding: 6px 12px; font-weight: 800; text-decoration: none;">
                &gt;
            </a>
        @else
            <span aria-disabled="true" aria-label="Page suivante" class="btn btn-secondary" style="min-height: 38px; padding: 6px 12px; font-weight: 800; cursor: not-allowed;">
                &gt;
            </span>
        @endif
    </nav>
@endif
