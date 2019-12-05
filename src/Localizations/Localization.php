<?php namespace Vebto\Localizations;

use Illuminate\Database\Eloquent\Model;

/**
 * Vebto\Localizations\Localization
 *
 * @property int $id
 * @property string $name
 * @property string $lines
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\Vebto\Localizations\Localization whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Vebto\Localizations\Localization whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Vebto\Localizations\Localization whereLines($value)
 * @method static \Illuminate\Database\Query\Builder|\Vebto\Localizations\Localization whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\Vebto\Localizations\Localization whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Localization extends Model
{
    protected $guarded = ['id'];

    /**
     * Decode lines json attribute.
     *
     * @param string $text
     * @return array
     */
    public function getLinesAttribute($text) {
        if ( ! $text) return [];

        return json_decode($text, true);
    }
}
