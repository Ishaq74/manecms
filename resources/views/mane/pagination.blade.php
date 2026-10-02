{{--
    Pagination for Livewire paginators (WithPagination). Works with both
    length-aware and simple paginators; never needs the total of a simple one.
--}}
@props([
    'paginator',
])

@php
    /** @var Illuminate\Contracts\Pagination\Paginator $paginator */
    $pageName = $paginator->getPageName();
    $lengthAware = $paginator instanceof Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp

@if ($paginator->hasPages())
    <nav {{ $attributes->merge(['aria-label' => __('Page navigation')])->class('flex flex-wrap items-center justify-between gap-3 text-sm text-fg-muted') }}>
        <p>
            @if ($lengthAware)
                {{ __('Showing :first to :last of :total results', ['first' => $paginator->firstItem(), 'last' => $paginator->lastItem(), 'total' => $paginator->total()]) }}
            @else
                {{ __('Page :page', ['page' => $paginator->currentPage()]) }}
            @endif
        </p>

        <div class="flex items-center gap-2">
            <x-mane::button
                variant="secondary"
                size="sm"
                icon="chevron-left"
                class="[&_svg]:rtl:rotate-180"
                :text="__('Previous')"
                :disabled="$paginator->onFirstPage()"
                wire:click="previousPage('{{ $pageName }}')"
                data-test="pagination-previous"
            />

            @if ($lengthAware)
                <span class="px-2 font-medium text-fg">
                    {{ __(':page / :pages', ['page' => $paginator->currentPage(), 'pages' => $paginator->lastPage()]) }}
                </span>
            @endif

            <x-mane::button
                variant="secondary"
                size="sm"
                icon="chevron-right"
                icon-position="end"
                class="[&_svg]:rtl:rotate-180"
                :text="__('Next')"
                :disabled="! $paginator->hasMorePages()"
                wire:click="nextPage('{{ $pageName }}')"
                data-test="pagination-next"
            />
        </div>
    </nav>
@endif
