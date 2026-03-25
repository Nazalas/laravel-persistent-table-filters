<?php

use Nazalas\PersistentTableFilters\Models\TableFilter;
use Nazalas\PersistentTableFilters\Tests\Support\User;

beforeEach(function () {
    $this->user = User::create([
        'name'     => 'Test User',
        'email'    => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($this->user);
});

it('can create a saved filter', function () {
    $filter = TableFilter::create([
        'user_id'    => $this->user->id,
        'resource'   => 'campaigns',
        'label'      => 'Active campaigns',
        'filters'    => ['status' => 'active', 'search' => ''],
        'is_default' => false,
    ]);

    expect($filter)->toBeInstanceOf(TableFilter::class)
        ->and($filter->resource)->toBe('campaigns')
        ->and($filter->filters['status'])->toBe('active');
});

it('scopes filters to the current user', function () {
    $otherUser = User::create([
        'name'     => 'Other User',
        'email'    => 'other@example.com',
        'password' => bcrypt('password'),
    ]);

    TableFilter::create([
        'user_id'  => $this->user->id,
        'resource' => 'campaigns',
        'label'    => 'Mine',
        'filters'  => ['status' => 'active'],
        'is_default' => false,
    ]);

    TableFilter::create([
        'user_id'  => $otherUser->id,
        'resource' => 'campaigns',
        'label'    => 'Theirs',
        'filters'  => ['status' => 'draft'],
        'is_default' => false,
    ]);

    $filters = TableFilter::forCurrentUser()->forResource('campaigns')->get();

    expect($filters)->toHaveCount(1)
        ->and($filters->first()->label)->toBe('Mine');
});

it('can set a default filter and clears previous defaults', function () {
    $first = TableFilter::create([
        'user_id'    => $this->user->id,
        'resource'   => 'campaigns',
        'label'      => 'First',
        'filters'    => ['status' => 'active'],
        'is_default' => true,
    ]);

    $second = TableFilter::create([
        'user_id'    => $this->user->id,
        'resource'   => 'campaigns',
        'label'      => 'Second',
        'filters'    => ['status' => 'draft'],
        'is_default' => false,
    ]);

    $second->setAsDefault();

    expect($first->fresh()->is_default)->toBeFalse()
        ->and($second->fresh()->is_default)->toBeTrue();
});

it('scopes filters to a resource', function () {
    TableFilter::create([
        'user_id'  => $this->user->id,
        'resource' => 'campaigns',
        'label'    => 'Campaign filter',
        'filters'  => [],
        'is_default' => false,
    ]);

    TableFilter::create([
        'user_id'  => $this->user->id,
        'resource' => 'orders',
        'label'    => 'Order filter',
        'filters'  => [],
        'is_default' => false,
    ]);

    expect(TableFilter::forCurrentUser()->forResource('campaigns')->count())->toBe(1)
        ->and(TableFilter::forCurrentUser()->forResource('orders')->count())->toBe(1);
});

it('casts filters as array', function () {
    $filter = TableFilter::create([
        'user_id'  => $this->user->id,
        'resource' => 'campaigns',
        'label'    => 'Test',
        'filters'  => ['search' => 'foo', 'sort_by' => 'name', 'sort_dir' => 'asc'],
        'is_default' => false,
    ]);

    expect($filter->filters)->toBeArray()
        ->and($filter->filters['search'])->toBe('foo');
});
