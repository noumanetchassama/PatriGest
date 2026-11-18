<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Patrimoine — PatriGest</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a2332; }
    h1 { font-size: 16px; color: #0f2a4a; margin-bottom: 2px; }
    .meta { color: #6b7a90; font-size: 10px; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #f2f5f9; color: #0f2a4a; font-size: 8px; text-transform: uppercase; }
    td, th { padding: 3px 5px; border-bottom: 1px solid #eef1f5; text-align: left; }
    .num { text-align: right; }
</style>
</head>
<body>
    <h1>Liste du patrimoine</h1>
    <div class="meta">PatriGest — {{ count($items) }} bien(s) — Généré le {{ $generatedAt->format('d/m/Y à H:i') }}</div>
    <table>
        <tr>
            <th>Référence</th><th>Désignation</th><th>Type</th><th>Catégorie</th>
            <th>Statut</th><th>État</th><th class="num">Valeur (FCFA)</th><th>Acquisition</th>
        </tr>
        @foreach ($items as $a)
            <tr>
                <td>{{ $a['reference'] ?? '' }}</td>
                <td>{{ $a['designation'] ?? '' }}</td>
                <td>{{ $a['type'] ?? '' }}</td>
                <td>{{ $a['category'] ?? '' }}</td>
                <td>{{ $a['status'] ?? '' }}</td>
                <td>{{ $a['etat'] ?? '' }}</td>
                <td class="num">{{ number_format((float) ($a['value'] ?? 0), 0, ',', ' ') }}</td>
                <td>{{ !empty($a['acquisition_date']) ? \Illuminate\Support\Carbon::parse($a['acquisition_date'])->format('d/m/Y') : '' }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
