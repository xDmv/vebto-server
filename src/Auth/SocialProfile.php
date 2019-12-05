<?php namespace Vebto\Auth;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\SocialProfile
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $service_name
 * @property string $user_service_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\User $user
 * @method static Builder|SocialProfile whereId($value)
 * @method static \Illuminate\Database\Query\Builder|SocialProfile whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|SocialProfile whereServiceName($value)
 * @method static \Illuminate\Database\Query\Builder|SocialProfile whereUserServiceId($value)
 * @method static \Illuminate\Database\Query\Builder|SocialProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|SocialProfile whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string $username
 * @method static \Illuminate\Database\Query\Builder|SocialProfile whereUsername($value)
 */
class SocialProfile extends Model {

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
