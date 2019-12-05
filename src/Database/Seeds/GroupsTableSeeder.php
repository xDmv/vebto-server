<?php namespace Vebto\Database\Seeds;

use DB;
use Carbon\Carbon;
use Vebto\Auth\User;
use Vebto\Groups\Group;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Collection;

class GroupsTableSeeder extends Seeder
{
    /**
     * @var Group
     */
    private $group;

    /**
     * @var User
     */
    private $user;

    /**
     * GroupsTableSeeder constructor.
     *
     * @param Group $group
     * @param User $user
     */
    public function __construct(Group $group, User $user)
    {
        $this->user = $user;
        $this->group = $group;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if ( ! $this->group->where('name', 'guests')->orWhere('guests', 1)->first()) {
            $this->group->create(['name' => 'guests', 'permissions' => json_encode(config('vebto.permissions.groups.guests')), 'guests' => 1]);
        }

        if ( ! $users = $this->group->where('name', 'users')->orWhere('default', 1)->first()) {
            $users = $this->group->create(['name' => 'users', 'permissions' => json_encode(config('vebto.permissions.groups.users')), 'default' => 1]);
        }

        $this->attachUsersGroupToExistingUsers($users);
    }

    /**
     * Attach default user's group to all existing users.
     *
     * @param Group $group
     */
    private function attachUsersGroupToExistingUsers(Group $group)
    {
        $this->user->with('groups')->orderBy('id', 'desc')->select('id')->chunk(500, function(Collection $users) use($group) {
            $insert = $users->filter(function(User $user) use ($group) {
                return ! $user->groups->contains('id', $group->id);
            })->map(function(User $user) use($group) {
                return ['user_id' => $user->id, 'group_id' => $group->id, 'created_at' => Carbon::now()];
            })->toArray();

            DB::table('user_group')->insert($insert);
        });
    }
}
