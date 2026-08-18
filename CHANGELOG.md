# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

### Planned (v2.0.0)
- Configurable inventory size per permission group.
- Optional MySQL backend for multi-server networks sharing the same ender chest.

## [1.0.0] - 2026-08-18

### Added
- Full replacement of the vanilla ender chest: interacting with an ender chest block opens a custom 54-slot inventory instead of the vanilla 27-slot one.
- Per-player persistence backed by SQLite, keyed by UUID, with all I/O executed off the main thread via `AsyncTask`.
- In-memory cache loaded on join and released on quit; contents are saved when the inventory closes and batch-saved on plugin disable.
- Vanilla-consistent obstruction check (blocked ender chests refuse to open) and open/close sound effects.
- `doubleenderchest.use` permission gate (default: true).
