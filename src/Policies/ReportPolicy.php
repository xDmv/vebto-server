<?php namespace Vebto\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {
        return $user->hasPermission('reports.view');
    }
}
