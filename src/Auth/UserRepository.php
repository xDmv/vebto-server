<?php namespace Vebto\Auth;

use Vebto\Groups\Group;
use Vebto\Settings\Settings;

class UserRepository {

    /**
     * User model instance.
     *
     * @var User
     */
    private $user;

    /**
     * Group model instance.
     *
     * @var Group
     */
    private $group;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * UserRepository constructor.
     *
     * @param User $user
     * @param Group $group
     * @param Settings $settings
     */
    public function __construct(
        User $user,
        Group $group,
        Settings $settings
    )
    {
        $this->user  = $user;
        $this->group = $group;
        $this->settings = $settings;
    }

    /**
     * Find user with given id or throw an error.
     *
     * @param integer $id
     * @param array $lazyLoad
     * @return User
     */
    public function findOrFail($id, $lazyLoad = [])
    {
        return $this->user->with($lazyLoad)->findOrFail($id);
    }

    /**
     * Paginate all users using given params.
     *
     * @param array $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateUsers($params)
    {
        $orderBy    = isset($params['order_by']) ? $params['order_by'] : 'created_at';
        $orderDir   = isset($params['order_dir']) ? $params['order_dir'] : 'desc';
        $perPage    = isset($params['per_page']) ? $params['per_page'] : 13;
        $searchTerm = isset($params['query']) ? $params['query'] : null;
        $groupId    = isset($params['group_id']) ? (int) $params['group_id'] : null;
        $groupName  = isset($params['group_name']) ? $params['group_name'] : null;
	$subscription  = isset($params['subscription']) ? $params['subscription'] : null;        

        $query = $this->user->with('groups');
		
		if ($subscription && $subscription != 'all') {
			if ($subscription == 'active')
				$query->where('subscription_s', 1);
			if ($subscription == 'without')
				$query->where('subscription', '0000-00-00 00:00:00');
			if ($subscription == 'expired') {
				$query->where('subscription_s', 0);
				$query->where('subscription','<>', '0000-00-00 00:00:00');
				$query->whereNotNull('subscription');
			}	
        }

        if ($searchTerm) {
            $query->where('email', 'LIKE', "%$searchTerm%");
        }

        if ($groupId) {
            $query->whereHas('groups', function($q) use($groupId) {
                $q->where('groups.id', $groupId);
            });
        }

        if ($groupName) {
            $query->whereHas('groups', function($q) use($groupName) {
                $q->where('groups.name', $groupName);
            });
        }

        return $query->orderBy($orderBy, $orderDir)->paginate($perPage);
    }

    /**
     * Return first user matching attributes or create a new one.
     *
     * @param array $params
     * @return User
     */
    public function firstOrCreate($params)
    {
        $user = $this->user->where('email', $params['email'])->first();

        if (is_null($user)) {
            $user = $this->user->where('phone', $params['phone'])->first();
            if (is_null($user)) {
                $user = $this->create($params);
            }
        }
        
        return $user;
    }
    
    /**
     * Return first user matching phone or throw an error.
     *
     * @param str $params
     * @return User
     */
    public function firstPhoneOrFail($params)
    {
        $user = $this->user->where('phone', $params)->first();

        return $user;
    }
    
    /**
     * Return first user matching phone or throw an error.
     *
     * @param str $params
     * @return User
     */
    public function firstEmailOrFail($params)
    {
        $user = $this->user->where('email', $params)->first();

        return $user;
    }

    /**
     * Create a new user and assign default customer group to it.
     *
     * @throws \Exception
     *
     * @param array $params
     * @return User
     */
    public function create($params)
    {
        /** @var User $user */
        $user = User::forceCreate($this->formatParams($params));

        try {
            if ( ! isset($params['groups']) || ! $this->attachGroups($user, $params['groups'])) {
                $this->assignDefaultGroup($user);
            }
        } catch (\Exception $e) {
            //delete user if there were any errors creating/assigning
            //purchase codes or groups, so there are no artifacts left
            $user->delete();
            throw($e);
        }

        return $user;
    }

    /**
     * Update given user.
     *
     * @param User $user
     * @param array $params
     *
     * @return User
     */
    public function update(User $user, $params)
    {
        $user->forceFill($this->formatParams($params, 'update'))->save();

        if (isset($params['groups'])) {
            $this->attachGroups($user, $params['groups']);
        }

        return $user->load('groups');
    }
    
     /**
     * Update verify phone.
     *
     * @param Phoneveryfy $phoneveryfy
     * @param array $params
     *
     * @return Phoneveryfy
     */
    public function updatePhoneCode(User $user, $params)
    {
        $user->forceFill($params)->save();
        
        return $user;
    }

    /**
     * Delete multiple users.
     *
     * @param array $ids
     * @return bool|null
     */
    public function deleteMultiple($ids)
    {
        foreach ($ids as $id) {
            $user = $this->user->find($id);
            if (is_null($user)) continue;

            $user->social_profiles()->delete();
            $user->groups()->detach();
            $user->delete();
        }

        return $ids;
    }

    /**
     * Prepare given params for inserting into database.
     *
     * @param array $params
     * @param string $type
     * @return array
     */
    private function formatParams($params, $type = 'create')
    {
        $formatted = [
            'avatar'      => isset($params['avatar']) ? $params['avatar'] : null,
            'first_name'  => isset($params['first_name']) ? $params['first_name'] : null,
            'last_name'   => isset($params['last_name']) ? $params['last_name'] : null,
            'language'    => isset($params['language']) ? $params['language'] : $this->settings->get('i18n.default_localization'),
            'country'     => isset($params['country']) ? $params['country'] : null,
            'timezone'    => isset($params['timezone']) ? $params['timezone'] : null,
            'confirmed'   => isset($params['confirmed']) ? $params['confirmed'] : 1,
            'confirmation_code' => isset($params['confirmation_code']) ? $params['confirmation_code'] : null,            
            'company'     => isset($params['company']) ? $params['company'] : null,
            'phone'     => isset($params['phone']) && $params['phone'] != '' ? $params['phone'] : null,
            'session_id'  => isset($params['session_id']) && $params['session_id'] != '' ? $params['session_id'] : null,
        ];

        //cast permission values to integer
        if (isset($params['permissions'])) {
            $formatted['permissions'] = array_map(function($value) {
                return (int) $value;
            }, $params['permissions']);
        }

        if ($type === 'create') {                        
            $formatted['email']    = isset($params['email']) ? $params['email'] : null;            
            $formatted['password'] = isset($params['password']) ? bcrypt($params['password']) : null;            
        }

        return $formatted;
    }

    /**
     * Assign groups to user, if any are given.
     *
     * @param User  $user
     * @param array $groups
     * @type string $type
     *
     * @return int
     */
    public function attachGroups(User $user, $groups, $type = 'sync')
    {
        $groupIds = $this->group->whereIn('id', $groups)->get()->pluck('id');
        return $user->groups()->$type($groupIds);
    }

    /**
     * Detach specified groups from user.
     *
     * @param User $user
     * @param int[] $groups
     *
     * @return int
     */
    public function detachGroups(User $user, $groups)
    {
        return $user->groups()->detach($groups);
    }

    /**
     * Add specified permissions to user.
     *
     * @param User $user
     * @param array $permissions
     * @return User
     */
    public function addPermissions(User $user, $permissions)
    {
        $existing = $user->permissions;

        foreach ($permissions as $permission) {
            $existing[$permission] = 1;
        }

        $user->forceFill(['permissions' => $existing])->save();

        return $user;
    }

    /**
     * Remove specified permissions from user.
     *
     * @param User $user
     * @param array $permissions
     * @return User
     */
    public function removePermissions(User $user, $permissions)
    {
        $existing = $user->permissions;

        foreach ($permissions as $permission) {
            unset($existing[$permission]);
        }

        $user->forceFill(['permissions' => $existing])->save();

        return $user;
    }

    /**
     * Assign default group to given user.
     *
     * @param User $user
     */
    private function assignDefaultGroup(User $user)
    {
        $defaultGroup = $this->group->getDefaultGroup();

        if ($defaultGroup) {
            $user->groups()->attach($defaultGroup->id);
        }
    }
}