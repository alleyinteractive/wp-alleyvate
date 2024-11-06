@if ($paginator->has_pages())
	<nav>
		<ul class="pagination">
			{{-- Previous Page Link --}}
			@if ($paginator->on_first_page())
				<li class="disabled" aria-disabled="true"><span>&lsaquo;</span></li>
			@else
				<li><a href="{{ $paginator->previous_url() }}" rel="prev">&lsaquo;</a></li>
			@endif

			@foreach ($paginator->elements() as $element)
				{{-- "Three Dots" Separator --}}
				@if (is_string($element))
					<li class="disabled" aria-disabled="true"><span>{{ $element }}</span></li>
				@endif

				{{-- Array Of Links --}}
				@if (is_array($element))
					@foreach ($element as $page => $url)
						@if ($page == $paginator->current_page())
							<li class="active" aria-current="page"><span>{{ $page }}</span></li>
						@else
							<li><a href="{{ $url }}">{{ $page }}</a></li>
						@endif
					@endforeach
				@endif
			@endforeach

			{{-- Next Page Link --}}
			@if ($paginator->has_more())
				<li><a href="{{ $paginator->next_url() }}" rel="next">&rsaquo;</a></li>
			@else
				<li class="disabled" aria-disabled="true"><span>&rsaquo;</span></li>
			@endif
		</ul>
	</nav>
@endif
