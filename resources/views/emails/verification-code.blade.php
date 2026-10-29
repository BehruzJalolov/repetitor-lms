<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <title>{{ $purpose }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f8fafc;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1e293b;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f8fafc;padding:32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:480px;background-color:#ffffff;border-radius:16px;border:1px solid #e2e8f0;padding:32px;">
                    <tr>
                        <td style="text-align:center;padding-bottom:20px;">
                            <span style="display:inline-block;background-color:#6338e8;color:#ffffff;font-weight:800;font-size:18px;padding:8px 14px;border-radius:10px;">
                                {{ config('app.name', 'RepetitorLMS') }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <h1 style="margin:0 0 12px;font-size:20px;font-weight:700;color:#0f172a;">Assalomu alaykum, {{ $userName }}!</h1>
                            <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#475569;">
                                {{ $description }}
                            </p>
                            <div style="text-align:center;margin:24px 0;padding:18px;background-color:#f1f0ff;border-radius:12px;border:1px dashed #8f80fb;">
                                <span style="font-family:monospace;font-size:32px;font-weight:800;letter-spacing:8px;color:#5429cc;">{{ $code }}</span>
                            </div>
                            <p style="margin:0 0 12px;font-size:13px;line-height:1.5;color:#64748b;">
                                ⏳ Ushbu kod <strong>15 daqiqa</strong> davomida amal qiladi. Xavfsizlik uchun kodni hech kimga bermang.
                            </p>
                            <p style="margin:0;font-size:13px;line-height:1.5;color:#94a3b8;">
                                Agar bu so'rovni siz yubormagan bo'lsangiz, ushbu xabarni e'tiborsiz qoldiring.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
