@props([
    'type' => 'positif', // positif | attente | attention | critique
    'texte' => null
])

@php
    $prefixes = [
        'positif' => '✓',
        'attente' => '○',
        'attention' => '!',
        'critique' => '✕',
    ];
    $classe = match($type) {
        'positif' => 'statut--positif',
        'attente' => 'statut--attente',
        'attention' => 'statut--attention',
        'critique' => 'statut--critique',
        default => 'statut--attente',
    };
    $prefixe = $prefixes[$type] ?? '○';
@endphp

<span class="statut {{ $classe }}">
    <span aria-hidden="true">{{ $prefixe }}</span>
    <span>{{ $texte ?? $slot }}</span>
</span>
