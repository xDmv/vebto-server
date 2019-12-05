<?php namespace Vebto\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppearancePolicy
{
    use HandlesAuthorization;

    public function update(User $user)
    {
        return $user->hasPermission('appearance.update');
    }
}
