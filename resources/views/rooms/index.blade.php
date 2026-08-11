@extends('layouts.app')

@section('title', 'Phòng — '.config('app.name'))

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Phòng & cài đặt</p>
            <h1>Danh sách phòng</h1>
            @if ($property)
                <p class="muted property-summary">
                    {{ $property->name }}
                    @if ($property->address)
                        <span aria-hidden="true">·</span> {{ $property->address }}
                    @endif
                </p>
            @endif
        </div>
    </header>

    @if (! $property)
        <div class="card empty-state">
            <h2>Chưa có dữ liệu nhà trọ</h2>
            <p>Chạy demo seeder để tạo cấu trúc ban đầu, sau đó kiểm tra lại dữ liệu thực tế.</p>
            <code>php artisan db:seed --class=DemoPropertySeeder</code>
        </div>
    @else
        <div class="notice notice-warning">
            <strong>Lưu ý:</strong> danh sách seed ban đầu chỉ là demo. Hãy xác minh từng phòng và mức giá.
        </div>

        <div class="floor-list">
            @forelse ($property->floors as $floor)
                <section class="card floor-card" aria-labelledby="floor-{{ $floor->id }}">
                    <header class="floor-header">
                        <div>
                            <p class="eyebrow">Tầng {{ $floor->code }}</p>
                            <h2 id="floor-{{ $floor->id }}">{{ $floor->name }}</h2>
                        </div>
                        <span class="count-pill">{{ $floor->rooms->count() }} phòng</span>
                    </header>

                    <div class="room-list">
                        @forelse ($floor->rooms as $room)
                            <article class="room-row @if(! $room->is_active) is-inactive @endif">
                                <div class="room-identity">
                                    <strong>Phòng {{ $room->room_number }}</strong>
                                    <span class="status-badge status-{{ strtolower($room->status->value) }}">
                                        <span aria-hidden="true">{{ $room->status->symbol() }}</span>
                                        {{ $room->status->label() }}
                                    </span>
                                </div>
                                <div class="room-actions">
                                    <span class="sort-label">Thứ tự {{ $room->sort_order }}</span>
                                    <a class="button button-secondary" href="{{ route('rooms.settings.edit', $room) }}">
                                        Cài đặt
                                    </a>
                                </div>
                            </article>
                        @empty
                            <p class="empty-inline">Tầng này chưa có phòng.</p>
                        @endforelse
                    </div>
                </section>
            @empty
                <div class="card empty-state">Chưa có tầng nào.</div>
            @endforelse
        </div>
    @endif
@endsection
