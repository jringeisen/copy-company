<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OAuthTokenContext extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'oauth_token_contexts';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'access_token_id';

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'access_token_id',
        'brand_id',
    ];

    /**
     * Get the brand associated with this token context.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
