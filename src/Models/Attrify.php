<?php

namespace Obelaw\Attrify\Models;

use Illuminate\Database\Eloquent\Model;

class Attrify extends Model
{
    protected $table = 'obelaw_attrifys';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    public function modelable()
    {
        return $this->morphTo();
    }
}
