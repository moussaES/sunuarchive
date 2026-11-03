<?php

namespace App\Modules\Demandes\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de la création d'une demande de communication d'actes d'état civil.
 */
class CreerDemandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_demandeur' => ['required', 'string', 'max:150'],
            'objet' => ['required', 'string', 'max:255'],
            'date_demande' => ['nullable', 'date'],
            'actes' => ['nullable', 'array'],
            'actes.*' => ['integer', 'exists:acte,id_acte'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_demandeur.required' => 'Le service demandeur ou la qualité de l\'usager est obligatoire.',
            'objet.required' => 'L\'objet officiel de la demande de communication est obligatoire.',
            'actes.*.exists' => 'L\'un des actes sélectionnés est introuvable.',
        ];
    }
}
