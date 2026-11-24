<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'max:20'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            // Foto en el mismo formulario (23/11/2026): opcional, se guarda con "Guardar".
            'foto_perfil' => [
                'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120',
                'dimensions:max_width=3000,max_height=3000',
            ],
            'quitar_foto' => ['nullable', 'boolean'],
            // Receta en PDF (24/11/2026): la cédula profesional sale en cada receta.
            'cedula_profesional' => ['nullable', 'string', 'max:20', 'regex:/^[0-9A-Za-z-]+$/'],
            'especialidad' => ['nullable', 'string', 'max:100'],
        ];
    }
}
