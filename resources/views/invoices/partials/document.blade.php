@php
    $invoice = $document->invoice;
    $variant = $variant ?? 'single';
@endphp

<article class="invoice-paper invoice-paper--{{ $variant }}" aria-label="Hóa đơn phòng {{ $invoice->room->room_number }}">
    <header class="invoice-paper-header">
        <div class="invoice-property">
            <strong>{{ $invoice->billingPeriod->property->name }}</strong>
            @if ($invoice->billingPeriod->property->address)
                <span>{{ $invoice->billingPeriod->property->address }}</span>
            @endif
        </div>
        @if ($invoice->status === \App\Enums\InvoiceStatus::Finalized)
            <span class="invoice-paper-status">{{ $invoice->status->label() }}</span>
        @endif
    </header>

    <div class="invoice-paper-title">
        <div>
            <p>Hóa đơn tiền phòng</p>
            <strong>{{ $invoice->billingPeriod->label() }}</strong>
        </div>
        <div class="invoice-room-number">
            <span>Phòng</span>
            <strong>{{ $invoice->room->room_number }}</strong>
        </div>
    </div>

    <table class="invoice-line-table">
        <colgroup>
            <col class="invoice-col-description">
            <col class="invoice-col-reading">
            <col class="invoice-col-reading">
            <col class="invoice-col-unit-price">
            <col class="invoice-col-amount">
        </colgroup>
        <thead>
            <tr>
                <th scope="col">Khoản thu</th>
                <th scope="col">Số cũ</th>
                <th scope="col">Số mới</th>
                <th scope="col">Đơn giá</th>
                <th scope="col">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($document->rows() as $row)
                @php($item = $row['item'])
                <tr class="@if($row['type']->isMetered()) is-metered @endif @if(! $item) is-empty @endif">
                    <th scope="row">
                        <strong>{{ $row['label'] }}</strong>
                        @if (! $item)
                            <span class="invoice-no-charge">Không phát sinh</span>
                        @endif
                    </th>
                    <td class="invoice-meter-value">
                        {{ $item && $row['type']->isMetered() ? number_format($item->metadata['previous'], 0, ',', '.') : '' }}
                    </td>
                    <td class="invoice-meter-value">
                        {{ $item && $row['type']->isMetered() ? number_format($item->metadata['current'], 0, ',', '.') : '' }}
                    </td>
                    <td class="invoice-unit-price">
                        {{ $item && $row['type']->isMetered() ? number_format($item->unit_price, 0, ',', '.').' đ' : '' }}
                    </td>
                    <td class="invoice-line-amount">
                        @if ($item)
                            {{ number_format($item->amount, 0, ',', '.') }} đ
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="invoice-paper-total">
        <span>Tổng cộng</span>
        <strong>{{ number_format($invoice->total, 0, ',', '.') }} đ</strong>
    </div>

    <p class="invoice-total-words">
        <strong>Số tiền bằng chữ:</strong> {{ $document->totalInWords() }}
    </p>

    <div class="invoice-paper-note">
        <strong>Ghi chú:</strong>
        <span>{{ $invoice->note ?: 'Không có' }}</span>
    </div>

    <footer class="invoice-paper-footer">
        <p>Ngày {{ $invoice->generated_at?->format('d/m/Y') ?? '...../...../..........' }}</p>
        <div class="invoice-signatures">
            <div>
                <strong>Người thu</strong>
                <span>Ký và ghi rõ họ tên</span>
            </div>
            <div>
                <strong>Người thuê</strong>
                <span>Ký và ghi rõ họ tên</span>
            </div>
        </div>
    </footer>
</article>
