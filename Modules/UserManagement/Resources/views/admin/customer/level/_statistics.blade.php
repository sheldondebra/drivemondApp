<div class="auto-items gap-3 mt-2">
    @forelse($levels as $level)
        <div class="card text-center">
            <div class="card-body">
                <img src="{{ onErrorImage(
                    $level?->image,
                    asset('storage/app/public/customer/level') . '/' . $level?->image,
                    asset('public/assets/admin-module/img/media/level5.png'),
                    'customer/level/',
                ) }}"
                    class="dark-support mb-3 custom-box-size" alt="" style="--size: 48px">
                <h3 class="fs-21 text-primary mb-2">{{ $level->users->count() ?? 0 }}</h3>
                <div class="fw-semibold">{{ $level->name }}</div>
            </div>
        </div>
    @empty
        <h4 class="text-primary">No Data Available</h4>
    @endforelse
</div>
