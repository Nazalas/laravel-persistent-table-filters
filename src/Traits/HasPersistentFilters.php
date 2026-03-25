<?php

namespace Nazalas\PersistentTableFilters\Traits;

use Illuminate\Support\Collection;
use Nazalas\PersistentTableFilters\Models\TableFilter;

/**
 * Add to any Livewire component (or plain class) to get persistent filter support.
 *
 * Usage in a Livewire component:
 *
 *   use HasPersistentFilters;
 *
 *   protected string $filterResource = 'campaigns';
 *   protected array $filterKeys = ['search', 'sort_by', 'sort_dir', 'status'];
 *   protected bool $autoSaveFilters = true;  // optional, default true
 *
 * Then in mount():
 *   $this->restoreFilters();
 *
 * To auto-save on every filter change, call $this->persistFilters() from your
 * updatedSearch(), updatedStatus(), etc. hooks — or call it anywhere that mutates filter state.
 */
trait HasPersistentFilters
{
    // Do NOT declare $filterResource, $filterKeys, or $autoSaveFilters here —
    // define them in your class:
    //
    //   protected string $filterResource = 'campaigns';
    //   protected array $filterKeys = ['search', 'sort_by', 'sort_dir'];
    //   protected bool $autoSaveFilters = true;  // set false to disable auto-persist
    //

    // -------------------------------------------------------------------------
    // Auto-persist (silent, per-user per-resource last state)
    // -------------------------------------------------------------------------

    /**
     * Restore the last auto-persisted filter state on mount.
     * Falls back to the default named filter if no auto-state exists.
     * Call this from mount() in your component.
     */
    public function restoreFilters(): void
    {
        // Try auto-persisted state first
        $auto = TableFilter::forCurrentUser()
            ->forResource($this->getFilterResource())
            ->where('is_auto', true)
            ->first();

        if ($auto) {
            $this->applyFilterState($auto->filters);
            return;
        }

        // Fall back to the named default, if any
        $default = TableFilter::forCurrentUser()
            ->forResource($this->getFilterResource())
            ->where('is_default', true)
            ->first();

        if ($default) {
            $this->applyFilterState($default->filters);
        }
    }

    /**
     * Silently persist the current filter state.
     * Call this from updatedSearch(), updatedStatus(), etc. — or from any method
     * that changes filter state. Only runs if $autoSaveFilters !== false.
     */
    public function persistFilters(): void
    {
        if (property_exists($this, 'autoSaveFilters') && $this->autoSaveFilters === false) {
            return;
        }

        if (! auth()->check()) {
            return;
        }

        TableFilter::updateOrCreate(
            [
                'user_id'  => auth()->id(),
                'resource' => $this->getFilterResource(),
                'is_auto'  => true,
            ],
            [
                'label'   => null,
                'filters' => $this->captureFilterState(),
            ]
        );
    }

    /**
     * Clear the auto-persisted state for this resource.
     */
    public function clearPersistedFilters(): void
    {
        TableFilter::forCurrentUser()
            ->forResource($this->getFilterResource())
            ->where('is_auto', true)
            ->delete();
    }

    // -------------------------------------------------------------------------
    // Named presets
    // -------------------------------------------------------------------------

    /**
     * @deprecated Use restoreFilters() instead — it handles both auto-state and named defaults.
     */
    public function loadDefaultFilter(): void
    {
        $this->restoreFilters();
    }

    /**
     * Save the current filter state as a named preset.
     */
    public function saveFilter(string $label, bool $setAsDefault = false): TableFilter
    {
        $filter = TableFilter::create([
            'user_id'   => auth()->id(),
            'resource'  => $this->getFilterResource(),
            'label'     => $label,
            'filters'   => $this->captureFilterState(),
            'is_default' => false,
            'is_auto'   => false,
        ]);

        if ($setAsDefault) {
            $filter->setAsDefault();
        }

        return $filter;
    }

    /**
     * Load a saved named filter by ID and apply it.
     */
    public function loadFilter(int $id): void
    {
        $filter = TableFilter::forCurrentUser()
            ->forResource($this->getFilterResource())
            ->where('is_auto', false)
            ->findOrFail($id);

        $this->applyFilterState($filter->filters);

        // Persist the newly loaded state as the auto-state too
        $this->persistFilters();
    }

    /**
     * Delete a saved named filter by ID.
     */
    public function deleteFilter(int $id): void
    {
        TableFilter::forCurrentUser()
            ->forResource($this->getFilterResource())
            ->where('is_auto', false)
            ->where('id', $id)
            ->delete();
    }

    /**
     * Get all saved named filters for the current user + resource.
     */
    public function getSavedFilters(): Collection
    {
        return TableFilter::forCurrentUser()
            ->forResource($this->getFilterResource())
            ->where('is_auto', false)
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    protected function captureFilterState(): array
    {
        $state = [];

        foreach ($this->getFilterKeys() as $key) {
            $state[$key] = $this->{$key} ?? null;
        }

        return $state;
    }

    protected function applyFilterState(array $state): void
    {
        foreach ($this->getFilterKeys() as $key) {
            if (array_key_exists($key, $state)) {
                $this->{$key} = $state[$key];
            }
        }
    }

    protected function getFilterResource(): string
    {
        if (! empty($this->filterResource)) {
            return $this->filterResource;
        }

        return strtolower(class_basename(static::class));
    }

    protected function getFilterKeys(): array
    {
        return $this->filterKeys;
    }
}
