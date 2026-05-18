<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Başarısız</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: #0a0a0b; color: #e4e4e7;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        .card {
            background: #141416; border: 1px solid #2a2a2d;
            border-radius: 16px; padding: 3rem 2rem;
            text-align: center; max-width: 420px;
        }
        .icon {
            width: 64px; height: 64px;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .icon svg { width: 32px; height: 32px; color: #ef4444; }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        p { color: #71717a; font-size: 0.9375rem; line-height: 1.6; }
        a {
            display: inline-block; margin-top: 1.5rem;
            color: #6366f1; text-decoration: none; font-weight: 500;
        }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </div>
        <h1>Ödeme Başarısız</h1>
        <p>Ödemeniz tamamlanamadı. Lütfen tekrar deneyiniz veya farklı bir kart kullanınız.</p>
        <a href="/">← Tekrar dene</a>
    </div>
</body>
</html>
