<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de validation pour le formulaire d'authentification agent.
 */
class ConnexionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifiant' => ['required', 'string', 'max:50'],
            'mot_de_passe' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifiant.required' => 'L\'identifiant de l\'agent est obligatoire.',
            'mot_de_passe.required' => 'Le mot de passe est obligatoire.',
        ];
    }
}
