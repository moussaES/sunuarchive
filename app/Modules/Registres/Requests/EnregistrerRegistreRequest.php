<?php

namespace App\Modules\Registres\Requests;

use App\Enums\EtatMateriel;
use App\Enums\PresencePhysique;
use App\Enums\TypeActe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Requête de validation pour la création ou mise à jour d'un registre d'état civil.
 */
class EnregistrerRegistreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $registreId = $this->route('id') ?? $this->input('id_registre');

        return [
            'cote' => [
                'required',
                'string',
                'max:80',
                Rule::unique('registre', 'cote')->ignore($registreId, 'id_registre'),
            ],
            'type' => ['required', Rule::enum(TypeActe::class)],
            'annee_debut' => ['required', 'integer', 'min:1850', 'max:2100'],
            'annee_fin' => ['required', 'integer', 'min:1850', 'max:2100', 'gte:annee_debut'],
            'numero_volume' => ['nullable', 'string', 'max:30'],
            'nombre_pages' => ['nullable', 'integer', 'min:1'],
            'nombre_actes' => ['nullable', 'integer', 'min:0'],
            'localisation' => ['nullable', 'string', 'max:255'],
            'etat_materiel' => ['required', Rule::enum(EtatMateriel::class)],
            'presence' => ['required', Rule::enum(PresencePhysique::class)],
            'inventaire_complet' => ['nullable', 'boolean'],
            'observations' => ['nullable', 'string'],
            'date_inventaire' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'cote.required' => 'La cote archivistique est obligatoire.',
            'cote.unique' => 'Cette cote archivistique est déjà attribuée à un autre registre.',
            'type.required' => 'Le type d\'acte est obligatoire.',
            'annee_debut.required' => 'L\'année de début est obligatoire.',
            'annee_fin.required' => 'L\'année de fin est obligatoire.',
            'annee_fin.gte' => 'L\'année de fin doit être supérieure ou égale à l\'année de début.',
            'etat_materiel.required' => 'L\'état matériel de conservation est obligatoire.',
            'presence.required' => 'La présence physique du registre est obligatoire.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'inventaire_complet' => $this->boolean('inventaire_complet'),
        ]);
    }
}
