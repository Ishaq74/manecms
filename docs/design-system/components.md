# ManeUI — composants (§39, §41)

Chaque composant vit dans `resources/views/mane/` et s'utilise en `<x-mane::nom>`. Les états communs
s'appliquent à tous : **sombre** (tokens sémantiques), **RTL** (propriétés logiques), **mouvement
réduit** (base CSS), **mobile** (mise en page fluide, cibles ≥ 24 px), **focus visible** (base CSS).
Les colonnes ci-dessous listent les états propres à chaque composant.

États non portés par les composants de base : *offline* (file hors ligne, §40) et *permission
denied* (rendu par l'écran avec `x-mane::empty-state kind="no-access"`).

## Actions

| Composant | API | États |
|:--|:--|:--|
| `x-mane::button` | `variant` (`primary`, `secondary`, `ghost`, `danger`), `size` (`sm`, `md`, `lg`), `type`, `href`, `navigate`, `icon`, `icon-position` (`start`, `end`), `loading`, `disabled`, `block`, `text` | default, hover, focus-visible, active, disabled (`aria-disabled`, focalisable, inerte), loading (`loading="méthode"`) |
| `x-mane::icon-button` | `icon`, `label` (obligatoire, nom accessible), `variant`, `size`, `type`, `href`, `navigate`, `loading`, `disabled` | comme `button` |
| `x-mane::link` | `href`, `text`, `navigate`, `underline`, `colorless`, `size`, `blank` | default, hover, focus-visible |
| `x-mane::icon` | `name` (Heroicons), `label` | décorative (`aria-hidden`) ou annoncée (`role="img"`) si `label` |

## Champs

| Composant | API | États |
|:--|:--|:--|
| `x-mane::input` | `label`, `hint`, `icon`, `icon-position`, `prefix`, `suffix`, `clearable` + attributs HTML | default, focus, error (message lié au champ), disabled, readonly |
| `x-mane::password` | `label`, `hint`, `rules` | default, focus, error, révélation du mot de passe, verrouillage majuscules |
| `x-mane::textarea` | `label`, `hint`, `count`, `resize` | default, focus, error, disabled, readonly, compteur |
| `x-mane::select` | `label`, `hint`, `options` (valeur ⇒ libellé), `placeholder` | default, focus, error (`aria-invalid`), disabled |
| `x-mane::checkbox` | `label` | default, checked, focus, error, disabled |
| `x-mane::radio` | `label`, `value` | default, selected, focus, error, disabled |
| `x-mane::switch` | `label` | off, on (`role="switch"`), focus, disabled |
| `x-mane::pin` | `label`, `hint`, `length` | default, focus, error, rempli |
| `x-mane::otp-input` | `name`, `label`, `length` | vide, saisie chiffre par chiffre, collage complet, retour arrière |

## Retour d'information

| Composant | API | États |
|:--|:--|:--|
| `x-mane::badge` | `variant` (`primary`, `secondary`, `muted`), `size`, `icon`, `text` | statique |
| `x-mane::status` | `tone` (`success`, `warning`, `danger`, `info`, `muted`), `text` | une icône et un texte par ton |
| `x-mane::alert` | `tone` (`success`, `warning`, `danger`, `info`), `title`, `text`, `dismissible` | `role="alert"` (danger, warning), `role="status"` (autres), fermée |
| `x-mane::toast` | région unique par layout ; envoi par `$this->toast()` | success, error, info, warning |
| `x-mane::tooltip` | `text`, `position` (`top`, `bottom`) ; le déclencheur est le slot | masqué, survol, focus, Échap (WCAG 1.4.13) |
| `x-mane::popover` | `label`, `icon`, `align` (`start`, `end`), `placement` (`bottom`, `top`) | fermé, ouvert (`aria-expanded`), Échap et clic extérieur ferment, focus rendu |
| `x-mane::spinner` | `size` | décoratif (`aria-hidden`) ; l'annonce passe par `loading-state` |

## Superpositions et menus

| Composant | API | États |
|:--|:--|:--|
| `x-mane::modal` | `id`, `title`, `size`, `wire`, `persistent`, slot `footer` | fermée, ouverte (focus piégé), Échap |
| `x-mane::drawer` | `id`, `title`, `size`, `side` (`start`, `end`), `wire`, `persistent`, `paddingless`, slot `footer` | fermé, ouvert ; le côté suit la direction |
| `x-mane::dropdown` | `label`, `icon`, `align` (`start`, `end`), `placement` (`bottom`, `top`), slots `trigger`, `header` | fermé, ouvert ; flèches, Début, Fin, Échap, Tab ; positionné par Floating UI (bascule, décalage), au-dessus des modales |
| `x-mane::dropdown.item` | `text`, `icon`, `href`, `navigate`, `separator` | default, hover, focus |
| `x-mane::dropdown.group` | `label` ; regroupe des items sous un intitulé (`role="group"`) | statique |
| `x-mane::dropdown.trigger` | déclencheur pleine largeur du slot `trigger` | fermé, ouvert (`aria-expanded`) |

## Navigation et structure

| Composant | API | États |
|:--|:--|:--|
| `x-mane::tabs` / `x-mane::tabs.item` | `selected` ; `tab`, `title` | selected, focus |
| `x-mane::accordion` / `x-mane::accordion.item` | `multiple` ; `title`, `open`, `id` | fermé, ouvert |
| `x-mane::breadcrumb` | `items` (`label`, `href`) | dernier élément `aria-current="page"` |
| `x-mane::pagination` | `paginator` (Livewire, complet ou simple) | première page, dernière page (précédent/suivant désactivés), intermédiaire |
| `x-mane::divider` | `label` | trait simple, ou séparateur libellé (« ou continuer avec ») sans fond |
| `x-mane::card` | `paddingless`, `rounded`, slots `header`, `footer` | statique |
| `x-mane::avatar` | `model`, `text`, `image`, `size` | initiales, image |
| `x-mane::page-header` | `title`, `description` (prop ou slot), `align` (`start`, `center`), slot `actions` | le seul `h1` d'une page |
| `x-mane::section-header` | `title`, `description` (prop ou slot), `level` (`2`, `3`) | titre de section et son introduction |
| `x-mane::nav-link` | `href`, `current`, `icon`, `navigate` | default, survol, courant (`aria-current="page"`) |
| `x-mane::form` | attributs du `<form>`, `dirty-notice`, slot `actions` | voir le contrat §281 dans le [README](README.md) |

## Patterns éditoriaux (§37)

Utilisés par les pages marketing (`home`).

| Composant | API | États |
|:--|:--|:--|
| `x-mane::section` | `title`, `eyebrow`, `lead`, `tone` (`default`, `sunken`), `align` (`start`, `center`), slot | bande pleine largeur, conteneur interne |
| `x-mane::feature` | `title`, `icon`, `align`, slot (description) | carte à icône |
| `x-mane::stat` | `value`, `label`, `delta` | chiffre clé |
| `x-mane::check-item` | `title`, slot | élément de liste précédé d'une coche (dans un `<ul>`) |

## États d'écran (§464–§466)

| Composant | API | États |
|:--|:--|:--|
| `x-mane::empty-state` | `kind` (`empty`, `first-use`, `no-access`, `no-results`, `error`), `title`, `description`, slot `action` | un titre et une icône par cas |
| `x-mane::error-state` | `title`, `description`, `reference`, slot `action` | `role="alert"`, référence de support |
| `x-mane::loading-state` | `kind` (`initial`, `action`, `background`, `queued`), `label` | `role="status"`, annonce polie |
| `x-mane::skeleton` | `lines` | animé, figé en mouvement réduit, annoncé une fois |

## Cadre d'application

Composants du shell, utilisés par `layouts/sidebar` et `partials/header` :

| Composant | Rôle |
|:--|:--|
| `x-mane::layout` / `x-mane::layout.header` | cadre : slots `menu`, `header`, `footer` ; `left`, `right` |
| `x-mane::sidebar` / `x-mane::sidebar.item` / `x-mane::sidebar.separator` | navigation latérale repliable ; `text`, `href`, `icon`, `current` |
| `x-mane::theme-switch` | choix clair, sombre, système |
| `x-mane::theme-toggle` | bascule clair/sombre d'en-tête (`role="switch"`) |

## Tables

Le DataTable n'est pas une balise : c'est le trait `App\Livewire\DataTable\WithDataTable` et la vue
`mane/livewire/data-table.blade.php`. Voir le [README](README.md).

États : chargement (opacité pendant la requête), vide, aucun résultat (filtres actifs), lignes
sélectionnées, ligne dépliée, tri croissant/décroissant (`aria-sort`), trois densités.
