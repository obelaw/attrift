<?php

namespace Obelaw\Attrify\Concerns;

use Obelaw\Attrify\Models\Attrify;


trait HasAttrify
{
    /**
     * Cache for the attrify attributes.
     *
     * @var \Illuminate\Support\Collection|null
     */
    protected $attrifyCache = null;

    /**
     * The "booting" method of the trait.
     *
     * @return void
     */
    protected static function bootHasAttrify()
    {
        static::creating(function ($model) {
            $model->syncAttrify();
        });

        static::updating(function ($model) {
            $model->syncAttrify();
        });
    }

    /**
     * Sync the attrify attributes from the model's attributes.
     *
     * @return void
     */
    public function syncAttrify()
    {
        // Get only the dirty attributes that are in our fillableAttrify array
        $attrifyData = array_intersect_key($this->getDirty(), array_flip($this->fillableAttrify ?? []));

        foreach ($attrifyData as $key => $value) {
            $this->attrifys()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Unset the attributes from the main model to prevent them from being saved to the main table
        foreach ($this->fillableAttrify ?? [] as $attr) {
            if (array_key_exists($attr, $this->attributes)) {
                unset($this->attributes[$attr]);
            }
        }
    }

    /**
     * Initialize the trait for an instance.
     *
     * @return void
     */
    protected function initializeHasAttrify()
    {
        // The fillableAttrify property must be defined on the model using this trait.
        if (!isset($this->fillableAttrify)) {
            $this->fillableAttrify = [];
        }
    }

    /**
     * Load the attrify attributes from the database.
     */
    protected function loadAttrify()
    {
        if (is_null($this->attrifyCache)) {
            $this->attrifyCache = $this->attrifys()->pluck('value', 'key');
        }
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (in_array($key, $this->fillableAttrify)) {
            $this->loadAttrify();
            return $this->attrifyCache[$key] ?? null;
        }

        return parent::getAttribute($key);
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->fillableAttrify)) {
            $this->loadAttrify();
            $this->attrifyCache[$key] = $value;
            // Also set it on the main attributes array so getDirty() picks it up.
            // It will be unset before saving to the main table in syncAttrify().
            $this->attributes[$key] = $value;
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function getAttrifys()
    {
        $this->loadAttrify();
        return $this->attrifyCache;
    }

    /**
     * Get the relationship for the attrify attributes.
     */
    public function attrifys()
    {
        return $this->morphMany(Attrify::class, 'modelable');
    }

    /**
     * Scope a query to only include models where the attrify key-value matches.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $q
     * @param  string  $key
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereAttrify($q, $key, $value)
    {
        return $q->whereHas('attrifys', fn($s) => $s->where('key', $key)->where('value', $value));
    }

    /**
     * Scope a query to only include models where the attrify key-value is like the given pattern.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $q
     * @param  string  $key
     * @param  string  $like
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereAttrifyLike($q, $key, $like)
    {
        return $q->whereHas('attrifys', fn($s) => $s->where('key', $key)->where('value', 'like', $like));
    }

    /**
     * Scope a query to order by an attrify value.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $q
     * @param  string  $key
     * @param  string  $dir
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByAttrify($q, $key, $dir = 'asc')
    {
        return $q->whereHas('attrifys', fn($s) => $s->where('key', $key))
            ->withAggregate(['attrifys as _attrift' . $key => fn($s) => $s->select('value')], 'value')
            ->orderBy('_attrift' . $key, $dir);
    }

    /**
     * Scope a query to only include models where the JSON attrify value matches.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $q
     * @param  string  $key
     * @param  string  $jsonPath
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereAttrifyJson($q, $key, $jsonPath, $value)
    {
        return $q->whereHas('attrifys', fn($s) => $s->where('key', $key)->where("value->$jsonPath", $value));
    }

    /**
     * Scope a query to only include models where the JSON attrify value is like the given pattern.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $q
     * @param  string  $key
     * @param  string  $jsonPath
     * @param  string  $like
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereAttrifyJsonLike($q, $key, $jsonPath, $like)
    {
        return $q->whereHas('attrifys', fn($s) => $s->where('key', $key)->where("value->$jsonPath", 'like', $like));
    }

    /**
     * Scope a query to only include models where a JSON path exists in the attrify value.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $q
     * @param  string  $key
     * @param  string  $jsonPath
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereAttrifyJsonContains($q, $key, $jsonPath, $value)
    {
        return $q->whereHas('attrifys', fn($s) => $s->where('key', $key)->whereJsonContains("value->$jsonPath", $value));
    }

    /**
     * Scope a query to only include models where a JSON path has a specific length.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $q
     * @param  string  $key
     * @param  string  $jsonPath
     * @param  int  $length
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereAttrifyJsonLength($q, $key, $jsonPath, $length)
    {
        return $q->whereHas('attrifys', fn($s) => $s->where('key', $key)->whereJsonLength("value->$jsonPath", $length));
    }

    /**
     * Scope a query to order by a JSON value in attrify.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $q
     * @param  string  $key
     * @param  string  $jsonPath
     * @param  string  $dir
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByAttrifyJson($q, $key, $jsonPath, $dir = 'asc')
    {
        return $q->whereHas('attrifys', fn($s) => $s->where('key', $key))
            ->withAggregate(['attrifys as _attrift' . $key . '_' . str_replace('.', '_', $jsonPath) => fn($s) => $s->select("value->$jsonPath")], "value->$jsonPath")
            ->orderBy('_attrift' . $key . '_' . str_replace('.', '_', $jsonPath), $dir);
    }
}
