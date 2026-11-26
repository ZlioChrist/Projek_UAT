@if ($paginator->hasPages())
    <nav class="wn-pagination" role="navigation" aria-label="Navigasi halaman">
        <div class="wn-pagination-controls">
            @if ($paginator->onFirstPage())
                <span class="wn-pagination-link is-disabled" aria-disabled="true" aria-label="Halaman sebelumnya">
                    <i class="ph ph-caret-left" aria-hidden="true"></i>
                </span>
            @else
                <a class="wn-pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">
                    <i class="ph ph-caret-left" aria-hidden="true"></i>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="wn-pagination-ellipsis" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="wn-pagination-link is-current" aria-current="page" aria-label="Halaman {{ $page }}">{{ $page }}</span>
                        @else
                            <a class="wn-pagination-link" href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="wn-pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">
                    <i class="ph ph-caret-right" aria-hidden="true"></i>
                </a>
            @else
                <span class="wn-pagination-link is-disabled" aria-disabled="true" aria-label="Halaman berikutnya">
                    <i class="ph ph-caret-right" aria-hidden="true"></i>
                </span>
            @endif
        </div>

        <p class="wn-pagination-summary">
            Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} hasil
        </p>
    </nav>
@endif
