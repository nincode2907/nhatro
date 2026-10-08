@extends('layouts.app')
@section('title', 'Giá mặc định — '.config('app.name'))
@section('content')
    <nav class="breadcrumbs" aria-label="Điều hướng"><a href="{{ route('rooms.index') }}">← Danh sách phòng</a></nav>
    <header class="page-header"><div><p class="eyebrow">Cài đặt nhà trọ</p><h1>Giá mặc định</h1><p class="muted">Áp dụng khi thêm phòng mới. Sau khi tạo, bạn có thể sửa giá riêng từng phòng.</p></div></header>
    @if (session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="notice notice-error" role="alert">Vui lòng kiểm tra các mức giá bên dưới.</div>@endif
    <form class="settings-form" method="POST" action="{{ route('default-prices.update') }}">
        @csrf
        @method('PUT')
        <section class="card form-section">
            <h2>Giá cho phòng mới</h2>
            <div class="form-grid two-columns">
                @foreach ([
                    'rent_amount' => ['Tiền phòng', 'tháng'],
                    'electricity_unit_price' => ['Giá điện', 'kWh'],
                    'water_unit_price' => ['Giá nước', 'm³'],
                    'vehicle_amount' => ['Phí xe', 'tháng'],
                    'garbage_amount' => ['Phí rác', 'tháng'],
                    'cable_amount' => ['Internet', 'tháng'],
                ] as $field => [$label, $unit])
                    <div class="field">
                        <label for="{{ $field }}">{{ $label }}</label>
                        <div class="input-with-suffix"><x-money-input :name="$field" :value="old($field, $prices[$field])" /><span>đ / {{ $unit }}</span></div>
                        @error($field)<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </section>
        <div class="sticky-actions"><a class="button button-secondary" href="{{ route('rooms.index') }}">Quay lại</a><button class="button button-primary" type="submit">Lưu giá mặc định</button></div>
    </form>
@endsection
