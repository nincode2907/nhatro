<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>In hàng loạt — {{ $period->label() }}</title>
    <link rel="stylesheet" href="{{ asset('css/invoice-print.css') }}">
    <link rel="stylesheet" href="{{ asset('css/invoice-print-batch.css') }}">
</head>
<body class="print-body print-body--batch">
    <nav class="print-toolbar" aria-label="Công cụ in">
        <a href="{{ route('invoices.index', $period) }}">← Danh sách hóa đơn</a>
        <div>
            <span>{{ $documents->count() }} hóa đơn · 3 phiếu mỗi tờ A4</span>
            <button type="button" data-print-page @disabled($documents->isEmpty())>In hàng loạt</button>
        </div>
    </nav>

    <main class="print-preview print-preview--batch">
        @forelse ($sheets as $sheetNumber => $sheet)
            <section class="batch-sheet" data-batch-sheet aria-label="Tờ {{ $sheetNumber + 1 }}">
                @foreach ($sheet as $document)
                    @include('invoices.partials.document', ['document' => $document, 'variant' => 'compact'])
                @endforeach
            </section>
        @empty
            <div class="print-empty-state">Chưa có hóa đơn để in trong kỳ này.</div>
        @endforelse
    </main>

    <script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
