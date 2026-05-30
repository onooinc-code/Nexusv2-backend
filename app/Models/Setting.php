<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Setting Model
 *
 * Stores application-wide configuration settings as key-value pairs.
 * Supports grouping, types, visibility, and multi-tenancy (scope, workspace, user).
 *
 * @property int $id
 * @property string $key
 * @property mixed $value
 * @property string $type
 * @property string $group
 * @property bool $is_public
 * @property bool $is_encrypted
 * @property string $scope
 * @property int $workspace_id
 * @property int $user_id
 * @property string $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Setting extends Model
{
   use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'is_public',
        'is_encrypted',
        'scope',
        'workspace_id',
        'user_id',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'json',
        'is_public' => 'boolean',
        'is_encrypted' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope constants for multi-tenancy.
     */
    public const SCOPE_GLOBAL = 'global';
    public const SCOPE_WORKSPACE = 'workspace';
    public const SCOPE_USER = 'user';

    /**
     * Setting type constants.
     */
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_JSON = 'json';
    public const TYPE_TEXT = 'text';

    /**
     * Setting group constants.
     */
    public const GROUP_GENERAL = 'general';
    public const GROUP_SECURITY = 'security';
    public const GROUP_AI = 'ai';
    public const GROUP_NOTIFICATIONS = 'notifications';
    public const GROUP_INTEGRATIONS = 'integrations';
    public const GROUP_UI = 'ui';

    /**
     * Get the workspace that owns this setting.
     */
    public function workspace()
    {
        return $this->belongsTo(\App\Models\Workspace::class);
    }

    /**
     * Get the user that owns this setting.
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Scope to filter by scope (global, workspace, user).
     */
    public function scopeByScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Scope to filter by workspace.
     */
    public function scopeByWorkspace($query, int $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get only global settings.
     */
    public function scopeGlobal($query)
    {
        return $query->where('scope', self::SCOPE_GLOBAL);
    }

    /**
     * Scope to get settings visible to a user (global + workspace + user-specific).
     */
    public function scopeVisibleTo($query, int $userId, ?int $workspaceId = null)
    {
        return $query->where(function ($q) use ($userId, $workspaceId) {
            $q->where('scope', self::SCOPE_GLOBAL)
              ->orWhere(function ($q2) use ($workspaceId) {
                  if ($workspaceId) {
                      $q2->where('scope', self::SCOPE_WORKSPACE)
                         ->where('workspace_id', $workspaceId);
                  }
              })
              ->orWhere(function ($q3) use ($userId) {
                  $q3->where('scope', self::SCOPE_USER)
                     ->where('user_id', $userId);
              });
        });
    }

    /**
     * Scope to filter by group.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $group
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope to filter by type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get public settings only.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get private settings only.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Get the typed value of the setting.
     *
     * @return mixed
     */
    public function getTypedValue()
    {
        return match ($this->type) {
            self::TYPE_BOOLEAN => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_INTEGER => (int) $this->value,
            self::TYPE_JSON => is_string($this->value) ? json_decode($this->value, true) : $this->value,
            self::TYPE_TEXT => (string) $this->value,
            default => (string) $this->value,
        };
    }

    /**
     * Set the value with automatic type casting.
     *
     * @param mixed $value
     * @return void
     */
    public function setTypedValue($value): void
    {
        $this->value = match ($this->type) {
            self::TYPE_BOOLEAN => (bool) $value,
            self::TYPE_INTEGER => (int) $value,
            self::TYPE_JSON => is_array($value) ? $value : json_decode($value, true),
            default => (string) $value,
        };
    }

    /**
     * Get the label for the setting group.
     *
     * @return string
     */
    public function getGroupLabelAttribute(): string
    {
        return match ($this->group) {
            self::GROUP_GENERAL => 'General',
            self::GROUP_SECURITY => 'Security',
            self::GROUP_AI => 'AI Configuration',
            self::GROUP_NOTIFICATIONS => 'Notifications',
            self::GROUP_INTEGRATIONS => 'Integrations',
            self::GROUP_UI => 'User Interface',
            default => ucfirst($this->group),
        };
    }
}
