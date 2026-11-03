<?php

namespace App\Modules\Actes\Requests;

use App\Enums\EtatVerification;
use App\Enums\TypeActe;
use App\Models\Acte;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Requête de validation pour la création et mise à jour d'actes d'état civil.
 * 
 * Applique le contrôle strict anti-doublon composite (type, numero_acte, annee, id_registre)
 * conformément à la section 5 et 11.3 du cahier des charges.
 */
class EnregistrerActeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_registre' => ['required', 'integer', 'exists:registre,id_registre'],
            'type' => ['required', Rule::enum(TypeActe::class)],
            'numero_acte' => ['required', 'string', 'max:20'],
            'annee' => ['required', 'integer', 'min:1850', 'max:2100'],
            'date_enregistrement' => ['nullable', 'date'],
            'page_folio' => ['nullable', 'string', 'max:20'],
            'numero_jugement' => ['nullable', 'string', 'max:20', 'regex:/^\d+$/'],
            'date_jugement' => ['nullable', 'date'],
            'annee_jugement' => ['nullable', 'integer', 'min:1850', 'max:2100'],
            'etat_verification' => ['required', Rule::enum(EtatVerification::class)],
            'observations' => ['nullable', 'string'],

            // Champs Naissance
            'date_naissance' => ['nullable', 'date'],
            'lieu_naissance' => ['nullable', 'string', 'max:150'],
            'sexe' => ['nullable', 'string', 'max:15'],
            'date_declaration' => ['nullable', 'date'],
            'nom_enfant' => ['nullable', 'string', 'max:100'],
            'prenoms_enfant' => ['nullable', 'string', 'max:150'],
            'nom_pere' => ['nullable', 'string', 'max:100'],
            'prenoms_pere' => ['nullable', 'string', 'max:150'],
            'nom_mere' => ['nullable', 'string', 'max:100'],
            'prenoms_mere' => ['nullable', 'string', 'max:150'],
            'nom_declarant' => ['nullable', 'string', 'max:100'],
            'prenoms_declarant' => ['nullable', 'string', 'max:150'],

            // Champs Mariage
            'date_mariage' => ['nullable', 'date'],
            'lieu_mariage' => ['nullable', 'string', 'max:150'],
            'regime_matrimonial' => ['nullable', 'string', 'max:100'],
            'mentions_marginales' => ['nullable', 'string'],
            'nom_epoux' => ['nullable', 'string', 'max:100'],
            'prenoms_epoux' => ['nullable', 'string', 'max:150'],
            'nom_epouse' => ['nullable', 'string', 'max:100'],
            'prenoms_epouse' => ['nullable', 'string', 'max:150'],
            'nom_temoin' => ['nullable', 'string', 'max:100'],
            'prenoms_temoin' => ['nullable', 'string', 'max:150'],

            // Champs Décès
            'date_deces' => ['nullable', 'date'],
            'heure_deces' => ['nullable', 'string'],
            'lieu_deces' => ['nullable', 'string', 'max:150'],
            'profession' => ['nullable', 'string', 'max:100'],
            'domicile' => ['nullable', 'string', 'max:200'],
            'date_declaration_deces' => ['nullable', 'date'],
            'nom_defunt' => ['nullable', 'string', 'max:100'],
            'prenoms_defunt' => ['nullable', 'string', 'max:150'],
            'nom_declarant_deces' => ['nullable', 'string', 'max:100'],
            'prenoms_declarant_deces' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_registre.required' => 'Le registre de rattachement est obligatoire.',
            'id_registre.exists' => 'Le registre sélectionné n\'existe pas dans l\'inventaire.',
            'type.required' => 'Le type d\'acte (Naissance, Mariage ou Décès) est obligatoire.',
            'numero_acte.required' => 'Le numéro officiel de l\'acte tel qu\'inscrit au registre est obligatoire.',
            'annee.required' => 'L\'année de l\'acte est obligatoire.',
            'numero_jugement.regex' => 'Le numéro de jugement doit contenir uniquement des chiffres (format type 0000).',
            'etat_verification.required' => 'L\'état de vérification de la transcription est obligatoire.',
        ];
    }

    /**
     * Contrôle des doublons composite : (type, numero_acte, annee, id_registre).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $type = $this->input('type');
            $numeroActe = $this->input('numero_acte');
            $annee = $this->input('annee');
            $idRegistre = $this->input('id_registre');
            $idActeCourant = $this->route('id') ?? $this->input('id_acte');

            if ($type && $numeroActe && $annee && $idRegistre) {
                $doublonExiste = Acte::where('type', $type)
                    ->where('numero_acte', $numeroActe)
                    ->where('annee', $annee)
                    ->where('id_registre', $idRegistre)
                    ->when($idActeCourant, fn($q) => $q->where('id_acte', '!=', $idActeCourant))
                    ->exists();

                if ($doublonExiste) {
                    $v->errors()->add(
                        'numero_acte',
                        "Alerte doublon : un acte de type {$type} portant le numéro {$numeroActe} pour l'année {$annee} existe déjà dans ce registre."
                    );
                }
            }
        });
    }
}
