@extends("layouts.main")

@section("content")
    {{-- PHP CODE BLOCK IF $RESULTS VALUE IS NOT NULL --}}
    @if ($results !== null)
        @php
            $filtered_found_value = array_filter($results, fn($item) => $item !== []);
            $count_found_value = 0;

            // Assign NULL if all result fields have at least one value among them
            if (count($filtered_found_value) !== 0) {
                $results_keys = array_keys($filtered_found_value);
                for ($i = 0; $i < count($filtered_found_value); $i++) {
                    $count_found_value += count($filtered_found_value[$results_keys[$i]]);
                }
            }

            // Merge all results or flatting the values
            $results_final_data = array_merge(...array_values($results));
        @endphp
    @endif

    <div class="app-search__results">
        <div class="app-search__results_header" aria-labelledby="app-search__results_header_title" aria-describedby="app-search__results_header_summary">
            <h1 class="app-search__results_header_title" id="app-search__results_header_title">Searching results for search query "<mark>{{ request('search') }}</mark>"</h1>
            <p class="app-search__results_header_summary" id="app-search__results_header_summary">Found <strong>{{ $results !== null ? $count_found_value : 0 }} results</strong> from <strong>{{ $results !== null ? count($results) : 0 }} sources</strong>{!! $results !== null ? " (<span style='font-style: italic'>".implode(", ", array_keys($results))."</span>)" : "" !!}.</p>
        </div>

        <div class="app-search__results_content" id="app-search__results_content" role="list">
            @if ($results !== null)
                @foreach ($results_final_data as $result)
                    <div class="app-search__results_item" role="listitem">
                        <div class="item-content">
                            <div class="item-icon">
                                <i class="bi bi-link-45deg"></i>
                            </div>
                            <div class="item-details">
                                <div class="item-header">
                                    <span class="item-badge {{ "badge-".strtolower($result['type']) }}">{{ $result['type'] }}</span>
                                </div>
                                <a href="{{ $result["url"] }}" role="button" class="item-title">{{ $result["title"] }}</a>
                                <p class="item-url">-- {{ $result["url"] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="app-search__results_empty" role="listitem">
                    <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <h3>No results found for search query '<mark>{{ request('search') }}</mark>'</h3>
                    <p>Try searching for something else.</p>
                </div>
            @endif
        </div>
    </div>
@endsection