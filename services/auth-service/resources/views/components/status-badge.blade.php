@props(['status'])
@php
    $map = [
        'actif' => ['Actif', 'bg-success/10 text-success'],
        'brouillon' => ['Brouillon', 'bg-track text-muted'],
        'en_validation' => ['En validation', 'bg-warning/10 text-warning'],
        'maintenance' => ['Maintenance', 'bg-warning/10 text-warning'],
        'hors_service' => ['Hors service', 'bg-danger/10 text-danger'],
        'reforme' => ['Réformé', 'bg-danger/10 text-danger'],
        'en_attente' => ['En attente', 'bg-warning/10 text-warning'],
        'approuvee' => ['Approuvé', 'bg-success/10 text-success'],
        'rejetee' => ['Rejeté', 'bg-danger/10 text-danger'],
        'annulee' => ['Annulé', 'bg-track text-muted'],
        'ouvert' => ['Ouvert', 'bg-danger/10 text-danger'],
        'en_cours' => ['En cours', 'bg-primary/10 text-primary'],
        'resolu' => ['Résolu', 'bg-success/10 text-success'],
        'clos' => ['Clos', 'bg-track text-muted'],
        'planifiee' => ['Planifiée', 'bg-primary/10 text-primary'],
        'terminee' => ['Terminée', 'bg-success/10 text-success'],
        'trouve' => ['Trouvé', 'bg-success/10 text-success'],
        'absent' => ['Absent', 'bg-danger/10 text-danger'],
        'non_concerne' => ['Non concerné', 'bg-track text-muted'],
    ];
    [$label, $classes] = $map[$status] ?? [ucfirst(str_replace('_', ' ', (string) $status)), 'bg-track text-muted'];
@endphp
<span class="badge {{ $classes }}">{{ $label }}</span>
