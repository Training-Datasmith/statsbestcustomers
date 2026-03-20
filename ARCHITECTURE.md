# Architecture: statsbestcustomers

## Purpose

A PrestaShop statistics module that ranks customers by order volume and total spend on the admin dashboard, helping merchants identify their most valuable buyers.

## Directory Structure

```
statsbestcustomers.php   - Module class (ModuleGrid subclass); all business logic
upgrade/                 - Migration scripts for version upgrades
tests/                   - PHPUnit test stubs and PHPStan bootstrap
translations/            - Locale string overrides
```

## Key Design Decisions

- **ModuleGrid inheritance**: Delegates column definitions, sorting, pagination, and CSV export to PrestaShop's built-in grid engine.
- **Single-file module**: Follows PrestaShop module conventions — all logic in one class file.
- **Date-range filtering**: Uses the parent `getDate()` helper so the module respects the admin stats date picker.

## Extension Points

- Override `getData()` to change ranking criteria or add customer attributes.
- Add extra columns to the `$columns` array in the constructor.

## Dependency Flow

```
statsbestcustomers (ModuleGrid)
  └─> hookDisplayAdminStatsModules() — renders the customer ranking widget
  └─> getData()                      — executes ranking SQL
        └─> Db::getInstance()        — PrestaShop database abstraction
```
