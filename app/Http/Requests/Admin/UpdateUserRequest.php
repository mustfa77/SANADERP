<?php

namespace App\Http\Requests\Admin;

use App\Models\User;

class UpdateUserRequest extends UserRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->target());
    }

    protected function target(): ?User
    {
        return $this->route('user');
    }
}
