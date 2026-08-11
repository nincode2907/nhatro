<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    @include('exports.pdf.styles')
</head>
<body>
    @include('exports.pdf.partials.invoice', ['record' => $record, 'compact' => false])
</body>
</html>
