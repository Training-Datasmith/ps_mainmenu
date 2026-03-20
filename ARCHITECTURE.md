# Architecture: ps_mainmenu

## Purpose

A PrestaShop front-office widget module that renders the main horizontal navigation menu. Menu items can be categories, CMS pages, product pages, manufacturers, suppliers, or custom URLs.

## Directory Structure

```
ps_mainmenu.php                  - Module class; all hook listeners, menu build logic, admin config
ps_menutoplinks.class.php        - Legacy ObjectModel for custom link entries (menu top links)
views/templates/admin/           - Admin configuration form templates
views/templates/                 - Front-office menu template (Smarty)
upgrade/                         - SQL/PHP migration scripts
tests/                           - PHPUnit test stubs and PHPStan bootstrap
translations/                    - Locale string overrides
```

## Key Design Decisions

- **WidgetInterface**: Implements `WidgetInterface` so the menu block is positionable via the theme editor.
- **JSON cache**: Menu structure is JSON-serialised and stored in a PrestaShop `Configuration` key (`MOD_BLOCKTOPMENU_MENU_JSON`) to avoid rebuilding the tree on every request.
- **Legacy compatibility**: Keeps `ps_menutoplinks.class.php` to maintain compatibility with custom link data created by the PS 1.6 equivalent (`blocktopmenu`).
- **Broad hook coverage**: Listens to category, product, CMS, manufacturer, and supplier mutation hooks to invalidate the JSON cache whenever catalogue structure changes.

## Extension Points

- Add new item types by extending the menu-building logic in `buildMenu()`.
- Override the Smarty template for custom menu HTML structure.

## Dependency Flow

```
ps_mainmenu (Module + WidgetInterface)
  └─> renderWidget()          — renders the navigation menu template
        └─> getWidgetVariables()
              └─> buildMenu() — builds the JSON menu tree
  └─> hookAction*After()      — flushes the JSON menu cache
  └─> Admin config form       — lets merchants add/remove/reorder menu items
```
