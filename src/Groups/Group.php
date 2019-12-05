<?php namespace Vebto\Groups;

use Illuminate\Database\Eloquent\Model;
use Vebto\Auth\FormatsPermissions;

/**
 * App\Group
 *
 * @property integer $id
 * @property string $name
 * @property string $permissions
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property boolean $default
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users
 * @method static \Illuminate\Database\Query\Builder|\App\Group whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Group whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Group wherePermissions($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Group whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Group whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Group whereDefault($value)
 * @mixin \Eloquent
 * @property int $guests
 * @method static \Illuminate\Database\Query\Builder|\App\Group whereGuests($value)
 */
class Group extends Model
{
    use FormatsPermissions;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden   = ['pivot'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'default' => 'boolean', 'guests' => 'boolean'];

    /**
     * Get default group for assigning to new users.
     *
     * @return Group|null
     */
    public function getDefaultGroup()
    {
        return $this->where('default', 1)->first();
    }

    /**
     * Users belonging to this group.
     */
    public function users()
    {
        return $this->belongsToMany('App\User', 'user_group');
    }
}
