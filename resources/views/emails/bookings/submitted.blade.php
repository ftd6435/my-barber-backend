<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle réservation</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f5f7;font-family:Arial,sans-serif;color:#1c1c1e;">
    <div style="max-width:640px;margin:0 auto;padding:32px 20px;">
        <div style="background-color:#ffffff;border:1px solid #e5e5ea;border-radius:16px;overflow:hidden;">
            <div style="background-color:#1c1c1e;padding:24px 28px;">
                <h1 style="margin:0;color:#f5f5f7;font-size:24px;font-weight:700;">Nouvelle réservation</h1>
            </div>

            <div style="padding:28px;">
                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                    Une nouvelle réservation vient d'être soumise pour l'un de vos services.
                </p>

                <div style="background-color:#f9f6ee;border-left:4px solid #d4af37;border-radius:10px;padding:16px 18px;margin-bottom:20px;">
                    <p style="margin:0 0 8px;font-size:14px;"><strong>Référence :</strong> {{ $booking->reference }}</p>
                    <p style="margin:0 0 8px;font-size:14px;"><strong>Service :</strong> {{ $booking->service?->name ?? 'Non renseigné' }}</p>
                    <p style="margin:0 0 8px;font-size:14px;"><strong>Client :</strong> {{ $booking->client?->first_name }} {{ $booking->client?->last_name }}</p>
                    <p style="margin:0 0 8px;font-size:14px;"><strong>Date :</strong> {{ $booking->booking_date?->format('d/m/Y') }}</p>
                    <p style="margin:0;font-size:14px;"><strong>Heure :</strong> {{ $booking->start_time }}</p>
                </div>

                <p style="margin:0;font-size:14px;line-height:1.6;color:#333335;">
                    Merci de consulter cette réservation et d'accepter ou refuser la demande selon vos disponibilités.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
