<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password Akun</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f8fafc;
            padding-bottom: 40px;
            padding-top: 40px;
        }
        .container {
            max-width: 570px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03), 0 1px 3px rgba(0, 0, 0, 0.02);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #5EA500 0%, #3E7D00 100%);
            padding: 32px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }
        .content {
            padding: 40px 32px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .text {
            font-size: 14px;
            line-height: 1.6;
            color: #475569;
            margin-top: 0;
            margin-bottom: 24px;
        }
        .btn-container {
            text-align: center;
            margin-bottom: 30px;
            margin-top: 10px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #5EA500 0%, #3E7D00 100%);
            color: #ffffff !important;
            text-decoration: none !important;
            padding: 14px 30px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(94, 165, 0, 0.25);
            transition: all 0.2s ease;
        }
        .alert-box {
            background-color: #f1f5f9;
            border-left: 4px solid #94a3b8;
            padding: 16px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 24px;
        }
        .alert-box p {
            margin: 0;
            font-size: 13px;
            line-height: 1.5;
            color: #64748b;
        }
        .divider {
            height: 1px;
            background-color: #e2e8f0;
            margin: 30px 0;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.5;
            padding: 0 32px;
        }
        .footer a {
            color: #3E7D00;
            text-decoration: none;
        }
        .fallback-link {
            font-size: 12px;
            color: #94a3b8;
            word-break: break-all;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>SiPetani</h1>
            </div>
            <div class="content">
                <p class="text">Hai {{ $user->nama_lengkap }},</p>
                <p class="text">Kami menerima permintaan untuk mereset password akun Anda pada SiPetani, Sistem Informasi Pemetaan Tanaman Padi.</p>
                
                <div class="btn-container">
                    <a href="{{ $url }}" class="btn">Reset Password Sekarang</a>
                </div>

                <div class="alert-box">
                    <p><strong>Penting:</strong> Tautan ini hanya berlaku selama <strong>60 menit</strong>. Jika Anda tidak mengajukan permintaan ini, silakan abaikan email ini dengan aman.</p>
                </div>

                <div class="divider"></div>

                <div class="footer">
                    <p>Hormat kami,<br><strong>Sistem Informasi Pemetaan Tanaman Padi</strong></p>
                    <p style="margin-top: 20px;">Jika tombol di atas tidak berfungsi, salin dan tempel URL berikut ke browser Anda:</p>
                    <p class="fallback-link"><a href="{{ $url }}">{{ $url }}</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
