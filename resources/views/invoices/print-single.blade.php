<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>In hóa đơn phòng {{ $invoice->room->room_number }} — {{ $period->label() }}</title>
    <link rel="stylesheet" href="{{ asset('css/invoice-print.css') }}">
    <link rel="stylesheet" href="{{ asset('css/invoice-print-a5.css') }}">
</head>
<body class="print-body print-body--single">
    <nav class="print-toolbar" aria-label="Công cụ in">
        <a href="{{ route('invoices.show', [$period, $invoice]) }}">← Quay lại hóa đơn</a>
        <div>
            <span>Khổ A5 · Dọc</span>
            <button type="button" data-print-page>In hóa đơn</button>
        </div>
    </nav>

    <main class="print-preview print-preview--single">
        @include('invoices.partials.document', ['document' => $document, 'variant' => 'single'])
    </main>

    <script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
