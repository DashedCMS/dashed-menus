# Changelog

All notable changes to `dashed-menus` will be documented in this file.

## v4.2.5 - 2026-05-07

### Fixed
- Tree-builder bewaarde geen consistente boom-structuur: saade's default save-loop saved top-level items via `Menu::menuItemsForTree()->save($record)` (HasMany op `menu_id`) en raakte `parent_menu_item_id` daarbij niet aan. Nested → top-level verplaatste items hielden hun oude `parent_menu_item_id`. Eigen `saveRelationshipsUsing()`-closure traversteert nu de state per laag en schrijft per record expliciet zowel `parent_menu_item_id` (null voor top-level, parent->id voor nested) als `order` (1-based per laag). Menu cache wordt na opslag geleegd via `Menus::clearCache()`.

## v4.2.4 - 2026-05-07

### Fixed
- `Attempt to assign property "order" on null` bij het opslaan van een geneste tree. saade's cache bevatte alleen top-level items (via `Menu::parentMenuItems` met `whereNull(parent_menu_item_id)`), waardoor nested item-keys in de save-state niet matchen met de cache en `$record` `null` is.
- Nieuwe `Menu::menuItemsForTree()` relatie levert een flat HasMany over álle items in het menu (geen `whereNull`-filter, geen `with('childMenuItems')`). saade vult zijn cache met alle items (top-level én nested), Staudenmeir's `toTree()` reconstrueert de hierarchy via `parent_menu_item_id`, en `childrenKey` is veranderd van `childMenuItems` → `children` zodat de save-loop dezelfde in-memory tree-structuur volgt als de state-build.
- `Menu::parentMenuItems()` blijft als top-level-filtered relatie bestaan voor de frontend menu-render; alleen de tree-builder gebruikt nu `menuItemsForTree`.

## v4.2.3 - 2026-05-07

### Changed
- Tree-builder weer als inklapbare `Section` "Sorteren" (icoon `bars-arrow-down`) op de Menu edit-pagina, met `collapsible()->collapsed()->persistCollapsed()`. De button-modal-aanpak uit v4.2.0–v4.2.2 vulde de tree niet betrouwbaar met de bestaande items in deze setup; de inline Section werkt direct omdat de form-binding daar onveranderd is. Schema-definitie blijft centraal in `MenuResource::adjacencyListField()` voor hergebruik.

## v4.2.2 - 2026-05-07

### Changed
- Knop "Menu structuur ordenen" verplaatst van de page-header van `EditMenu` naar de header van de `MenuItemsRelationManager` (naast "Menu item aanmaken" / locale-switcher / "Vertaal menu" / "Kopiëren"). De knop staat nu in de visuele context waar de items zelf zichtbaar zijn, wat 'm beter vindbaar maakt. `mountUsing()` valt expliciet terug op `$this->ownerRecord` zodat de schema-binding werkt vanuit de relation-manager.

## v4.2.1 - 2026-05-07

### Fixed
- Tree-builder modal opende leeg: bestaande menu-items werden niet geladen. saade's `HasRelationship::getCachedExistingRecords()` valt zonder expliciete schema-binding terug op `app(Model::class)` (een vers Menu zonder id), waardoor het relationship-query niets vindt. `mountUsing()` zet nu expliciet `$schema->model(Menu::class)` en `$schema->record($record)` voordat `$schema->fill()` de relationship-state hydrateert.

## v4.2.0 - 2026-05-07

### Changed
- De drag-and-drop tree-builder is nu een modal achter een knop "Menu structuur ordenen" in de header van de Menu edit-pagina (`bars-arrow-down`-icoon, modalWidth `4xl`). De inline `Section` is daardoor verdwenen — de form-pagina zelf is weer compact en de tree neemt alleen ruimte in als de admin de modal opent. Wijzigingen in de tree worden incrementeel door saade gepersisteerd; de modal kent geen "opslaan"-knop, alleen "Sluiten".
- Het tree-schema is verplaatst naar `MenuResource::adjacencyListField()` zodat zowel de header-action als toekomstige plekken (bv. een dashboard-widget) hetzelfde tree-component kunnen hergebruiken.

## v4.1.3 - 2026-05-07

### Changed
- Sectie "Menu structuur ordenen" op de Menu edit-pagina is nu standaard ingeklapt (`collapsible()->collapsed()->persistCollapsed()`) met een ordenen-icoon. De drag-and-drop tree neemt zo geen verticale ruimte in tot de admin er expliciet op klikt om uit te klappen; de uitklap-state wordt per gebruiker onthouden.

## v4.1.2 - 2026-05-07

### Fixed
- `Method Illuminate\Database\Eloquent\Collection::toTree does not exist` op de Menu edit-pagina. saade/filament-adjacency-list roept `->toTree()` aan op het collection-resultaat van de relatie; die methode komt van Staudenmeir's `Collection`. Trait `HasRecursiveRelationships` toegevoegd aan `MenuItem` (de daadwerkelijk zelf-referentiële kant) zodat MenuItem's collections de Staudenmeir-versie gebruiken. `getParentKeyName()` override naar `parent_menu_item_id` zodat de trait's eigen relations onze bestaande FK-kolom gebruiken.

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
