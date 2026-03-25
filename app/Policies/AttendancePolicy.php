<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_ADMIN,
            User::ROLE_GESTOR,
            User::ROLE_AUDITOR,
            User::ROLE_RECEPCAO,
            User::ROLE_ENFERMAGEM,
            User::ROLE_MEDICO_UBS,
            User::ROLE_MEDICO_HOSPITAL,
        ], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_RECEPCAO], true);
    }

    public function triage(User $user, Attendance $attendance): bool
    {
        if (! in_array($user->role, [User::ROLE_ADMIN, User::ROLE_ENFERMAGEM], true)) {
            return false;
        }

        return $this->isCentral($user) || (int) $attendance->health_unit_id === (int) $user->health_unit_id;
    }

    public function prescribe(User $user, Attendance $attendance): bool
    {
        if (! in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MEDICO_UBS, User::ROLE_MEDICO_HOSPITAL], true)) {
            return false;
        }

        return $this->isCentral($user) || (int) $attendance->health_unit_id === (int) $user->health_unit_id;
    }

    private function isCentral(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_GESTOR, User::ROLE_AUDITOR], true);
    }
}
