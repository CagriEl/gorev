<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kırklareli Belediyesi — Görev Yönetim Sistemi</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(145deg, #0f172a 0%, #1e3a8a 45%, #2563eb 100%);
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            width: 100%;
            max-width: 520px;
            background: rgba(255, 255, 255, 0.97);
            color: #0f172a;
            border-radius: 24px;
            padding: 48px 40px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.45);
        }

        .emblem {
            width: 72px;
            height: 72px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.35);
        }

        h1 {
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.3;
            margin-bottom: 8px;
            color: #0f172a;
        }

        .subtitle {
            font-size: 1rem;
            color: #64748b;
            margin-bottom: 32px;
            line-height: 1.5;
        }

        .features {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 36px;
        }

        .feature {
            font-size: 0.8rem;
            font-weight: 600;
            color: #475569;
            background: #f1f5f9;
            padding: 6px 14px;
            border-radius: 999px;
        }

        .btn-login {
            display: inline-block;
            width: 100%;
            max-width: 280px;
            padding: 16px 32px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            font-size: 1.05rem;
            font-weight: 700;
            text-decoration: none;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.5);
        }

        .footer {
            margin-top: 28px;
            font-size: 0.8rem;
            color: #94a3b8;
        }

        @media (max-width: 480px) {
            .card { padding: 36px 24px; }
            h1 { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="emblem" aria-hidden="true">🏛️</div>

        <h1>Kırklareli Belediyesi<br>Görev Yönetim Sistemi</h1>
        <p class="subtitle">Saha operasyonları, görev takibi ve yönetim paneli</p>

        <div class="features">
            <span class="feature">Görev atama</span>
            <span class="feature">Saha haritası</span>
            <span class="feature">Raporlama</span>
        </div>

        <a href="{{ url('/admin/login') }}" class="btn-login">Giriş</a>

        <p class="footer">Bel-Sistem · Kırklareli Belediyesi</p>
    </main>
</body>
</html>
