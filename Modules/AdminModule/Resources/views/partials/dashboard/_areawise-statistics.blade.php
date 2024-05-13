@php($colors = [])
<!-- Area wise Trip Statistics -->
<div class="top-providers">
    <div class="max-h320-auto position-relative" data-trigger="scrollbar">
        <ul class="list-unstyled gap-4 d-flex flex-column">

            @forelse($trips as $key => $trip)
            @if ($trip->zone)
            <li>
                <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-2 fs-13">
                    <div>{{$trip->zone?->name}}</div>
                    <div dir="ltr">{{$volume = number_format(($trip->total_records/$totalCount)*100),1}}
                        % {{ translate('trip_volume')}}</div>
                </div>
                @if($volume > -1 && $volume < 33)
                    @php($color = '--bs-danger')
                @elseif($volume >= 33 && $volume < 66)
                    @php($color = '--bs-secondary')
                @else
                    @php($color = '--bs-primary')
                @endif
                <div class="progress">
                    <div class="progress-bar" role="progressbar"
                         style="width: {{$volume}}%; background-color: var({{$color}})" aria-valuenow="12"
                         aria-valuemin="0"
                         aria-valuemax="100" data-bs-toggle="tooltip" data-bs-html="true"
                         data-bs-title="<div>Acceptance rate - 78%</div> <div>Trip Volume - 12%</div>"></div>
                </div>
            </li>
            @endif

            @empty
            @endforelse
        </ul>
    </div>
</div>
<!-- End Area wise Trip Statistics -->
