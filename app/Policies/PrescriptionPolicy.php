<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_ADMIN,
            User::ROLE_GESTOR,
            User::ROLE_AUDITOR,
            User::ROLE_MEDICO_UBS,
            User::ROLE_MEDICO_HOSPITAL,
            User::ROLE_FARMACIA,
        ], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MEDICO_UBS, User::ROLE_MEDICO_HOSPITAL], true);
    }

    public function dispense(User $user, Prescription $prescription): bool
    {
        if (! in_array($user->role, [User::ROLE_ADMIN, User::ROLE_FARMACIA], true)) {
            return false;
        }

        if ($this->isCentral($user)) {
            return true;
        }

        return (int) optional($prescription->attendance)->health_unit_id === (int) $user->health_unit_id;
    }

    private function isCentral(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_GESTOR, User::ROLE_AUDITOR], true);
    }
}
