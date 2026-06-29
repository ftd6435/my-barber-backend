<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à jour de réservation</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f5f7;font-family:Arial,sans-serif;color:#1c1c1e;">
    <div style="max-width:640px;margin:0 auto;padding:32px 20px;">
        <div style="background-color:#ffffff;border:1px solid #e5e5ea;border-radius:16px;overflow:hidden;">
            <div style="background-color:#1c1c1e;padding:24px 28px;">
                <h1 style="margin:0;color:#f5f5f7;font-size:24px;font-weight:700;">Mise à jour de réservation</h1>
            </div>

            <div style="padding:28px;">
                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                    Le statut de la réservation <strong>{{ $booking->reference }}</strong> a été mis à jour.
                </p>

                <div style="background-color:#f9f6ee;border-left:4px solid #d4af37;border-radius:10px;padding:16px 18px;margin-bottom:20px;">
                    <p style="margin:0 0 8px;font-size:14px;"><strong>Service :</strong> {{ $booking->service?->name ?? 'Non renseigné' }}</p>
                    <p style="margin:0 0 8px;font-size:14px;"><strong>Statut :</strong> {{ ucfirst($action) }}</p>
                    <p style="margin:0 0 8px;font-size:14px;"><strong>Date :</strong> {{ $booking->booking_date?->format('d/m/Y') }}</p>
                    <p style="margin:0 0 8px;font-size:14px;"><strong>Heure :</strong> {{ $booking->start_time }}</p>
                    @if ($booking->professionel_comment)
                        <p style="margin:0 0 8px;font-size:14px;"><strong>Commentaire professionnel :</strong> {{ $booking->professionel_comment }}</p>
                    @endif
                    @if ($booking->cancel_reason)
                        <p style="margin:0 0 8px;font-size:14px;"><strong>Motif d'annulation :</strong> {{ $booking->cancel_reason }}</p>
                    @endif
                    @if ((float) $booking->extra_fees > 0)
                        <p style="margin:0;font-size:14px;"><strong>Frais supplémentaires :</strong> {{ number_format((float) $booking->extra_fees, 2, ',', ' ') }}</p>
                    @endif
                </div>

                <p style="margin:0;font-size:14px;line-height:1.6;color:#333335;">
                    Merci de consulter les détails de votre réservation dans l'application.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
