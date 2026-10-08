@extends('layouts.app')

@section('title', $period->label().' — '.config('app.name'))

@section('content')
    <nav class="breadcrumbs" aria-label="Điều hướng">
        <a href="{{ route('billing-periods.index') }}">← Các kỳ tháng</a>
    </nav>

    <header class="page-header period-detail-header">
        <div>
            <p class="eyebrow">Ghi điện nước</p>
            <h1>{{ $period->label() }}</h1>
            <p class="muted property-summary">Chọn tầng để mở phòng chưa xử lý tiếp theo.</p>
        </div>
        <span class="status-badge period-status-{{ strtolower($period->status->value) }}">
            {{ $period->status->label() }}
        </span>
    </header>

    @if (session('status'))
        <div class="notice" role="status">{{ session('status') }}</div>
    @endif

    @if (! $period->isOpen())
        <div class="notice notice-warning" role="status">
            Kỳ này đã chốt. Bạn có thể xem lại chỉ số nhưng không thể lưu hoặc bỏ qua phòng.
        </div>
    @endif

    <div class="period-switcher field">
        <label for="period-switch">Đổi kỳ tháng</label>
        <select id="period-switch" data-period-switch>
            @foreach ($periods as $availablePeriod)
                <option value="{{ route('billing-periods.show', $availablePeriod) }}" @selected($availablePeriod->is($period))>
                    {{ $availablePeriod->label() }} — {{ $availablePeriod->status->label() }}
                </option>
            @endforeach
        </select>
    </div>

    <a class="button button-secondary button-block period-invoice-link"
       href="{{ route('invoices.index', $period) }}">
        Hóa đơn tháng · {{ $invoiceCount }} bản
    </a>

    @if ($period->isOpen())
        <section class="card period-finalize-card" aria-labelledby="finalize-period-title">
            <div>
                <h2 id="finalize-period-title">Đóng kỳ</h2>
                @if ($pendingRoomCount > 0)
                    <p>Còn {{ $pendingRoomCount }} phòng chưa ghi hoặc bỏ qua. Hoàn tất các phòng để đóng kỳ.</p>
                @else
                    <p>Đã xử lý tất cả phòng. Đóng kỳ sẽ khóa chỉ số và chốt các hóa đơn.</p>
                @endif
            </div>
            <form method="POST" action="{{ route('billing-periods.finalize', $period) }}"
                  data-confirm-message="Đóng {{ $period->label() }} và chốt các hóa đơn? Sau khi đóng, không thể sửa chỉ số thông thường.">
                @csrf
                <button class="button button-primary" type="submit" @disabled($pendingRoomCount > 0)>Đóng kỳ</button>
            </form>
        </section>
    @endif

    @error('period')<div class="notice notice-error" role="alert">{{ $message }}</div>@enderror

    <section class="floor-progress-list" aria-label="Tiến độ theo tầng">
        @forelse ($floorSummaries as $summary)
            @php
                $complete = $summary['total'] > 0 && $summary['processed'] === $summary['total'];
                $percent = $summary['total'] > 0 ? ($summary['processed'] / $summary['total']) * 100 : 0;
            @endphp
            <article class="card floor-progress-card">
                <div class="floor-progress-heading">
                    <div>
                        <p class="eyebrow">Tầng {{ $summary['floor']->code }}</p>
                        <h2>{{ $summary['floor']->name }}</h2>
                    </div>
                    <span class="completion-mark @if($complete) is-complete @endif" aria-hidden="true">
                        @if ($complete)
                            <svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg>
                        @else
                            {{ $summary['processed'] }}/{{ $summary['total'] }}
                        @endif
                    </span>
                </div>
                <div class="progress-copy">
                    <strong>Đã xử lý {{ $summary['processed'] }} / {{ $summary['total'] }}</strong>
                    <span>{{ $complete ? 'Hoàn tất' : 'Còn '.($summary['total'] - $summary['processed']).' phòng' }}</span>
                </div>
                <div class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $summary['total'] }}"
                     aria-valuenow="{{ $summary['processed'] }}" aria-label="Tiến độ {{ $summary['floor']->name }}">
                    <span style="width: {{ $percent }}%"></span>
                </div>
                @if ($summary['total'] > 0)
                    <a class="button {{ $complete ? 'button-secondary' : 'button-primary' }} button-block"
                       href="{{ route('meter-readings.floor', [$period, $summary['floor']]) }}">
                        {{ $complete ? 'Xem lại phòng' : ($summary['processed'] > 0 ? 'Tiếp tục ghi' : 'Bắt đầu ghi') }}
                    </a>
                @else
                    <span class="muted">Tầng này chưa có phòng đang sử dụng.</span>
                @endif
            </article>
        @empty
            <div class="card empty-state">Chưa có tầng nào.</div>
        @endforelse
    </section>

    @if ($period->canResetReadings())
        <section class="card danger-zone" aria-labelledby="reset-readings-title">
            <div>
                <h2 id="reset-readings-title">Xóa dữ liệu ghi thử</h2>
                <p>Xóa toàn bộ chỉ số và hóa đơn của {{ $period->label() }} để ghi lại từ đầu. Phòng và cấu hình giá vẫn được giữ nguyên.</p>
            </div>
            <form method="POST" action="{{ route('billing-periods.readings.destroy', $period) }}"
                  data-confirm-message="Xóa toàn bộ chỉ số và hóa đơn nháp của {{ $period->label() }}? Thao tác này không thể hoàn tác.">
                @csrf
                @method('DELETE')
                <button class="button button-danger" type="submit">Xóa bản ghi tháng này</button>
            </form>
        </section>
    @endif
@endsection
