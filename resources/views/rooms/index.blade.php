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
        @if ($property)
            <div class="header-actions"><a class="button button-primary" href="{{ route('property-structure.index') }}">Quản lý tầng & phòng</a>
            <a class="button button-secondary" href="{{ route('default-prices.edit') }}">Giá mặc định</a></div>
        @endif
    </header>

    @if (! $property)
        <div class="card empty-state">
            <h2>Chưa có dữ liệu nhà trọ</h2>
            <p>Chạy demo seeder để tạo cấu trúc ban đầu, sau đó kiểm tra lại dữ liệu thực tế.</p>
            <code>php artisan db:seed --class=DemoPropertySeeder</code>
        </div>
    @else
        <div class="floor-list">
            @forelse ($property->floors as $floor)
                <details class="card floor-card floor-accordion">
                    <summary class="floor-header" aria-controls="floor-rooms-{{ $floor->id }}">
                        <div>
                            <p class="eyebrow">Tầng {{ $floor->code }}</p>
                            <h2>{{ $floor->name }}</h2>
                        </div>
                        <span class="floor-summary-actions">
                            <span class="count-pill">{{ $floor->rooms->count() }} phòng</span>
                            <span class="floor-toggle" aria-hidden="true">
                                <span class="floor-toggle-label">Xem phòng</span>
                                <svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                            </span>
                        </span>
                    </summary>

                    <div class="room-list" id="floor-rooms-{{ $floor->id }}">
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
                                    @if ($room->status->usesWalkOrder())
                                        <span class="sort-label">Thứ tự {{ $room->sort_order }}</span>
                                    @endif
                                    <a class="button button-secondary" href="{{ route('rooms.settings.edit', $room) }}">
                                        Cài đặt
                                    </a>
                                </div>
                            </article>
                        @empty
                            <p class="empty-inline">Tầng này chưa có phòng.</p>
                        @endforelse
                    </div>
                </details>
            @empty
                <div class="card empty-state">Chưa có tầng nào.</div>
            @endforelse
        </div>
    @endif
@endsection
