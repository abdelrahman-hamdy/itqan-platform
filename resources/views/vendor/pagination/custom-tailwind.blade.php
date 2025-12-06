@if ($paginator->hasPages())
    <nav role="navigation" aria-label="التنقل بين الصفحات" class="flex items-center justify-between" dir="rtl">
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center justify-center px-5 py-2.5 h-11 text-sm font-medium text-gray-400 bg-gray-50 border border-gray-300 cursor-not-allowed leading-5 rounded-lg opacity-60 shadow-sm">
                    السابق
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center justify-center px-5 py-2.5 h-11 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-lg hover:bg-amber-50 hover:text-amber-600 hover:border-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 active:bg-amber-100 shadow-sm transition-all duration-150">
                    السابق
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center justify-center px-5 py-2.5 h-11 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-lg hover:bg-amber-50 hover:text-amber-600 hover:border-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 active:bg-amber-100 shadow-sm transition-all duration-150">
                    التالي
                </a>
            @else
                <span class="relative inline-flex items-center justify-center px-5 py-2.5 h-11 text-sm font-medium text-gray-400 bg-gray-50 border border-gray-300 cursor-not-allowed leading-5 rounded-lg opacity-60 shadow-sm">
                    التالي
                </span>
            @endif
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700 leading-5">
                    عرض
                    @if ($paginator->firstItem())
                        <span class="font-medium">{{ $paginator->firstItem() }}</span>
                        إلى
                        <span class="font-medium">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    من
                    <span class="font-medium">{{ $paginator->total() }}</span>
                    نتيجة
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex shadow-sm rounded-md">
                    {{-- Previous Page Link (shown first in RTL) --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="السابق">
                            <span class="relative inline-flex items-center justify-center px-4 py-2 h-10 text-sm font-medium text-gray-400 bg-gray-50 border border-gray-300 cursor-not-allowed rounded-r-md leading-5 opacity-60" aria-hidden="true">
                                السابق
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center justify-center px-4 py-2 h-10 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md leading-5 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-300 focus:z-10 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 active:bg-amber-100 transition-all duration-150" aria-label="السابق">
                            السابق
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative inline-flex items-center justify-center px-4 py-2 -ml-px h-10 text-sm font-medium text-gray-400 bg-white border border-gray-300 cursor-default leading-5">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="relative inline-flex items-center justify-center px-4 py-2 -ml-px h-10 text-base font-bold text-white bg-amber-500 border border-amber-500 cursor-default leading-5 shadow-md">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="relative inline-flex items-center justify-center px-4 py-2 -ml-px h-10 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-300 focus:z-10 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 active:bg-amber-100 transition-all duration-150" aria-label="صفحة {{ $page }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link (shown last in RTL) --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center justify-center px-4 py-2 -ml-px h-10 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md leading-5 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-300 focus:z-10 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 active:bg-amber-100 transition-all duration-150" aria-label="التالي">
                            التالي
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="التالي">
                            <span class="relative inline-flex items-center justify-center px-4 py-2 -ml-px h-10 text-sm font-medium text-gray-400 bg-gray-50 border border-gray-300 cursor-not-allowed rounded-l-md leading-5 opacity-60" aria-hidden="true">
                                التالي
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
