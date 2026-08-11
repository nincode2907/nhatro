<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    @include('exports.pdf.styles')
</head>
<body>
    @foreach ($sheets as $sheet)
        <div class="pdf-sheet @unless($loop->last) pdf-sheet--break @endunless">
            @foreach ($sheet as $record)
                @include('exports.pdf.partials.invoice', ['record' => $record, 'compact' => true])
            @endforeach
        </div>
    @endforeach
</body>
</html>
