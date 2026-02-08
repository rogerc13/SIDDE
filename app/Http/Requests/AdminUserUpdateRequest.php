<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminUserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = (int) $this->route('id');

        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:' . User::MAX_LENGTH_EMAIL,
                Rule::unique('users', 'email')
                    ->ignore($userId)
                    ->whereNull('deleted_at'),
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'max:' . User::MAX_LENGTH_PASSWORD,
            ],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no tiene un formato válido.',
            'email.unique' => 'Este correo ya está en uso.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'role_id.required' => 'El rol es obligatorio.',
        ];
    }

    /**
     * Additional business rules:
      * - If setting role to Facilitador, user must have a person_id (the Facilitator row can be created).
     * - If removing Facilitador role, the facilitator must not be assigned to any scheduled_course.
     * - If removing Participante role, the person must not have participant rows.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $userId = (int) $this->route('id');
            $user = User::withTrashed()->find($userId);

            if (! $user) {
                return;
            }

            $newRoleId = (int) $this->input('role_id');
            $oldRoleId = (int) $user->role_id;
            $personId = (int) $user->person_id;

            if ($newRoleId === Role::FACILITADOR) {
                if ($personId <= 0) {
                    $validator->errors()->add('role_id', 'No se puede asignar el rol Facilitador: el usuario no tiene una persona asociada.');
                    return;
                }
            }

            if ($oldRoleId === Role::FACILITADOR && $newRoleId !== Role::FACILITADOR) {
                $facilitatorId = DB::table('facilitators')
                    ->where('person_id', $personId)
                    ->whereNull('deleted_at')
                    ->value('id');

                if ($facilitatorId) {
                    $hasAssignments = DB::table('scheduled_course')
                        ->where('facilitator_id', $facilitatorId)
                        ->whereNull('deleted_at')
                        ->exists();

                    if ($hasAssignments) {
                        $validator->errors()->add('role_id', 'No se puede cambiar el rol: este facilitador está asignado a acciones de formación programadas.');
                        return;
                    }
                }
            }

            if ($oldRoleId === Role::PARTICIPANTE && $newRoleId !== Role::PARTICIPANTE) {
                $hasParticipation = DB::table('participants')
                    ->where('person_id', $personId)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($hasParticipation) {
                    $validator->errors()->add('role_id', 'No se puede cambiar el rol: esta persona tiene registros de participación vinculados.');
                }
            }
        });
    }
}
