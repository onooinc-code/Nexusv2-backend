<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Base Model for all Nexus application models
 * Provides common functionality and JSON attribute handling
 */
class BaseModel extends Model
{
    use HasFactory;
    /**
     * Cast JSON attributes to arrays/objects
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'json',
        'attributes' => 'json',
        'settings' => 'json',
        'config' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Default attributes for new models
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The storage format of the model's date columns
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * Get the primary key for the model
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->primaryKey ?? 'id';
    }

    /**
     * Generate a UUID for the model
     *
     * @return string
     */
    public static function generateUuid(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Boot the model and populate string primary keys with UUIDs
     */
    protected static function booted(): void
    {
        static::creating(function ($model) {
            $keyName = $model->getKeyName();

            if (! $model->getIncrementing() && $model->getKeyType() === 'string' && empty($model->{$keyName})) {
                $model->{$keyName} = (string) Str::uuid();
            }
        });
    }

    /**
     * Scope to filter by status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|array $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, $status)
    {
        if (is_array($status)) {
            return $query->whereIn('status', $status);
        }

        return $query->where('status', $status);
    }

    /**
     * Scope to get active records
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get inactive records
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Get a JSON attribute value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getJsonAttribute($key, $default = null)
    {
        $data = $this->attributes[$key] ?? null;

        if (is_null($data)) {
            return $default;
        }

        return is_array($data) ? $data : json_decode($data, true);
    }

    /**
     * Set a JSON attribute value
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setJsonAttribute($key, $value)
    {
        $this->attributes[$key] = is_array($value) ? json_encode($value) : $value;
        return $this;
    }

    /**
     * Convert the model to an array
     *
     * @return array
     */
    public function toArray()
    {
        return parent::toArray();
    }

    /**
     * Convert the model to JSON
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
}
