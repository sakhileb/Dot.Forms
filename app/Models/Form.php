<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'user_id',
        'title',
        'slug',
        'description',
        'logo_path',
        'views_count',
        'current_version',
        'settings',
        'is_published',
        'published_at',
        'archived_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
            'views_count' => 'integer',
            'current_version' => 'integer',
        ];
    }

    /**
     * Get the team that owns the form.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user that created the form.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all fields for the form.
     */
    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class);
    }

    /**
     * Get all submissions for the form.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    /**
     * Get all AI suggestions linked to the form.
     */
    public function aiSuggestions(): HasMany
    {
        return $this->hasMany(AiSuggestion::class);
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(FormUserRole::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FormVersion::class);
    }

    public function editableBy(User $user): bool
    {
        if ((int) $this->user_id === (int) $user->id || $user->ownsTeam($this->team)) {
            return true;
        }

        $role = $this->userRoles()->where('user_id', $user->id)->value('role');

        return in_array($role, ['owner', 'editor'], true);
    }

    public function viewableSubmissionsBy(User $user): bool
    {
        if ($this->editableBy($user)) {
            return true;
        }

        $role = $this->userRoles()->where('user_id', $user->id)->value('role');

        return in_array($role, ['viewer'], true);
    }

    public function availableCollaborators(): Collection
    {
        $members = $this->team->users()->get();

        if ($this->team->owner && ! $members->contains('id', $this->team->owner->id)) {
            $members->prepend($this->team->owner);
        }

        return $members->unique('id')->values();
    }
}
