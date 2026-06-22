<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prix en attente d'approbation</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f5f7;font-family:Arial,sans-serif;color:#1c1c1e;">
    <div style="max-width:640px;margin:0 auto;padding:32px 20px;">
        <div style="background-color:#ffffff;border:1px solid #e5e5ea;border-radius:16px;overflow:hidden;">
            <div style="background-color:#1c1c1e;padding:24px 28px;">
                <h1 style="margin:0;color:#f5f5f7;font-size:24px;font-weight:700;">Prix en attente d'approbation</h1>
            </div>

            <div style="padding:28px;">
                @if ($recipientType === 'admin')
                    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                        Un professionnel a soumis des prix qui nécessitent une validation.
                    </p>
                @else
                    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                        Vos prix ont bien été enregistrés et sont maintenant en attente d'approbation par l'administration.
                    </p>
                @endif

                <div style="background-color:#f9f6ee;border-left:4px solid #d4af37;border-radius:10px;padding:16px 18px;margin-bottom:20px;">
                    <p style="margin:0 0 8px;font-size:14px;"><strong>Service :</strong> {{ $service->name }}</p>
                    <p style="margin:0 0 8px;font-size:14px;"><strong>Salon :</strong> {{ $service->salon?->name ?? 'Non renseigné' }}</p>
                    <p style="margin:0 0 8px;font-size:14px;"><strong>Professionnel :</strong> {{ $service->professionel?->first_name }} {{ $service->professionel?->last_name }}</p>
                    <p style="margin:0;font-size:14px;"><strong>Nombre de prix concernés :</strong> {{ $pricesCount }}</p>
                </div>

                @if ($recipientType === 'admin')
                    <p style="margin:0;font-size:14px;line-height:1.6;color:#333335;">
                        Merci de vérifier ces prix et de procéder à leur approbation si tout est conforme.
                    </p>
                @else
                    <p style="margin:0;font-size:14px;line-height:1.6;color:#333335;">
                        Vous recevrez une confirmation une fois la revue terminée par l'administration.
                    </p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
