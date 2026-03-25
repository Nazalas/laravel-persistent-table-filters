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

it('loads a saved filter and applies state to the component', function () {
    $saved = TableFilter::create([
        'user_id'    => $this->user->id,
        'resource'   => 'campaigns',
        'label'      => 'Archived',
        'filters'    => ['search' => 'archived', 'sort_by' => 'name', 'sort_dir' => 'desc', 'status' => 'archived'],
        'is_default' => false,
    ]);

    $this->component->loadFilter($saved->id);

    expect($this->component->search)->toBe('archived')
        ->and($this->component->sort_dir)->toBe('desc')
        ->and($this->component->status)->toBe('archived');
});

it('deletes a saved filter', function () {
    $filter = $this->component->saveFilter('To Delete');

    expect(TableFilter::count())->toBe(1);

    $this->component->deleteFilter($filter->id);

    expect(TableFilter::count())->toBe(0);
});

it('returns saved filters for the current user and resource', function () {
    $this->component->saveFilter('Filter A');
    $this->component->saveFilter('Filter B');

    $filters = $this->component->getSavedFilters();

    expect($filters)->toHaveCount(2);
});

it('loads the default filter on mount', function () {
    TableFilter::create([
        'user_id'    => $this->user->id,
        'resource'   => 'campaigns',
        'label'      => 'Default',
        'filters'    => ['search' => 'default-search', 'sort_by' => 'name', 'sort_dir' => 'asc', 'status' => 'active'],
        'is_default' => true,
    ]);

    $this->component->loadDefaultFilter();

    expect($this->component->search)->toBe('default-search')
        ->and($this->component->status)->toBe('active');
});
