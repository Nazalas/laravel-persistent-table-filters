# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-03-25

### Added
- `TableFilter` Eloquent model with `forCurrentUser` and `forResource` scopes
- `HasPersistentFilters` trait for Livewire components (and any PHP class)
- Methods: `saveFilter`, `loadFilter`, `deleteFilter`, `getSavedFilters`, `loadDefaultFilter`
- Default filter support with automatic clearing of previous defaults
- Database migration with configurable table name
- Config file: table name, user model, max filters per resource
- Full Pest test suite
