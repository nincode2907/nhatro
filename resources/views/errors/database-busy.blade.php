<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database đang bận</title>
    <style>
        body { margin: 0; background: #f5f3ee; color: #20201d; font: 16px/1.5 system-ui, sans-serif; }
        main { box-sizing: border-box; width: min(92%, 34rem); margin: 12vh auto; padding: 2rem; border: 1px solid #d7d2c8; border-radius: 1rem; background: #fff; }
        h1 { margin-top: 0; font-size: 1.65rem; }
        button { min-height: 3rem; padding: .7rem 1.2rem; border: 0; border-radius: .65rem; background: #20201d; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
<main>
    <h1>Database đang bận</h1>
    <p>Một thiết bị khác có thể đang lưu dữ liệu. Hãy chờ vài giây rồi thử lại; dữ liệu vừa nhập vẫn còn trên màn hình trước.</p>
    <button type="button" onclick="history.back()">Quay lại và thử lại</button>
</main>
</body>
</html>
