@php
    $invoice = $record->invoice;
    $compact = $compact ?? false;
@endphp

<div class="pdf-invoice @if($compact) pdf-invoice--compact @endif">
    <table class="pdf-header">
        <tr>
            <td>
                <div class="pdf-property">{{ $invoice->billingPeriod->property->name }}</div>
                @if ($invoice->billingPeriod->property->address)
                    <div class="pdf-address">{{ $invoice->billingPeriod->property->address }}</div>
                @endif
            </td>
            @if ($invoice->status === \App\Enums\InvoiceStatus::Finalized)
                <td style="width: 25%; text-align: right">
                    <span class="pdf-status">{{ $invoice->status->label() }}</span>
                </td>
            @endif
        </tr>
    </table>

    <table class="pdf-title">
        <tr>
            <td>
                <div class="pdf-title-name">Hóa đơn tiền phòng</div>
                <div class="pdf-period">{{ $invoice->billingPeriod->label() }}</div>
            </td>
            <td class="pdf-room-box">
                <span class="pdf-room-label">Phòng</span>
                <span class="pdf-room-number">{{ $invoice->room->room_number }}</span>
            </td>
        </tr>
    </table>

    <table class="pdf-lines">
        <colgroup>
            <col style="width: 26%">
            <col style="width: 13%">
            <col style="width: 13%">
            <col style="width: 20%">
            <col style="width: 28%">
        </colgroup>
        <thead>
            <tr>
                <th>Khoản thu</th>
                <th>Số cũ</th>
                <th>Số mới</th>
                <th>Đơn giá</th>
                <th>Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($record->document->rows() as $row)
                @php($item = $row['item'])
                <tr>
                    <th>
                        {{ $row['label'] }}
                        @if (! $item)
                            <span class="pdf-muted">Không phát sinh</span>
                        @endif
                    </th>
                    <td class="pdf-reading">{{ $item && $row['type']->isMetered() ? number_format($item->metadata['previous'], 0, ',', '.') : '' }}</td>
                    <td class="pdf-reading">{{ $item && $row['type']->isMetered() ? number_format($item->metadata['current'], 0, ',', '.') : '' }}</td>
                    <td class="pdf-unit-price">{{ $item && $row['type']->isMetered() ? number_format($item->unit_price, 0, '.', ',').' đ' : '' }}</td>
                    <td class="pdf-amount">{{ $item ? number_format($item->amount, 0, '.', ',').' đ' : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="pdf-total">
        <tr>
            <td>Tổng cộng</td>
            <td class="pdf-total-amount">{{ number_format($invoice->total, 0, '.', ',') }} đ</td>
        </tr>
    </table>

    <div class="pdf-total-words"><strong>Số tiền bằng chữ:</strong> {{ $record->document->totalInWords() }}</div>

    <div class="pdf-note"><strong>Ghi chú:</strong> {{ $record->note() ?: 'Không có' }}</div>

    <div class="pdf-footer">
        <p class="pdf-date">Ngày {{ $invoice->generated_at?->format('d/m/Y') ?? '...../...../..........' }}</p>
        <table>
            <tr>
                <td class="pdf-signature"><strong>Người thu</strong><small>Ký và ghi rõ họ tên</small></td>
                <td class="pdf-signature"><strong>Người thuê</strong><small>Ký và ghi rõ họ tên</small></td>
            </tr>
        </table>
    </div>
</div>
