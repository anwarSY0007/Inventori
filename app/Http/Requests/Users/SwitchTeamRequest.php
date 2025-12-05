<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class SwitchTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_id' => ['required', 'string', 'exists:teams,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'team_id.required' => 'Team ID is required',
            'team_id.exists' => 'Team not found',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $teamId = $this->input('team_id');

            if (!$user->teams()->where('teams.id', $teamId)->exists()) {
                $validator->errors()->add('team_id', 'You are not a member of this team');
            }
        });
    }
}
