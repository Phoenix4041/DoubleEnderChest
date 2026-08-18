# DoubleEnderChest

### Replaces the vanilla ender chest with a 54-slot, per-player persisted double ender chest.

---

## Features

* **Double capacity**: opening any ender chest block gives players a 54-slot inventory instead of vanilla's 27 slots.
* **Full vanilla replacement**: no extra command or item is needed, the physical ender chest block is the only entry point.
* **Never lose data**: contents are persisted to SQLite off the main thread, keyed by player UUID so a gamertag change never orphans a chest.
* **Vanilla-consistent behavior**: obstruction check (can't open if blocked above) and matching open/close sound effects.

---

## Requirements

* PocketMine-MP 5.0+
* PHP 8.1+
* [InvMenu](https://poggit.pmmp.io/ci/Muqsit/InvMenu/InvMenu) virion (bundled automatically via Poggit CI)

---

## Installation

1. Download the latest `.phar` build from the [Releases](https://github.com/Phoenix4041/DoubleEnderChest/releases) page or Poggit CI.
2. Drop it into your server's `plugins/` folder.
3. Restart the server.
4. Right-click any ender chest to open the double-sized inventory.

---

## Configuration

No configuration file is required for v1.0.0. All player data is stored automatically.

* `enderchests.sqlite` - per-player ender chest contents, generated automatically in the plugin's data folder.

---

## Permissions

```yaml
doubleenderchest.use      # Allows opening the double ender chest (default: true)
```

---

## Usage

### How It Works

**Opening the chest**:
1. The player right-clicks a placed ender chest block.
2. The vanilla interaction is cancelled before it opens the built-in 27-slot inventory.
3. The plugin's own 54-slot inventory opens instead, pre-filled with the player's saved contents.

**Saving the chest**:
1. When the player closes the inventory, its final contents are written to an in-memory cache immediately.
2. The same contents are persisted to SQLite asynchronously in the background.
3. On plugin disable, every cached ender chest is flushed to disk in a single batched transaction.

---

## Performance Optimization

### Architecture
* All SQLite reads/writes run inside `AsyncTask`, pinned to a single dedicated worker to guarantee schema creation always completes before the first load/save.
* Player data lives in an in-memory cache while online (O(1) access by UUID); the cache entry is released on quit to avoid unbounded growth.
* Batched writes on shutdown use a single `BEGIN`/`COMMIT` transaction instead of one write per player.

### Benchmarks
* **Chest open**: O(1) cache lookup, zero disk I/O on the main thread.
* **Chest close**: one async write per player, never blocking.
* **TPS Impact**: negligible — no repeating tasks, no polling, purely event-driven.
* **Memory Overhead**: one NBT blob per online player, released on disconnect.

---

## Technical Details

### Architecture

```
src/Phoenix4041/DoubleEnderChest/
├── Loader.php
├── Permissions.php
├── listener/
│   └── EnderChestListener.php
├── menu/
│   └── EnderChestMenu.php
└── storage/
    ├── EnderChestRepository.php
    └── task/
        ├── CreateSchemaTask.php
        ├── LoadInventoryTask.php
        ├── SaveInventoryTask.php
        └── SaveAllInventoriesTask.php
```

### Design Principles
* **Single Responsibility**: the listener only reacts to events, the menu only builds the GUI, the repository only owns persistence.
* **Dependency Injection**: the repository is constructed once in `Loader` and injected into the listener; no service-locator chains.
* **Type Safety**: `declare(strict_types=1)` everywhere, typed properties, typed array docblocks.

---

## Static Analysis

Verified against real PocketMine-MP 5.44.3 and InvMenu 4.7.4 sources, not stubs guessed from memory.

```bash
composer install
vendor/bin/phpstan analyse
```

* `phpstan.neon` runs **level 9** (the highest level PHPStan 1.x offers) with `vendor/pocketmine/pocketmine-mp/src` and `vendor/muqsit/invmenu/src` scanned as dependency sources.
* Result: **0 errors at level 8 and level 9**, zero `@phpstan-ignore` suppressions.
* One real class of bug was caught and fixed by this: `SQLite3::prepare()` returns `SQLite3Stmt|false`, and the initial implementation used the result without checking for `false`. All three async tasks now validate it and raise a `RuntimeException` (caught by the surrounding `try/catch(\Throwable)`) instead of silently calling a method on `false`.
* `composer.json` is dev-tooling only (PHPStan + stubs) — it is not required to run the plugin; Poggit builds the `.phar` straight from `src/` + `plugin.yml` + `.poggit.yml`.

---

## Troubleshooting

### The chest says "still loading"
The player interacted before their async load finished (extremely rare, only possible right at join). Wait a moment and try again.

### The chest says "blocked"
Matches vanilla behavior: there must be no solid block directly above the ender chest.

### Known Limitations (v1.0.0)
* **No automatic migration**: if the plugin is installed on a server where players already had items in their vanilla 27-slot ender chest, those items stay in the player's vanilla `EnderInventory` NBT — they are not copied into the new 54-slot storage and are inaccessible from the in-game UI while this plugin is enabled. A one-time migration step is planned for v2.0 (see below).
* **Single server only**: the SQLite backend is local to one server process. On a proxied network (Waterdog PE, etc.) with multiple PM5 backends, each server keeps its own independent ender chest contents until the planned MySQL backend ships.

---

## Contributing

Contributions must meet these standards:

* **Code Quality**: no dead code, no unnecessary abstractions, PSR-4 autoloading respected.
* **Performance**: zero blocking I/O on the main thread, no per-entity/per-player repeating tasks.
* **Architecture**: match the existing minimal, single-responsibility structure.
* **Type Safety**: `strict_types=1`, explicit return types, PHPStan level 8.
* **Documentation**: update this README and `CHANGELOG.md` for any user-facing change.

---

## License

This project is licensed under the MIT License - see [LICENSE](LICENSE) file for details.

---

## Support

For issues, feature requests, or questions:

* Create an [Issue](https://github.com/Phoenix4041/DoubleEnderChest/issues)

---

## Updates & Improvements

### v1.0.0 - Initial Release (2026-08-18)

**Core Features:**
* Full vanilla ender chest replacement with a 54-slot inventory.
* Async SQLite persistence keyed by player UUID.

**Technical Highlights:**
* Zero blocking I/O, zero repeating tasks, cache-first architecture.

### Planned Features (v2.0.0+)
- [ ] Configurable inventory size per permission group (`config.yml`, e.g. VIP gets 6 rows, default gets 3).
- [ ] Optional MySQL backend for multi-server networks sharing the same ender chest across a proxy.
- [ ] One-time migration of a player's existing vanilla 27-slot `EnderInventory` contents into the new storage on their first interaction after upgrading.
- [ ] Optional per-world ender chest separation (toggle between one global chest and one chest per world).
- [ ] Staff moderation command (`/enderchest view <player>`, dedicated permission) to inspect another player's ender chest, read-only by default.
- [ ] Configurable inventory title, open/close messages and sounds via `config.yml`.

---

## Version Support

| Version | Release Date | Status | Support |
|---------|-------------|--------|---------|
| 1.0.0 | 2026-08-18 | 🟢 Active | Full support |

---

**Made with ❤️ by Phoenix4041**
