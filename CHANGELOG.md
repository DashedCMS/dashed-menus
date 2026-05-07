# Changelog

All notable changes to `dashed-menus` will be documented in this file.

## v4.1.1 - 2026-05-07

### Fixed
- `AdjacencyList` op de Menu edit-pagina gooide `The model Dashed\DashedMenus\Models\Menu must use either the Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships or HasGraphRelationships trait.`. saade/filament-adjacency-list v4 vereist de trait op het owner-model van het form-component, ook al is `Menu` zelf niet zelf-referentieel. Trait toegevoegd aan `Menu`; methodes worden niet aangeroepen — alleen de `class_uses()`-gate van saade hoeft te slagen.
- `staudenmeir/laravel-adjacency-list: ^1.25` als expliciete require van de package toegevoegd (was alleen transitief via saade).

## v4.1.0 - 2026-05-07

### Added
- **Visuele drag-and-drop tree-builder voor menu's** in `MenuResource` via `saade/filament-adjacency-list ^4.0`. Op de edit-pagina staat nu een sectie "Menu structuur" waarin items versleepbaar zijn binnen hun laag of onder een ander item gedropt kunnen worden om sub-items te maken. Onbeperkte nesting (`maxDepth(-1)`). Persisteert `parent_menu_item_id` + `order` op `dashed__menu_items`. Inline rename via een lichte form (alleen `name`); voor de volledige item-instellingen (link, blokken, sites, custom blocks) gaat de admin via "Bewerken" naar de bestaande `MenuItemResource`.
- `saade/filament-adjacency-list: ^4.0` toegevoegd als require van de package.

### Changed
- `Menu::parentMenuItems()` en `MenuItem::childMenuItems()` doen nu recursieve `with('childMenuItems')` eager-load zodat de tree-builder in 1 DB-pass de hele hiërarchie kan renderen i.p.v. N+1 per node.

## 1.0.0 - 202X-XX-XX

- initial release
