# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

### Planned (v2.0.0)
- Configurable inventory size per permission group.
- Optional MySQL backend for multi-server networks sharing the same ender chest.
- One-time migration of a player's existing vanilla 27-slot ender chest contents into the new storage.
- Optional per-world ender chest separation.
- Staff moderation command to inspect another player's ender chest.
- Configurable inventory title, messages and sounds via `config.yml`.

## [1.0.0] - 2026-08-18

### Added
- Full replacement of the vanilla ender chest: interacting with an ender chest block opens a custom 54-slot inventory instead of the vanilla 27-slot one.
- Per-player persistence backed by SQLite, keyed by UUID, with all I/O executed off the main thread via `AsyncTask`.
- In-memory cache loaded on join and released on quit; contents are saved when the inventory closes and batch-saved on plugin disable.
- Vanilla-consistent obstruction check (blocked ender chests refuse to open) and open/close sound effects.
- `doubleenderchest.use` permission gate (default: true).
- Verified clean against PHPStan level 8 and level 9 (max) using real PocketMine-MP 5.44.3 and InvMenu 4.7.4 sources as stubs.
