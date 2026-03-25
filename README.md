# Laravel Persistent Table Filters

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nazalas/laravel-persistent-table-filters.svg?style=flat-square)](https://packagist.org/packages/nazalas/laravel-persistent-table-filters)
[![Tests](https://img.shields.io/github/actions/workflow/status/Nazalas/laravel-persistent-table-filters/tests.yml?label=tests&style=flat-square)](https://github.com/Nazalas/laravel-persistent-table-filters/actions)
[![License](https://img.shields.io/packagist/l/nazalas/laravel-persistent-table-filters.svg?style=flat-square)](https://packagist.org/packages/nazalas/laravel-persistent-table-filters)

Save and restore table filter, sort, and search state per user in Laravel applications. Works great with Livewire.

## The Problem

Users refine a table — searching, sorting, filtering — then navigate away. When they come back, everything is reset. This package lets users save named filter presets and restore them with one click, or automatically restore their last state on page load.

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

Add the `HasPersistentFilters` trait to your Livewire component, define the resource key and which properties represent your filter state:

```php
use Nazalas\PersistentTableFilters\Traits\HasPersistentFilters;

class CampaignIndex extends Component
{
    use HasPersistentFilters;

    // Unique key for this table — used to scope saved filters
    protected string $filterResource = 'campaigns';

    // Which component properties make up the filter state
    protected array $filterKeys = ['search', 'sort_by', 'sort_dir', 'status'];

    public string $search = '';
    public string $sort_by = 'name';
    public string $sort_dir = 'asc';
    public string $status = '';

    public function mount(): void
    {
        // Optionally restore the user's default filter on load
        $this->loadDefaultFilter();
    }
}
```

### Saving a Filter

```php
// Save current state with a label
$filter = $this->saveFilter('Active campaigns');

// Save and mark as default (auto-loads on mount)
$filter = $this->saveFilter('My default view', setAsDefault: true);
```

### Loading a Filter

```php
$this->loadFilter($filterId);
```

### Deleting a Filter

```php
$this->deleteFilter($filterId);
```

### Listing Saved Filters

```php
$filters = $this->getSavedFilters(); // Collection of TableFilter models
```

### Example Blade UI

```blade
<div x-data="{ saving: false, label: '' }">
    <!-- Saved filter dropdown -->
    @foreach($this->getSavedFilters() as $filter)
        <button wire:click="loadFilter({{ $filter->id }})">
            {{ $filter->label }}
            @if($filter->is_default) ★ @endif
        </button>
    @endforeach

    <!-- Save current state -->
    <div x-show="saving">
        <input x-model="label" type="text" placeholder="Filter name" />
        <button @click="$wire.saveFilter(label); saving = false">Save</button>
    </div>
    <button @click="saving = true">Save current filters</button>
</div>
```

### Using Without Livewire

The `TableFilter` model and its scopes work independently of Livewire. You can use it directly in controllers:

```php
use Nazalas\PersistentTableFilters\Models\TableFilter;

// Save
TableFilter::create([
    'user_id'    => auth()->id(),
    'resource'   => 'campaigns',
    'label'      => 'My filter',
    'filters'    => $request->only(['search', 'status', 'sort_by']),
    'is_default' => false,
]);

// Load
$filter = TableFilter::forCurrentUser()->forResource('campaigns')->findOrFail($id);
$state = $filter->filters; // array
```

## Configuration

```php
// config/persistent-table-filters.php

return [
    'table_name'               => 'table_filters',
    'user_model'               => \App\Models\User::class,
    'max_per_user_per_resource' => 20,  // null for no limit
];
```

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT. See [LICENSE](LICENSE).
