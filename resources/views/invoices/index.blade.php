@extends('layouts.app')

@section('title', 'Hóa đơn '.$period->label().' — '.config('app.name'))

@section('content')
    <nav class="breadcrumbs" aria-label="Điều hướng">
        <a href="{{ route('billing-periods.show', $period) }}">← {{ $period->label() }}</a>
    </nav>

    <header class="page-header billing-header">
        <div>
            <p class="eyebrow">Hóa đơn tháng</p>
            <h1>{{ $period->label() }}</h1>
            <p class="muted property-summary">Số tiền bên dưới lấy trực tiếp từ snapshot hóa đơn.</p>
        </div>
        <div class="invoice-index-actions">
            <span class="status-badge period-status-{{ strtolower($period->status->value) }}">
                {{ $period->status->label() }}
            </span>
            @if ($invoices->isNotEmpty())
                <a class="button button-secondary" href="{{ route('invoice-exports.xlsx', $period) }}">
                    Tải Excel
                </a>
                <a class="button button-secondary" href="{{ route('invoice-exports.pdf-batch', $period) }}">
                    Tải PDF
                </a>
                <a class="button button-secondary" href="{{ route('invoice-exports.docx-batch', $period) }}">
                    Tải Word
                </a>
                <a class="button button-primary" href="{{ route('invoices.print-batch', $period) }}">
                    In 3 phiếu / A4
                </a>
            @endif
        </div>
    </header>

    <section class="invoice-list" aria-label="Danh sách hóa đơn">
        @forelse ($invoices as $invoice)
            <article class="card invoice-card">
                <div class="invoice-card-heading">
                    <div>
                        <p class="eyebrow">{{ $invoice->room->floor->name }}</p>
                        <h2>Phòng {{ $invoice->room->room_number }}</h2>
                    </div>
                    <span class="status-badge invoice-status-{{ strtolower($invoice->status->value) }}">
                        {{ $invoice->status->label() }}
                    </span>
                </div>

                <dl class="invoice-card-total">
                    <dt>Tổng cộng</dt>
                    <dd>{{ number_format($invoice->total, 0, ',', '.') }} đ</dd>
                </dl>

                <div class="invoice-card-actions">
                    <a class="button button-secondary"
                       href="{{ route('invoices.show', [$period, $invoice]) }}">
                        Xem hóa đơn
                    </a>
                    <a class="button button-primary"
                       href="{{ route('invoices.print-single', [$period, $invoice]) }}">
                        In A5
                    </a>
                </div>
            </article>
        @empty
            <div class="card empty-state">
                <h2>Chưa có hóa đơn nháp</h2>
                <p class="muted">Hóa đơn sẽ tự tạo khi lưu hoặc bỏ qua chỉ số của một phòng.</p>
            </div>
        @endforelse
    </section>
@endsection
