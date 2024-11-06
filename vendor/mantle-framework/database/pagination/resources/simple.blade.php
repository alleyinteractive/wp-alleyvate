@if ($paginator->has_pages())
	<nav>
		<ul class="pagination">
			{{-- Previous Page Link --}}
			@if ($paginator->on_first_page())
				<li class="disabled" aria-disabled="true"><span>{{ __('Previous', 'mantle') }}</span></li>
			@else
				<li><a href="{{ $paginator->previous_url() }}" rel="prev">{{ __('Previous', 'mantle') }}</a></li>
			@endif

			{{-- Next Page Link --}}
			@if ($paginator->has_more())
				<li><a href="{{ $paginator->next_url() }}" rel="next">{{ __('Next', 'mantle') }}</a></li>
			@else
				<li class="disabled" aria-disabled="true"><span>{{ __('Next', 'mantle') }}</span></li>
			@endif
		</ul>
	</nav>
@endif
