<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de votre adresse e-mail</title>
</head>
<body style="margin:0; padding:0; background-color:#1C1C1E; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#F5F5F7;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#1C1C1E; margin:0; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; background-color:#2C2C2E; border:1px solid rgba(212, 175, 55, 0.3); border-radius:16px; overflow:hidden;">
                    <tr>
                        <td style="padding:32px 32px 24px; background-color:#252527; border-bottom:1px solid rgba(212, 175, 55, 0.2);">
                            <div style="display:inline-block; padding:8px 14px; border-radius:999px; background-color:rgba(212, 175, 55, 0.14); color:#D4AF37; font-size:12px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase;">
                                {{ config('app.name', 'Kegny') }}
                            </div>
                            <h1 style="margin:20px 0 12px; font-size:30px; line-height:1.3; font-weight:600; color:#F5F5F7;">
                                Vérifiez votre adresse e-mail
                            </h1>
                            <p style="margin:0; font-size:16px; line-height:1.7; color:#E8E8EA;">
                                Bonjour {{ $user->first_name }},
                            </p>
                            <p style="margin:12px 0 0; font-size:16px; line-height:1.7; color:#B8B8BA;">
                                Merci d'avoir rejoint {{ config('app.name', 'Kegny') }}. Confirmez votre adresse e-mail pour sécuriser votre compte et finaliser votre accès.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#333335; border-radius:16px; border:1px solid rgba(212, 175, 55, 0.2);">
                                <tr>
                                    <td style="padding:24px;">
                                        <p style="margin:0 0 16px; font-size:15px; line-height:1.7; color:#E8E8EA;">
                                            Cliquez sur le bouton ci-dessous pour vérifier votre adresse e-mail.
                                        </p>
                                        <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 20px;">
                                            <tr>
                                                <td align="center" bgcolor="#D4AF37" style="border-radius:12px;">
                                                    <a href="{{ $verificationUrl }}" style="display:inline-block; padding:14px 26px; font-size:16px; font-weight:600; line-height:1; color:#1C1C1E; text-decoration:none; background-color:#D4AF37; border:2px solid #D4AF37; border-radius:12px;">
                                                        Vérifier mon e-mail
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                        <p style="margin:0 0 10px; font-size:14px; line-height:1.7; color:#B8B8BA;">
                                            Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :
                                        </p>
                                        <p style="margin:0; padding:16px; background-color:#1F1F21; border-radius:12px; border:1px solid rgba(212, 175, 55, 0.18); word-break:break-all; font-size:13px; line-height:1.7; color:#E8D7B0;">
                                            <a href="{{ $verificationUrl }}" style="color:#E8D7B0; text-decoration:none;">{{ $verificationUrl }}</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:24px; background-color:#252527; border-radius:16px;">
                                <tr>
                                    <td style="padding:20px 24px;">
                                        <p style="margin:0 0 8px; font-size:14px; line-height:1.7; color:#D4AF37; font-weight:600;">
                                            Besoin d'aide ?
                                        </p>
                                        <p style="margin:0; font-size:14px; line-height:1.7; color:#B8B8BA;">
                                            Si vous n'êtes pas à l'origine de cette inscription, vous pouvez ignorer cet e-mail en toute sécurité.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px 28px; border-top:1px solid #3A3A3C; text-align:center;">
                            <p style="margin:0; font-size:12px; line-height:1.7; color:#888889;">
                                © {{ now()->year }} {{ config('app.name', 'Kegny') }}. Une expérience premium au service de votre image.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
