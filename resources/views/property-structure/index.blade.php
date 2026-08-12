@extends('layouts.app')

@section('title', 'Quản lý tầng & phòng — '.config('app.name'))

@section('content')
    <nav class="breadcrumbs" aria-label="Điều hướng">
        <a href="{{ route('rooms.index') }}">← Danh sách phòng</a>
    </nav>

    <header class="page-header structure-header">
        <div>
            <p class="eyebrow">Cấu trúc nhà trọ</p>
            <h1>Tầng & phòng</h1>
            <p class="muted property-summary">{{ $property->name }} · Không xóa dữ liệu đã có lịch sử.</p>
        </div>
        <a class="button button-primary" href="{{ route('property-structure.floors.create') }}">Thêm tầng</a>
    </header>

    @if (session('status'))
        <div class="notice" role="status">{{ session('status') }}</div>
    @endif

    <div class="notice notice-warning">
        Phòng chưa có chỉ số/hóa đơn có thể xóa trong màn hình sửa phòng. Nếu đã có lịch sử, hãy chuyển sang <strong>Ngừng sử dụng</strong>.
    </div>

    <div class="structure-floor-list">
        @forelse ($property->floors as $floor)
            <details class="card structure-floor-card floor-accordion @if(! $floor->is_active) is-inactive @endif" id="floor-{{ $floor->id }}">
                <summary class="structure-floor-heading" aria-controls="structure-floor-rooms-{{ $floor->id }}">
                    <div>
                        <p class="eyebrow">Mã tầng {{ $floor->code }}</p>
                        <h2>{{ $floor->name }}</h2>
                        <p class="muted">Thứ tự {{ $floor->sort_order }} · {{ $floor->is_active ? 'Đang sử dụng' : 'Đã ngừng sử dụng' }}</p>
                    </div>
                    <span class="floor-summary-actions">
                        <span class="count-pill">{{ $floor->rooms->count() }} phòng</span>
                        <span class="floor-toggle" aria-hidden="true">
                            <span class="floor-toggle-label">Quản lý phòng</span>
                            <svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                        </span>
                    </span>
                </summary>

                <div class="structure-room-list" id="structure-floor-rooms-{{ $floor->id }}">
                    @forelse ($floor->rooms as $room)
                        <article class="structure-room-row @if(! $room->is_active) is-inactive @endif">
                            <div>
                                <strong>Phòng {{ $room->room_number }}</strong>
                                <span class="structure-room-meta">
                                    @if ($room->status->usesWalkOrder())
                                        Thứ tự {{ $room->sort_order }} ·
                                    @endif
                                    {{ $room->status->label() }}
                                </span>
                            </div>
                            <div class="structure-room-actions">
                                <a class="button button-secondary button-small" href="{{ route('property-structure.rooms.edit', $room) }}">Sửa phòng</a>
                                <a class="button button-secondary button-small" href="{{ route('rooms.settings.edit', $room) }}">Giá & phí</a>
                            </div>
                        </article>
                    @empty
                        <p class="empty-inline">Tầng này chưa có phòng.</p>
                    @endforelse
                </div>

                <footer class="structure-floor-footer">
                    <a class="button button-secondary" href="{{ route('property-structure.floors.edit', $floor) }}">Sửa tầng</a>
                    <a class="button button-primary" href="{{ route('property-structure.rooms.create', $floor) }}">Thêm phòng vào {{ $floor->name }}</a>
                </footer>
            </details>
        @empty
            <div class="card empty-state">
                <h2>Chưa có tầng nào</h2>
                <p class="muted">Thêm tầng đầu tiên rồi tạo các phòng thực tế.</p>
            </div>
        @endforelse
    </div>
@endsection
