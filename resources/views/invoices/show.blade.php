@extends('layouts.app')

@section('title', 'Hóa đơn phòng '.$invoice->room->room_number.' — '.config('app.name'))

@section('content')
    <nav class="breadcrumbs" aria-label="Điều hướng">
        <a href="{{ route('invoices.index', $period) }}">← Hóa đơn {{ $period->label() }}</a>
    </nav>

    <header class="page-header invoice-detail-header">
        <div>
            <p class="eyebrow">{{ $period->label() }} · {{ $invoice->room->floor->name }}</p>
            <h1>Phòng {{ $invoice->room->room_number }}</h1>
            <p class="muted property-summary">Snapshot tạo lúc {{ $invoice->generated_at?->format('H:i d/m/Y') }}.</p>
        </div>
        <span class="status-badge invoice-status-{{ strtolower($invoice->status->value) }}">
            {{ $invoice->status->label() }}
        </span>
    </header>

    <section class="card invoice-snapshot" aria-labelledby="invoice-items-title">
        <h2 id="invoice-items-title">Các khoản thu đã lưu</h2>

        <div class="invoice-items">
            @forelse ($invoice->items as $item)
                <article class="invoice-item">
                    <div class="invoice-item-copy">
                        <strong>{{ $item->description }}</strong>
                        @if ($item->metadata)
                            <span>
                                Chỉ số {{ number_format($item->metadata['previous'], 0, ',', '.') }}
                                → {{ number_format($item->metadata['current'], 0, ',', '.') }}
                            </span>
                        @endif
                        <span>
                            {{ number_format($item->quantity, 0, ',', '.') }} {{ $item->unit }}
                            × {{ number_format($item->unit_price, 0, ',', '.') }} đ
                        </span>
                    </div>
                    <strong class="invoice-item-amount">{{ number_format($item->amount, 0, ',', '.') }} đ</strong>
                </article>
            @empty
                <p class="muted">Phòng này không có khoản thu trong snapshot hiện tại.</p>
            @endforelse
        </div>

        <dl class="invoice-grand-total">
            <dt>Tổng cộng</dt>
            <dd>{{ number_format($invoice->total, 0, ',', '.') }} đ</dd>
        </dl>
    </section>
@endsection
