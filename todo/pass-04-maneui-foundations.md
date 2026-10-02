# P04 — ManeUI : fondations

> Spécification d'implémentation. Toute décision ci-dessous est figée pour cette passe.
> Référence : `todo/todo.md` §6, §7, §37–§43, §280–§283, §338, §339, §407, §464–§469, §485.
> Feuille de route : `todo/roadmap.md`. Prérequis : P01 accepté (P03 livré).

## 0. Résultat attendu

Le code applicatif ne parle plus à TallStackUI ni au HTML brut des contrôles : il consomme
ManeUI, un jeu de composants Blade à API sémantique (`<x-mane::button variant="danger">`)
qui encapsule TallStackUI, les tokens et les états. Les tables riches passent par un
DataTable Livewire qui ne charge jamais une collection entière et refuse tout tri non déclaré.
Le rendu tient en clair, en sombre, en RTL et dans les trois densités, et l'accessibilité est
vérifiée dans un vrai navigateur.

## 1. Périmètre

### Inclus

- Tokens primitive → sémantique → composant, mode sombre, densité, mouvement, z-index
- Direction du texte (`dir`) dérivée de la locale ; propriétés logiques imposées
- 27 composants de la feuille de route + composants de support déjà utilisés par l'application
  (Icon, Link, Password, Pin, Spinner, ThemeSwitch, Layout, Sidebar)
- Contrat de formulaire Livewire (§281)
- DataTable Livewire (§282) et vues enregistrées ; migration de l'écran d'audit
- Catalogue interne (`local` et `testing` uniquement)
- Migration de toutes les vues existantes vers ManeUI
- `docs/design-system/`
- Tests navigateur (Pest Browser + Playwright Chromium), en local et en CI

### Reporté

| Sujet | Passe |
|:--|:--|
| Baselines visuelles (captures de référence) : rendu des polices différent entre Windows et la CI Linux, il faut un environnement de rendu figé | P39 |
| Export de table (§282) : dépend des jobs et du stockage de fichiers | P14 |
| Tri multiple, redimensionnement, réordonnancement et épinglage de colonnes, édition en ligne, regroupement, agrégation (§42) | P66 |
| Composants spécialisés (dates, montants, arbre, upload, Kanban, calendrier, palette) | P07, P08, P13, P14, P21, P52, P66 |
| Locale `ar` réelle (le RTL est vérifié via le catalogue et `dir="rtl"`) | P66 |

## 2. Décisions figées

| Sujet | Décision |
|:--|:--|
| Espace de noms | Composants anonymes dans `resources/views/mane/`, enregistrés sous le préfixe `mane` : `<x-mane::button>` |
| TallStackUI | Préfixe `ts-` (`config/tallstackui.php`). `<x-ts-*>` n'est autorisé que dans `resources/views/mane/` (test d'architecture) |
| Contrôles bruts | `<button>`, `<input>` (hors `type="hidden"`), `<select>`, `<textarea>` interdits hors `resources/views/mane/` |
| API | Chaque composant déclare ses `@props` : variantes sémantiques (`primary`, `secondary`, `ghost`, `danger`), tailles `sm`/`md`/`lg`. Les props TallStackUI ne traversent pas : Blade ne transmet pas les props de classe via `$attributes` |
| Palette | Trois rampes inchangées (`primary`, `secondary`, `dark`). Un statut n'est jamais porté par la couleur seule : icône + texte (WCAG 1.4.1) |
| Tokens sémantiques | Variables CSS `--mane-*` avec surcharge `.dark`, exposées à Tailwind par `@theme inline` : `surface`, `surface-raised`, `surface-sunken`, `fg`, `fg-muted`, `line`, `line-strong`, `focus`, `danger`, `success`, `warning`, `info` |
| Densité | Attribut `data-density` (`comfortable` par défaut, `compact`, `dense`) ; tokens `--mane-control-h`, `--mane-cell-py`, `--mane-cell-px`, `--mane-gap` |
| Mouvement | Tokens `--mane-duration-*` ; `prefers-reduced-motion` neutralise animations et transitions |
| RTL | `<html dir>` calculé par `App\View\TextDirection` depuis la locale. Utilitaires physiques (`ml-`, `mr-`, `pl-`, `pr-`, `left-`, `right-`, `text-left`, `text-right`, `border-l`, `border-r`, `rounded-l`, `rounded-r`) interdits dans les vues applicatives et ManeUI |
| Bouton désactivé (§407) | `aria-disabled="true"`, reste focalisable, `type="button"`, sans `wire:click` ni `href` : annoncé, visible, inerte |
| Chargement d'action | `loading="méthode"` sur le bouton : désactivation réelle et indicateur pendant la requête (§465) |
| Formulaire (§281) | `<x-mane::form>` + trait `App\Livewire\Concerns\HasFormContract` (classe de base `App\Livewire\FormComponent` pour les composants en classe) : validation, `authorize`, état sale (`wire:dirty`), envoi et chargement, résumé d'erreurs `aria-live`, succès par toast, réinitialisation |
| DataTable (§282) | Trait `App\Livewire\DataTable\WithDataTable` (composé de `WithTableSelection` et `WithSavedViews`), classe de base `DataTable` pour les composants en classe ; les composants mono-fichier gardent `extends Component`, seul héritage que Sheath reconnaît ; colonnes, filtres et actions groupées déclarés par objets valeur ; tri, filtre, taille de page sur liste blanche ; `paginate()` uniquement ; rendu `<table>` sémantique (`aria-sort`, `scope`, `caption`) |
| Vues enregistrées | Table `saved_table_views` (contexte `Platform`, `tenant_id` + RLS forcée), privées à leur auteur, état JSON validé à la relecture |
| Actions groupées | Ids sélectionnés relus via la requête de la table (donc RLS et filtres), plafond de 100 ; autorisation dans l'action |
| Catalogue | Route `/_mane` enregistrée seulement en `local` et `testing` |
| Tests navigateur | `pestphp/pest-plugin-browser` (déjà installé) + `playwright` en devDependency ; Chromium installé en CI ; étape `browser` séparée dans `bin/check.php` (le serveur HTTP en processus se bloque après la suite complète) |
| Superpositions | Menu déroulant téléporté dans `<body>` et positionné par Floating UI (`x-anchor` : bascule, décalage), au-dessus des modales (`--mane-z-dropdown` 55 > `--mane-z-overlay` 50) ; popover ancré mais non téléporté (il contient des `wire:model`) |
| Pages | Toutes les vues consomment les tokens sémantiques (aucune rampe brute hors ManeUI, test d'architecture) et l'unique `h1` de chaque page passe par `x-mane::page-header` |

## 3. Tokens

```text
Primitive : --color-primary-*, --color-secondary-*, --color-dark-*, police, rayons de base
Sémantique : --mane-surface, --mane-fg, --mane-line, --mane-focus, --mane-danger… (clair/sombre)
Composant : --mane-control-h, --mane-control-radius, --mane-cell-py, --mane-cell-px, --mane-gap
```

Fichiers : `resources/css/mane/tokens.css` (primitives et sémantiques), `resources/css/mane/base.css`
(densité, mouvement, focus visible), importés par `resources/css/app.css`.

## 4. Composants

| ManeUI | Base | API principale |
|:--|:--|:--|
| button | `ts-button` | `variant`, `size`, `type`, `href`, `icon`, `icon-position`, `loading`, `disabled`, `block`, `text` |
| icon-button | `ts-button.circle` | `icon`, `label` (obligatoire), `variant`, `size`, `href`, `loading`, `disabled` |
| input | `ts-input` | `label`, `hint`, `icon`, `prefix`, `suffix`, `clearable` |
| password, pin | `ts-password`, `ts-pin` | `label`, `hint` ; `length` |
| textarea | `ts-textarea` | `label`, `hint`, `count`, `resize` |
| select | ManeUI (le `select.native` de TallStackUI rend un label orphelin) | `label`, `hint`, `options`, `placeholder` |
| checkbox, radio, switch | `ts-checkbox`, `ts-radio`, `ts-toggle` | `label` |
| badge | `ts-badge` | `variant`, `text` |
| status | ManeUI | `tone` (`success`, `warning`, `danger`, `info`, `muted`), `text` ; icône par ton |
| alert | ManeUI (rampes hors palette côté TallStackUI) | `tone`, `title`, `text`, `dismissible` |
| toast | `ts-toast` | conteneur unique par layout |
| tooltip | ManeUI (Alpine, WCAG 1.4.13) | `text`, `position` |
| popover | ManeUI (Alpine) | `label`, déclencheur `aria-expanded`, Échap, clic extérieur, retour du focus |
| dropdown, dropdown.item, dropdown.group, dropdown.trigger | ManeUI (le dropdown TallStackUI imbrique des contrôles interactifs) | `label`, `icon`, `align`, `placement` ; `href`, `separator` ; navigation clavier APG |
| modal | `ts-modal` | `id`, `title`, `size`, `wire`, `persistent`, slot `footer` |
| drawer | `ts-slide` | `id`, `title`, `size`, `side` (`start`, `end`, suit la direction) |
| tabs, tabs.item | `ts-tab`, `ts-tab.items` | `selected` ; `tab`, `title` |
| accordion, accordion.item | `ts-accordion`, `ts-accordion.items` | `title`, `open` |
| card | `ts-card` | slots `header`, `footer` |
| breadcrumb | ManeUI | `items` (`label`, `href`) ; dernier élément `aria-current="page"` |
| avatar | `ts-avatar` | `model`, `text`, `image`, `size` |
| pagination | ManeUI | `paginator` ; liens Livewire, `aria-current`, libellés traduits |
| empty-state | ManeUI | `kind` (`empty`, `first-use`, `no-access`, `no-results`, `error`), `title`, `description`, slot `action` |
| error-state | ManeUI | `title`, `description`, `reference`, slot `action` ; `role="alert"` |
| loading-state | ManeUI | `kind` (`initial`, `action`, `background`, `queued`), `label` ; `role="status"` |
| skeleton | ManeUI | `lines` ; `aria-hidden`, texte pour lecteur d'écran |

Support : icon, link, spinner, theme-switch, theme-toggle, layout (+ header), sidebar (+ item, separator), otp-input, divider, nav-link, page-header, section-header.

Patterns éditoriaux (§37) : section, feature, stat, check-item, et utilitaires `mane-eyebrow`, `mane-display`.

Les états obligatoires (§41) sont documentés par composant dans `docs/design-system/components.md`
et visibles dans le catalogue.

## 5. DataTable

```php
trait WithDataTable
{
    abstract protected function tableQuery(): Builder;        // RLS et autorisation incluses
    /** @return list<Column> */ abstract protected function tableColumns(): array;
    /** @return list<Filter> */ protected function tableFilters(): array;
    /** @return list<BulkAction> */ protected function tableBulkActions(): array;
    abstract protected function tableKey(): string;          // clé stable des vues enregistrées
    abstract protected function tableDefaultSort(): array;
    abstract protected function tableCaption(): string;
}
```

- État URL : `search`, `sort`, `direction`, `filters`, `perPage`.
- Tri : colonne déclarée `sortable`, sinon tri par défaut ; direction `asc`/`desc` uniquement.
- Recherche : `ILIKE` sur les colonnes `searchable`, jokers échappés.
- Filtres : seulement les clés déclarées, valeurs sur liste d'options.
- Taille de page : 10, 25, 50, 100.
- Sélection de la page courante ; actions groupées sur 100 ids au plus.
- Colonnes visibles : jamais moins d'une.
- Densité : `comfortable`, `compact`, `dense`.
- Vues enregistrées : enregistrer, appliquer, supprimer ; état relu et revalidé.
- Jamais de `get()` sur la requête de la table : `paginate()` seulement.

## 6. Tests

- Architecture : aucun `x-ts-` ni contrôle brut hors ManeUI ; aucun utilitaire physique ; chaque
  composant ManeUI documenté et présent au catalogue.
- Rendu : sémantique de chaque composant (labels, `aria-*`, `role`), bouton désactivé inerte.
- DataTable : tri sur colonne non déclarée ignoré (y compris par l'URL) ; filtre et taille de page
  hors liste ignorés ; requête toujours limitée ; actions groupées limitées au périmètre de la table ;
  vues enregistrées privées et isolées par tenant.
- Formulaire : validation, refus d'autorisation, succès, réinitialisation.
- Navigateur : axe sans violation sérieuse ni critique sur connexion, inscription, accueil du
  workspace, paramètres, audit et catalogue ; navigation clavier et focus visible sur formulaire,
  modale et table ; rendu sombre et RTL sans débordement horizontal ; scénario §407.

## 7. Acceptance

GIVEN un bouton d'envoi désactivé, WHEN on navigue au clavier, THEN le focus reste visible,
l'état désactivé est annoncé et l'action ne s'exécute pas (§407).

## 8. Interdits

- un composant applicatif qui utilise `<x-ts-*>` ou un contrôle HTML brut ;
- une couleur hors des trois rampes ; un statut porté par la seule couleur ;
- un utilitaire de direction physique ;
- un tri, filtre ou taille de page non déclaré qui atteint SQL ;
- un `get()` sur une collection potentiellement massive ;
- Flux.
