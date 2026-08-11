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
@endsection
