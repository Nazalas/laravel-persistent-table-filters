<?php

use Nazalas\PersistentTableFilters\Models\TableFilter;
use Nazalas\PersistentTableFilters\Tests\Support\User;
use Nazalas\PersistentTableFilters\Traits\HasPersistentFilters;

// A minimal fake "component" for testing the trait in isolation
class FakeComponent
{
    use HasPersistentFilters;

    protected string $filterResource = 'campaigns';
    protected array $filterKeys = ['search', 'sort_by', 'sort_dir', 'status'];

    public string $search = '';
    public string $sort_by = 'name';
    public string $sort_dir = 'asc';
    public string $status = '';
}

beforeEach(function () {
    $this->user = User::create([
        'name'     => 'Test User',
        'email'    => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($this->user);

    $this->component = new FakeComponent();
});

// -------------------------------------------------------------------------
// Named presets
// -------------------------------------------------------------------------

it('captures current filter state', function () {
    $this->component->search = 'foo';
    $this->component->sort_by = 'created_at';
    $this->component->status = 'active';

    $filter = $this->component->saveFilter('My Filter');

    expect($filter->filters)->toBe([
        'search'   => 'foo',
        'sort_by'  => 'created_at',
        'sort_dir' => 'asc',
        'status'   => 'active',
    ]);
});

it('loads a saved named filter and applies state to the component', function () {
    $saved = TableFilter::create([
        'user_id'    => $this->user->id,
        'resource'   => 'campaigns',
        'label'      => 'Archived',
        'filters'    => ['search' => 'archived', 'sort_by' => 'name', 'sort_dir' => 'desc', 'status' => 'archived'],
        'is_default' => false,
        'is_auto'    => false,
    ]);

    $this->component->loadFilter($saved->id);

    expect($this->component->search)->toBe('archived')
        ->and($this->component->sort_dir)->toBe('desc')
        ->and($this->component->status)->toBe('archived');
});

it('deletes a saved named filter', function () {
    $filter = $this->component->saveFilter('To Delete');

    expect(TableFilter::where('is_auto', false)->count())->toBe(1);

    $this->component->deleteFilter($filter->id);

    expect(TableFilter::where('is_auto', false)->count())->toBe(0);
});

it('getSavedFilters excludes auto-persisted records', function () {
    $this->component->saveFilter('Filter A');
    $this->component->saveFilter('Filter B');
    $this->component->persistFilters(); // creates an auto record

    $filters = $this->component->getSavedFilters();

    expect($filters)->toHaveCount(2); // auto record excluded
});

// -------------------------------------------------------------------------
// Auto-persist
// -------------------------------------------------------------------------

it('persistFilters creates an auto record on first call', function () {
    $this->component->search = 'hello';
    $this->component->persistFilters();

    $auto = TableFilter::where('is_auto', true)->first();

    expect($auto)->not->toBeNull()
        ->and($auto->filters['search'])->toBe('hello')
        ->and($auto->label)->toBeNull();
});

it('persistFilters updates existing auto record instead of creating a new one', function () {
    $this->component->search = 'first';
    $this->component->persistFilters();

    $this->component->search = 'second';
    $this->component->persistFilters();

    expect(TableFilter::where('is_auto', true)->count())->toBe(1)
        ->and(TableFilter::where('is_auto', true)->first()->filters['search'])->toBe('second');
});

it('restoreFilters loads auto-persisted state on mount', function () {
    TableFilter::create([
        'user_id'    => $this->user->id,
        'resource'   => 'campaigns',
        'label'      => null,
        'filters'    => ['search' => 'auto-saved', 'sort_by' => 'name', 'sort_dir' => 'asc', 'status' => 'active'],
        'is_default' => false,
        'is_auto'    => true,
    ]);

    $this->component->restoreFilters();

    expect($this->component->search)->toBe('auto-saved')
        ->and($this->component->status)->toBe('active');
});

it('restoreFilters falls back to named default if no auto state exists', function () {
    TableFilter::create([
        'user_id'    => $this->user->id,
        'resource'   => 'campaigns',
        'label'      => 'Default',
        'filters'    => ['search' => 'default-search', 'sort_by' => 'name', 'sort_dir' => 'asc', 'status' => 'active'],
        'is_default' => true,
        'is_auto'    => false,
    ]);

    $this->component->restoreFilters();

    expect($this->component->search)->toBe('default-search')
        ->and($this->component->status)->toBe('active');
});

it('auto state takes priority over named default on restore', function () {
    TableFilter::create([
        'user_id'    => $this->user->id,
        'resource'   => 'campaigns',
        'label'      => 'Default',
        'filters'    => ['search' => 'named-default', 'sort_by' => 'name', 'sort_dir' => 'asc', 'status' => ''],
        'is_default' => true,
        'is_auto'    => false,
    ]);

    TableFilter::create([
        'user_id'    => $this->user->id,
        'resource'   => 'campaigns',
        'label'      => null,
        'filters'    => ['search' => 'auto-state', 'sort_by' => 'name', 'sort_dir' => 'asc', 'status' => ''],
        'is_default' => false,
        'is_auto'    => true,
    ]);

    $this->component->restoreFilters();

    expect($this->component->search)->toBe('auto-state');
});

it('clearPersistedFilters removes the auto record', function () {
    $this->component->persistFilters();

    expect(TableFilter::where('is_auto', true)->count())->toBe(1);

    $this->component->clearPersistedFilters();

    expect(TableFilter::where('is_auto', true)->count())->toBe(0);
});

it('loadFilter also updates the auto-persisted state', function () {
    $saved = $this->component->saveFilter('My Preset');

    $this->component->search = 'something else';

    $this->component->loadFilter($saved->id);

    $auto = TableFilter::where('is_auto', true)->first();
    expect($auto)->not->toBeNull()
        ->and($auto->filters['search'])->toBe(''); // original preset had empty search
});
