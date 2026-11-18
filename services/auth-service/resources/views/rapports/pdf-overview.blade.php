<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rapport de synthèse — PatriGest</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a2332; }
    h1 { font-size: 18px; color: #0f2a4a; margin-bottom: 2px; }
    h2 { font-size: 13px; color: #0f2a4a; margin: 14px 0 6px; border-bottom: 1px solid #d7dee8; padding-bottom: 3px; }
    .meta { color: #6b7a90; font-size: 10px; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 4px 8px; border-bottom: 1px solid #eef1f5; text-align: left; }
    th { background: #f2f5f9; color: #0f2a4a; font-size: 10px; text-transform: uppercase; }
    .num { text-align: right; }
    .kpi { display: inline-block; width: 31%; margin: 0 1% 8px 0; padding: 8px 10px; border: 1px solid #d7dee8; border-radius: 4px; vertical-align: top; }
    .kpi .label { font-size: 9px; color: #6b7a90; text-transform: uppercase; }
    .kpi .value { font-size: 16px; font-weight: bold; color: #0f2a4a; }
</style>
</head>
<body>
    <h1>Rapport de synthèse du patrimoine</h1>
    <div class="meta">PatriGest — Généré le {{ $generatedAt->format('d/m/Y à H:i') }} (heure de Lomé)</div>

    @php
        $a = $overview['assets'] ?? [];
        $typeLabels = ['immobilier' => 'Immobiliers', 'mobilier' => 'Mobiliers', 'vehicule' => 'Véhicules'];
        $statusLabels = ['brouillon' => 'Brouillon', 'en_validation' => 'En validation', 'actif' => 'Actif', 'maintenance' => 'En maintenance', 'reforme' => 'Réformé', 'perdu' => 'Perdu'];
    @endphp

    <h2>Indicateurs clés</h2>
    <div class="kpi"><div class="label">Total des biens</div><div class="value">{{ number_format($a['total'] ?? 0, 0, ',', ' ') }}</div></div>
    <div class="kpi"><div class="label">Valeur totale</div><div class="value">{{ number_format($a['total_value'] ?? 0, 0, ',', ' ') }} F</div></div>
    <div class="kpi"><div class="label">Workflows en attente</div><div class="value">{{ ($overview['workflows']['assignments_pending'] ?? 0) + ($overview['workflows']['transfers_pending'] ?? 0) + ($overview['workflows']['submissions_pending'] ?? 0) }}</div></div>

    <h2>Répartition par type</h2>
    <table>
        <tr><th>Type</th><th class="num">Nombre</th></tr>
        @foreach ($typeLabels as $t => $label)
            <tr><td>{{ $label }}</td><td class="num">{{ $a['by_type'][$t] ?? 0 }}</td></tr>
        @endforeach
    </table>

    <h2>Répartition par statut</h2>
    <table>
        <tr><th>Statut</th><th class="num">Nombre</th></tr>
        @foreach (($a['by_status'] ?? []) as $status => $count)
            <tr><td>{{ $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}</td><td class="num">{{ $count }}</td></tr>
        @endforeach
    </table>

    <h2>Workflows et opérations</h2>
    <table>
        <tr><th>Indicateur</th><th class="num">Valeur</th></tr>
        <tr><td>Affectations en attente</td><td class="num">{{ $overview['workflows']['assignments_pending'] ?? 0 }}</td></tr>
        <tr><td>Transferts en attente</td><td class="num">{{ $overview['workflows']['transfers_pending'] ?? 0 }}</td></tr>
        <tr><td>Créations en attente</td><td class="num">{{ $overview['workflows']['submissions_pending'] ?? 0 }}</td></tr>
        <tr><td>Incidents ouverts</td><td class="num">{{ $overview['incidents']['ouverts'] ?? 0 }}</td></tr>
        <tr><td>Maintenances en cours</td><td class="num">{{ $overview['maintenances']['en_cours'] ?? 0 }}</td></tr>
        <tr><td>Coût maintenance (terminées)</td><td class="num">{{ number_format($overview['maintenances']['cout_total_terminées'] ?? 0, 0, ',', ' ') }} F</td></tr>
        <tr><td>Campagnes d'inventaire en cours</td><td class="num">{{ $overview['inventories']['en_cours'] ?? 0 }}</td></tr>
        <tr><td>Campagnes clôturées</td><td class="num">{{ $overview['inventories']['clos'] ?? 0 }}</td></tr>
        <tr><td>Biens absents constatés</td><td class="num">{{ $overview['inventories']['biens_absents'] ?? 0 }}</td></tr>
    </table>
</body>
</html>
