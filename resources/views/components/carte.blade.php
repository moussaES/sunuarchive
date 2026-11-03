@props([
    'titre' => null,
])

<div {{ $attributes->merge(['class' => 'carte']) }}>
    @if($titre)
        <div class="carte__header">
            <h3 class="titre-bloc">{{ $titre }}</h3>
            @if(isset($action))
                <div>{{ $action }}</div>
            @endif
        </div>
    @endif

    <div class="carte__body">
        {{ $slot }}
    </div>
</div>
