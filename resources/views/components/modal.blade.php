@props([
    'id',
    'titre' => 'Formulaire',
])

<dialog id="{{ $id }}" {{ $attributes->merge(['class' => 'sigaec-modal']) }} aria-labelledby="{{ $id }}-titre">
    <div class="sigaec-modal__header">
        <h3 id="{{ $id }}-titre" class="sigaec-modal__title">{{ $titre }}</h3>
        <button type="button" class="sigaec-modal__close" data-modal-close="{{ $id }}" aria-label="Fermer la fenêtre">
            &times;
        </button>
    </div>

    <div class="sigaec-modal__body">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="sigaec-modal__footer">
            {{ $footer }}
        </div>
    @endif
</dialog>
