# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-03-25

### Added
- `TableFilter` Eloquent model with `forCurrentUser` and `forResource` scopes
- `HasPersistentFilters` trait for Livewire components (and any PHP class)
- **Auto-persist**: `persistFilters()` silently upserts filter state per user+resource on every change
- **Restore**: `restoreFilters()` on mount — auto state takes priority over named default
- **Clear**: `clearPersistedFilters()` wipes the auto record
- **Named presets**: `saveFilter`, `loadFilter`, `deleteFilter`, `getSavedFilters`
- `loadFilter()` also refreshes the auto-persisted state after applying a preset
- Default preset support with automatic clearing of previous defaults
- `$autoSaveFilters = false` opt-out on any component
- `label` column nullable (null = auto record, string = named preset)
- `is_auto` boolean column to distinguish auto records from named presets
- Database migration with configurable table name
- Config file: table name, user model, max named presets per resource
- 16 Pest tests covering both auto-persist and named preset flows
- GitHub Actions CI matrix: PHP 8.2–8.4 × Laravel 10–13
- Laravel 13 support

### Deprecated
- `loadDefaultFilter()` — replaced by `restoreFilters()` (handles both auto state and named defaults)
