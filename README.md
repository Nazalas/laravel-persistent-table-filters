# Laravel Persistent Table Filters

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nazalas/laravel-persistent-table-filters.svg?style=flat-square)](https://packagist.org/packages/nazalas/laravel-persistent-table-filters)
[![Tests](https://github.com/Nazalas/laravel-persistent-table-filters/actions/workflows/tests.yml/badge.svg)](https://github.com/Nazalas/laravel-persistent-table-filters/actions/workflows/tests.yml)
[![License](https://img.shields.io/packagist/l/nazalas/laravel-persistent-table-filters.svg?style=flat-square)](https://packagist.org/packages/nazalas/laravel-persistent-table-filters)

Automatically persist and restore table filter, sort, and search state per user in Laravel applications. Works great with Livewire.

## The Problem

Users refine a table — searching, sorting, filtering — then navigate away or refresh. Everything resets. This package silently saves filter state as users interact and restores it automatically on the next visit. Optionally, users can also save named filter presets and switch between them.

## Features

- **Auto-persist** — silently saves filter state on every change; restores it on mount with no user interaction
- **Named presets** — users can save, label, and reload specific filter configurations
- **Default preset** — mark one named preset as the default fallback
- **Per-user, per-resource** — state is scoped to the authenticated user and a resource key you define
- **Opt-out** — set `$autoSaveFilters = false` on any component to disable auto-persist

## Installation

```bash
composer require nazalas/laravel-persistent-table-filters
```

Publish and run the migration:

```bash
php artisan vendor:publish --tag="persistent-table-filters-migrations"
php artisan migrate
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag="persistent-table-filters-config"
```

## Usage

### Livewire Component

Add the `HasPersistentFilters` trait, define a resource key and which properties make up your filter state:

```php
use Nazalas\PersistentTableFilters\Traits\HasPersistentFilters;

class CampaignIndex extends Component
{
    use HasPersistentFilters;

    // Unique key for this table — scopes saved state per resource
    protected string $filterResource = 'campaigns';

    // Which component properties make up the filter state
    protected array $filterKeys = ['search', 'sort_by', 'sort_dir', 'status'];

    public string $search = '';
    public string $sort_by = 'name';
    public string $sort_dir = 'asc';
    public string $status = '';

    public function mount(): void
    {
        // Restore last state (auto-persisted or named default)
        $this->restoreFilters();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->persistFilters(); // silently save on every change
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
        $this->persistFilters();
    }
}
```

That's the core usage. Filters now survive page refreshes automatically with no user action needed.

### Disabling Auto-Persist

Set `$autoSaveFilters = false` on any component to prevent `persistFilters()` from writing to the database:

```php
protected bool $autoSaveFilters = false;
```

### Clearing Persisted State

```php
$this->clearPersistedFilters();
```

Wipes the auto-persisted record for the current user + resource. The next page load will start fresh (or fall back to a named default if one exists).

---

## Named Presets (Optional)

On top of auto-persist, users can save and reload named filter configurations.

### Saving a Preset

```php
// Save current state with a label
$filter = $this->saveFilter('Active campaigns');

// Save and mark as the default fallback
$filter = $this->saveFilter('My default view', setAsDefault: true);
```

### Loading a Preset

```php
$this->loadFilter($filterId);
// Also updates the auto-persisted state
```

### Deleting a Preset

```php
$this->deleteFilter($filterId);
```

### Listing Presets

```php
$filters = $this->getSavedFilters(); // Collection of TableFilter — excludes auto records
```

### Example Blade UI for Presets

```blade
<div x-data="{ saving: false, label: '' }">
    @if($this->getSavedFilters()->count())
        <select wire:change="loadFilter($event.target.value)">
            <option value="">Load saved filter...</option>
            @foreach($this->getSavedFilters() as $filter)
                <option value="{{ $filter->id }}">
                    {{ $filter->label }}{{ $filter->is_default ? ' ★' : '' }}
                </option>
            @endforeach
        </select>
    @endif

    <div x-show="saving">
        <input x-model="label" type="text" placeholder="Filter name" />
        <button @click="$wire.saveFilter(label); saving = false">Save</button>
    </div>
    <button @click="saving = true">Save current filters</button>
</div>
```

---

## How Restore Priority Works

`restoreFilters()` follows this order:

1. **Auto-persisted state** — the last state saved by `persistFilters()` (if any)
2. **Named default** — a named preset marked `is_default = true` (if no auto state)
3. **Component defaults** — the property values you declared in the class (if neither exists)

---

## Using Without Livewire

The `TableFilter` model and scopes work independently of Livewire:

```php
use Nazalas\PersistentTableFilters\Models\TableFilter;

// Save state manually
TableFilter::updateOrCreate(
    ['user_id' => auth()->id(), 'resource' => 'campaigns', 'is_auto' => true],
    ['label' => null, 'filters' => $request->only(['search', 'status', 'sort_by'])]
);

// Restore state
$state = TableFilter::forCurrentUser()
    ->forResource('campaigns')
    ->where('is_auto', true)
    ->first()
    ?->filters ?? [];
```

---

## Configuration

```php
// config/persistent-table-filters.php

return [
    'table_name'                => 'table_filters',
    'user_model'                => \App\Models\User::class,
    'max_per_user_per_resource' => 20,  // named presets only; null = no limit
];
```

---

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT. See [LICENSE](LICENSE).
