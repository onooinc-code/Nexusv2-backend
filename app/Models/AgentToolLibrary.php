<?php

namespace App\Models;

class AgentToolLibrary extends BaseModel
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'agent_tools_library';

    protected $fillable = [
        'id',
        'name',
        'description',
        'category',
        'type',
        'is_system',
        'config',
    ];

    protected $casts = [
        'config' => 'json',
        'is_system' => 'boolean',
    ];

    /**
     * Scope to system tools only.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get tools by category, grouped.
     */
    public static function getGroupedByCategory()
    {
        return static::all()->groupBy('category');
    }
}
