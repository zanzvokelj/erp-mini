<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Policies\Concerns\HandlesCompanyAuthorization;

class OrderPolicy
{
    use HandlesCompanyAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.view') && $this->sameCompany($user, $order);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('orders.create');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.update') && $this->sameCompany($user, $order);
    }

    public function confirm(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.confirm') && $this->sameCompany($user, $order);
    }

    public function ship(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.ship') && $this->sameCompany($user, $order);
    }

    public function complete(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.complete') && $this->sameCompany($user, $order);
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.cancel') && $this->sameCompany($user, $order);
    }

    public function returnOrder(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.return') && $this->sameCompany($user, $order);
    }
}
