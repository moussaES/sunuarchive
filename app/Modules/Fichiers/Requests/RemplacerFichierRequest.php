<?php

namespace App\Modules\Fichiers\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de la requête de remplacement d'un fichier numérique.
 * 
 * Exige impérativement une justification détaillée conformément à la section 8
 * du cahier des charges afin d'assurer l'audit et l'intégrité de la chaîne archivistique.
 */
class RemplacerFichierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('id_fichier') && $this->route('id')) {
            $this->merge([
                'id_fichier' => (int) $this->route('id'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'id_fichier' => ['required', 'integer', 'exists:fichier_numerique,id_fichier'],
            'fichier' => [
                'required',
                'file',
                'mimes:pdf,tif,tiff,jpg,jpeg',
                'max:25600',
            ],
            'justification_remplacement' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
            'date_numerisation' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_fichier.required' => 'Le fichier à remplacer est introuvable.',
            'id_fichier.exists' => 'Le fichier sélectionné n\'existe pas dans les archives.',
            'fichier.required' => 'La nouvelle copie numérique est obligatoire.',
            'fichier.file' => 'Le document téléversé doit être un fichier valide.',
            'fichier.mimes' => 'Seuls les formats d\'archives TIFF, PDF et JPEG sont acceptés.',
            'fichier.max' => 'Le fichier ne doit pas dépasser la taille maximale autorisée (25 Mo).',
            'justification_remplacement.required' => 'La justification du remplacement est strictement obligatoire pour toute opération archivistique.',
            'justification_remplacement.min' => 'La justification doit comporter au moins 10 caractères pour être probante.',
        ];
    }
}
