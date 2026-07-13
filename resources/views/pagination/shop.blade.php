@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="페이지 이동">
        @if ($paginator->onFirstPage())
            <span class="disabled"><span aria-hidden="true">‹</span></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="이전">‹</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="dots">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="active"><span aria-current="page">{{ $page }}</span></span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="다음">›</a>
        @else
            <span class="disabled"><span aria-hidden="true">›</span></span>
        @endif
    </nav>
@endif
