<?php

namespace App\Policies;

use App\Models\Delivery;
use App\Models\User;

class DeliveryPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_ADMIN,
            User::ROLE_GESTOR,
            User::ROLE_AUDITOR,
            User::ROLE_FARMACIA,
            User::ROLE_ENTREGADOR,
        ], true);
    }

    public function update(User $user, Delivery $delivery): bool
    {
        if (! in_array($user->role, [User::ROLE_ADMIN, User::ROLE_FARMACIA, User::ROLE_ENTREGADOR], true)) {
            return false;
        }

        if ($this->isCentral($user)) {
            return true;
        }

        return (int) optional(optional($delivery->prescription)->attendance)->health_unit_id === (int) $user->health_unit_id;
    }

    private function isCentral(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_GESTOR, User::ROLE_AUDITOR], true);
    }
}
