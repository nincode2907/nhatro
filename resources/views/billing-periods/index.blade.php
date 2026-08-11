@extends('layouts.app')

@section('title', 'Kỳ ghi điện nước — '.config('app.name'))

@section('content')
    <header class="page-header billing-header">
        <div>
            <p class="eyebrow">Ghi điện nước</p>
            <h1>Chọn kỳ tháng</h1>
            <p class="muted property-summary">Mỗi kỳ lưu một bộ chỉ số riêng cho từng phòng.</p>
        </div>
    </header>

    @if (! $property)
        <div class="card empty-state">
            <h2>Chưa có dữ liệu nhà trọ</h2>
            <p>Hãy tạo dữ liệu nhà trọ trước khi mở kỳ ghi điện nước.</p>
        </div>
    @else
        <section class="card period-create" aria-labelledby="new-period-title">
            <div>
                <p class="eyebrow">Kỳ cần làm</p>
                <h2 id="new-period-title">Mở hoặc tạo một tháng</h2>
                <p class="muted">Nếu tháng đã tồn tại, ứng dụng sẽ mở lại đúng kỳ đó.</p>
            </div>
            <form class="period-create-form" method="POST" action="{{ route('billing-periods.store') }}">
                @csrf
                <div class="field">
                    <label for="period_key">Tháng ghi chỉ số</label>
                    <input id="period_key" name="period_key" type="month"
                           value="{{ old('period_key', $suggestedPeriodKey) }}" required>
                    @error('period_key')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <button class="button button-primary" type="submit">Tiếp tục</button>
            </form>
        </section>

        <section class="period-history" aria-labelledby="period-history-title">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Đã có</p>
                    <h2 id="period-history-title">Các kỳ gần đây</h2>
                </div>
                <span class="count-pill">{{ $periods->count() }} kỳ</span>
            </div>

            <div class="period-list">
                @forelse ($periods as $period)
                    <a class="card period-row" href="{{ route('billing-periods.show', $period) }}">
                        <span>
                            <strong>{{ $period->label() }}</strong>
                            <small>{{ $period->period_key }}</small>
                        </span>
                        <span class="status-badge period-status-{{ strtolower($period->status->value) }}">
                            {{ $period->status->label() }}
                        </span>
                    </a>
                @empty
                    <div class="card empty-state compact-empty">
                        <h3>Chưa có kỳ nào</h3>
                        <p>Chọn tháng ở trên để bắt đầu.</p>
                    </div>
                @endforelse
            </div>
        </section>
    @endif
@endsection
