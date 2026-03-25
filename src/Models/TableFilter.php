<?php

namespace Nazalas\PersistentTableFilters\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableFilter extends Model
{
    protected $fillable = [
        'user_id',
        'resource',
        'label',
        'filters',
        'is_default',
        'is_auto',
    ];

    protected $casts = [
        'filters'    => 'array',
        'is_default' => 'boolean',
        'is_auto'    => 'boolean',
    ];

    public function getTable(): string
    {
        return config('persistent-table-filters.table_name', 'table_filters');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class));
    }

    /**
     * Scope to a specific resource (e.g. 'campaigns', 'orders').
     */
    public function scopeForResource($query, string $resource)
    {
        return $query->where('resource', $resource);
    }

    /**
     * Scope to the authenticated user.
     */
    public function scopeForCurrentUser($query)
    {
        return $query->where('user_id', auth()->id());
    }

    /**
     * Mark this filter as the default, clearing others for the same resource.
     */
    public function setAsDefault(): void
    {
        static::where('user_id', $this->user_id)
            ->where('resource', $this->resource)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}
