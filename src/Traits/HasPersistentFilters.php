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
 *   protected string $filterResource = 'campaigns'; // unique key for this table
 *   protected array $filterKeys = ['search', 'sort_by', 'sort_dir', 'status']; // which properties to persist
 */
trait HasPersistentFilters
{
    /**
     * The resource key for this table (e.g. 'campaigns', 'orders').
     * Override in your component.
     */
    protected string $filterResource = '';

    /**
     * The component property names that make up filter state.
     * Override in your component to specify which properties to save/restore.
     */
    protected array $filterKeys = [];

    /**
     * Load the default saved filter for this resource on mount, if one exists.
     */
    public function loadDefaultFilter(): void
    {
        $default = TableFilter::forCurrentUser()
            ->forResource($this->getFilterResource())
            ->where('is_default', true)
            ->first();

        if ($default) {
            $this->applyFilterState($default->filters);
        }
    }

    /**
     * Save the current filter state with a label.
     */
    public function saveFilter(string $label, bool $setAsDefault = false): TableFilter
    {
        $filter = TableFilter::create([
            'user_id'  => auth()->id(),
            'resource' => $this->getFilterResource(),
            'label'    => $label,
            'filters'  => $this->captureFilterState(),
            'is_default' => false,
        ]);

        if ($setAsDefault) {
            $filter->setAsDefault();
        }

        return $filter;
    }

    /**
     * Load a saved filter by ID and apply it.
     */
    public function loadFilter(int $id): void
    {
        $filter = TableFilter::forCurrentUser()
            ->forResource($this->getFilterResource())
            ->findOrFail($id);

        $this->applyFilterState($filter->filters);
    }

    /**
     * Delete a saved filter by ID.
     */
    public function deleteFilter(int $id): void
    {
        TableFilter::forCurrentUser()
            ->forResource($this->getFilterResource())
            ->where('id', $id)
            ->delete();
    }

    /**
     * Get all saved filters for the current user + resource.
     */
    public function getSavedFilters(): Collection
    {
        return TableFilter::forCurrentUser()
            ->forResource($this->getFilterResource())
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get();
    }

    /**
     * Capture current filter state from the component's properties.
     */
    protected function captureFilterState(): array
    {
        $state = [];

        foreach ($this->getFilterKeys() as $key) {
            $state[$key] = $this->{$key} ?? null;
        }

        return $state;
    }

    /**
     * Apply a filter state array back onto the component's properties.
     */
    protected function applyFilterState(array $state): void
    {
        foreach ($this->getFilterKeys() as $key) {
            if (array_key_exists($key, $state)) {
                $this->{$key} = $state[$key];
            }
        }
    }

    /**
     * Resolve the resource key, falling back to the class name.
     */
    protected function getFilterResource(): string
    {
        if (! empty($this->filterResource)) {
            return $this->filterResource;
        }

        return strtolower(class_basename(static::class));
    }

    /**
     * Resolve the filter keys to capture/restore.
     */
    protected function getFilterKeys(): array
    {
        return $this->filterKeys;
    }
}
