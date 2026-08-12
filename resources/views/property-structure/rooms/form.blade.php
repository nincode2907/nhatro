@extends('layouts.app')

@php
    $editing = $room !== null;
    $selectedStatus = old('status', $room?->status->value ?? \App\Enums\RoomStatus::Occupied->value);
    $usesWalkOrder = $selectedStatus === \App\Enums\RoomStatus::Occupied->value;
@endphp

@section('title', ($editing ? 'Sửa phòng '.$room->room_number : 'Thêm phòng').' — '.config('app.name'))

@section('content')
    <nav class="breadcrumbs" aria-label="Điều hướng">
        <a href="{{ route('property-structure.index') }}">← Tầng & phòng</a>
    </nav>

    <header class="page-header">
        <div>
            <p class="eyebrow">Cấu trúc nhà trọ</p>
            <h1>{{ $editing ? 'Sửa phòng '.$room->room_number : 'Thêm phòng vào '.$floor->name }}</h1>
            <p class="muted property-summary">Số phòng chỉ cần duy nhất trong cùng một tầng.</p>
        </div>
    </header>

    <form class="card structure-form" method="POST"
          action="{{ $editing ? route('property-structure.rooms.update', $room) : route('property-structure.rooms.store', $floor) }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="form-grid two-columns" data-walk-order data-next-walk-order="{{ $suggestedSortOrder }}">
            @if ($editing)
                <div class="field">
                    <label for="floor_id">Tầng</label>
                    <select id="floor_id" name="floor_id" required>
                        @foreach ($floors as $availableFloor)
                            <option value="{{ $availableFloor->id }}" @selected((int) old('floor_id', $room->floor_id) === $availableFloor->id)>
                                {{ $availableFloor->name }}{{ $availableFloor->is_active ? '' : ' — ngừng sử dụng' }}
                            </option>
                        @endforeach
                    </select>
                    @error('floor_id') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="field">
                <label for="room_number">Số phòng</label>
                <input id="room_number" name="room_number" type="text" maxlength="30" required autofocus
                       value="{{ old('room_number', $room?->room_number) }}" aria-invalid="{{ $errors->has('room_number') ? 'true' : 'false' }}">
                @error('room_number') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field" data-walk-order-field @unless($usesWalkOrder) hidden @endunless>
                <label for="sort_order">Thứ tự đi thực tế</label>
                <input id="sort_order" name="sort_order" type="number" inputmode="numeric" min="1" max="10000"
                       value="{{ old('sort_order', $suggestedSortOrder) }}" aria-invalid="{{ $errors->has('sort_order') ? 'true' : 'false' }}"
                       @disabled(! $usesWalkOrder) @required($usesWalkOrder)>
                <p class="field-help" data-walk-order-help>
                    {{ $usesWalkOrder ? 'Đổi vị trí sẽ tự đẩy các phòng ở giữa xuống một bậc.' : 'Phòng này không tham gia thứ tự đi thực tế.' }}
                </p>
                @error('sort_order') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="status">Trạng thái</label>
                <select id="status" name="status" required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                @error('status') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        @unless ($editing)
            <div class="notice notice-warning">
                Phòng mới được tạo với giá và phí bằng 0. Sau bước này hệ thống sẽ mở trang cài đặt để bạn nhập giá thực tế.
            </div>
        @endunless

        @if ($editing)
            <p class="field-help">Chọn “Ngừng sử dụng” để ẩn phòng khỏi luồng ghi số mà vẫn giữ lịch sử.</p>
        @endif

        <div class="structure-form-actions">
            <a class="button button-secondary" href="{{ route('property-structure.index') }}">Hủy</a>
            <button class="button button-primary" type="submit">{{ $editing ? 'Lưu phòng' : 'Tạo phòng & nhập giá' }}</button>
        </div>
    </form>

    @if ($editing)
        <section class="card danger-zone" aria-labelledby="delete-room-title">
            <div>
                <h2 id="delete-room-title">Xóa phòng {{ $room->room_number }}</h2>
                <p>Chỉ xóa được phòng chưa có chỉ số hoặc hóa đơn. Thao tác này cũng xóa cấu hình giá của phòng.</p>
            </div>
            <form method="POST" action="{{ route('property-structure.rooms.destroy', $room) }}"
                  data-confirm-message="Xóa vĩnh viễn phòng {{ $room->room_number }}? Thao tác này không thể hoàn tác.">
                @csrf
                @method('DELETE')
                <button class="button button-danger" type="submit">Xóa phòng</button>
            </form>
        </section>

        @error('room')
            <div class="notice notice-error" role="alert">{{ $message }}</div>
        @enderror
    @endif
@endsection
