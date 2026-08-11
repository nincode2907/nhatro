@extends('layouts.app')

@section('title', 'Phòng '.$room->room_number.' — Cài đặt')

@section('content')
    <nav class="breadcrumbs" aria-label="Điều hướng">
        <a href="{{ route('rooms.index') }}">← Danh sách phòng</a>
    </nav>

    <header class="page-header settings-header">
        <div>
            <p class="eyebrow">{{ $room->floor->name }}</p>
            <h1>Phòng {{ $room->room_number }}</h1>
            <p class="muted">Cài đặt phòng, giá và đồng hồ</p>
        </div>
        <span class="status-badge status-{{ strtolower($room->status->value) }}">
            <span aria-hidden="true">{{ $room->status->symbol() }}</span>
            {{ $room->status->label() }}
        </span>
    </header>

    @if (session('status'))
        <div class="notice" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="notice notice-error" role="alert">
            <strong>Chưa thể lưu cấu hình.</strong>
            Vui lòng kiểm tra các trường được đánh dấu bên dưới.
        </div>
    @endif

    <form class="settings-form" method="POST" action="{{ route('rooms.settings.update', $room) }}">
        @csrf
        @method('PUT')

        <section class="card form-section" aria-labelledby="room-section-title">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Phòng</p>
                    <h2 id="room-section-title">Trạng thái & thứ tự</h2>
                </div>
            </div>

            <div class="form-grid two-columns">
                <div class="field">
                    <label for="status">Trạng thái</label>
                    <select id="status" name="status" required>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $room->status->value) === $status->value)>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="sort_order">Thứ tự đi</label>
                    <input id="sort_order" name="sort_order" type="number" inputmode="numeric" min="1" step="1"
                           value="{{ old('sort_order', $room->sort_order) }}" required>
                    <p class="field-help">Thứ tự di chuyển thực tế, không nhất thiết theo số phòng.</p>
                    @error('sort_order')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="field">
                <label for="room_note">Ghi chú phòng</label>
                <textarea id="room_note" name="room_note" rows="3">{{ old('room_note', $room->note) }}</textarea>
                @error('room_note')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </section>

        <section class="card form-section" aria-labelledby="price-section-title">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Giá hiện tại</p>
                    <h2 id="price-section-title">Tiền phòng & phí cố định</h2>
                </div>
            </div>

            @php
                $moneyFields = [
                    'rent_amount' => ['Tiền phòng', 'mỗi tháng'],
                    'vehicle_amount' => ['Xe', 'mỗi tháng'],
                    'garbage_amount' => ['Rác', 'mỗi tháng'],
                    'cable_amount' => ['Cáp / Internet', 'mỗi tháng'],
                    'other_amount' => ['Khoản cố định khác', 'mỗi tháng'],
                ];
            @endphp

            <div class="form-grid two-columns">
                @foreach ($moneyFields as $field => [$label, $suffix])
                    <div class="field">
                        <label for="{{ $field }}">{{ $label }}</label>
                        <div class="input-with-suffix">
                            <input id="{{ $field }}" name="{{ $field }}" type="number" inputmode="numeric" min="0" step="1"
                                   value="{{ old($field, $settings->{$field}) }}" required>
                            <span>đ / {{ $suffix }}</span>
                        </div>
                        <p class="field-help">Hiện tại: {{ number_format((int) old($field, $settings->{$field}), 0, ',', '.') }} đ</p>
                        @error($field)<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card form-section" aria-labelledby="meter-section-title">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Đồng hồ</p>
                    <h2 id="meter-section-title">Điện & nước</h2>
                </div>
            </div>

            <div class="meter-grid">
                <div class="meter-card">
                    <div class="field">
                        <label for="electricity_unit_price">Đơn giá điện</label>
                        <div class="input-with-suffix">
                            <input id="electricity_unit_price" name="electricity_unit_price" type="number" inputmode="numeric"
                                   min="0" step="1" value="{{ old('electricity_unit_price', $settings->electricity_unit_price) }}" required>
                            <span>đ / kWh</span>
                        </div>
                        @error('electricity_unit_price')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <input name="electricity_enabled" type="hidden" value="0">
                    <label class="checkbox-row" for="electricity_enabled">
                        <input id="electricity_enabled" name="electricity_enabled" type="checkbox" value="1"
                               @checked((bool) old('electricity_enabled', $settings->electricity_enabled))>
                        <span>Có đồng hồ điện</span>
                    </label>
                    @error('electricity_enabled')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="meter-card">
                    <div class="field">
                        <label for="water_unit_price">Đơn giá nước</label>
                        <div class="input-with-suffix">
                            <input id="water_unit_price" name="water_unit_price" type="number" inputmode="numeric"
                                   min="0" step="1" value="{{ old('water_unit_price', $settings->water_unit_price) }}" required>
                            <span>đ / m³</span>
                        </div>
                        @error('water_unit_price')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <input name="water_enabled" type="hidden" value="0">
                    <label class="checkbox-row" for="water_enabled">
                        <input id="water_enabled" name="water_enabled" type="checkbox" value="1"
                               @checked((bool) old('water_enabled', $settings->water_enabled))>
                        <span>Có đồng hồ nước</span>
                    </label>
                    @error('water_enabled')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="field">
                <label for="settings_note">Ghi chú cấu hình</label>
                <textarea id="settings_note" name="settings_note" rows="3">{{ old('settings_note', $settings->note) }}</textarea>
                @error('settings_note')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </section>

        <div class="sticky-actions">
            <a class="button button-secondary" href="{{ route('rooms.index') }}">Hủy</a>
            <button class="button button-primary" type="submit">Lưu cấu hình</button>
        </div>
    </form>
@endsection
