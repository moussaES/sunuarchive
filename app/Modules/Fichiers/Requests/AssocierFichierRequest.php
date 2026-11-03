<?php

namespace App\Modules\Fichiers\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de la requête d'association d'un fichier numérique à un acte.
 * 
 * Formats acceptés conformément à la section 8 du cahier des charges :
 * TIFF (images maîtresses), PDF et JPEG (consultation).
 */
class AssocierFichierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_acte' => ['required', 'integer', 'exists:acte,id_acte'],
            'fichier' => [
                'required',
                'file',
                'mimes:pdf,tif,tiff,jpg,jpeg',
                'max:25600', // Limite configurable (25 Mo)
            ],
            'date_numerisation' => ['nullable', 'date'],
            'numero_page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_acte.required' => 'L\'acte de rattachement est obligatoire.',
            'id_acte.exists' => 'L\'acte sélectionné n\'existe pas.',
            'fichier.required' => 'Le fichier numérisé est obligatoire.',
            'fichier.file' => 'Le document téléversé doit être un fichier valide.',
            'fichier.mimes' => 'Seuls les formats d\'archives TIFF, PDF et JPEG sont acceptés.',
            'fichier.max' => 'Le fichier ne doit pas dépasser la taille maximale autorisée (25 Mo).',
            'date_numerisation.date' => 'La date de numérisation doit être une date valide.',
            'numero_page.min' => 'Le numéro de page doit être supérieur ou égal à 1.',
        ];
    }
}
