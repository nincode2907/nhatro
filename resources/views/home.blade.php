@extends('layouts.app')

@section('title', 'Trang chủ — '.config('app.name'))

@section('content')
    <section class="hero" aria-labelledby="home-title">
        <p class="eyebrow">Hệ thống nội bộ</p>
        <h1 id="home-title">{{ config('app.name') }}</h1>
        <p class="hero-copy">Ghi chỉ số điện nước theo đúng thứ tự đi, ngay trên điện thoại.</p>
    </section>

    <section class="status-grid" aria-label="Trạng thái khởi tạo">
        <article class="card status-card priority-card">
            <span class="status-icon" aria-hidden="true">01</span>
            <h2>Ghi điện nước</h2>
            <p>Chọn tháng, đi theo từng tầng và dùng OK &amp; Tiếp để không bỏ sót phòng.</p>
            <a class="button button-primary card-action" href="{{ route('billing-periods.index') }}">Bắt đầu ghi chỉ số</a>
        </article>
        <article class="card status-card">
            <span class="status-icon" aria-hidden="true">02</span>
            <h2>Phòng & cài đặt</h2>
            <p>Xem phòng theo tầng, đổi trạng thái, thứ tự đi và các mức giá.</p>
            <a class="button button-primary card-action" href="{{ route('rooms.index') }}">Mở danh sách phòng</a>
        </article>
        <article class="card status-card">
            <span class="status-icon" aria-hidden="true">03</span>
            <h2>Bảo vệ đăng nhập</h2>
            <p>Toàn bộ trang nội bộ yêu cầu phiên quản trị.</p>
        </article>
    </section>

    <div class="notice" role="status">
        <strong>Dữ liệu khởi tạo là dữ liệu demo.</strong>
        Hãy xác minh số phòng, thứ tự và giá thực tế trước khi sử dụng.
    </div>
@endsection
