<div class="driver-level-grid mt-2">
    @forelse($levels as $level)
        <div class="card border analytical_data">
            <div class="card-body justify-content-around d-flex flex-row gap-3">
                <div>
                    <h6 class="text-primary mb-10">{{ $level->name }}</h6>
                    <div class="fs-10 fw-semibold text-muted mb-1">{{ translate('driver') }}</div>
                    <div class="d-flex flex-wrap align-items-end gap-2">
                        @php($level_drivers = $level->users->count())
                        <h3 class="fs-27">{{ $level_drivers }}</h3>
                    </div>
                </div>

                <div class="d-flex align-items-start">
                    <img loading="lazy" class="custom-box-size"
                        src="{{ onErrorImage(
                            $level?->image,
                            asset('storage/app/public/driver/level') . '/' . $level?->image,
                            asset('public/assets/admin-module/img/media/level5.png'),
                            'driver/level/',
                        ) }}"
                        alt="" style="--size: 45px">
                </div>
            </div>
        </div>
    @empty
        <h4 class="text-primary">No Data Available</h4>
    @endforelse
</div>
