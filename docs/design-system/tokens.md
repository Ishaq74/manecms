# ManeUI — tokens (§38)

Sources : `resources/css/mane/tokens.css`, `resources/css/mane/base.css`.

```text
Primitive  →  Sémantique  →  Composant  →  Page
```

## Primitives

| Token | Valeurs |
|:--|:--|
| `--color-primary-50…950` | bleu, teinte 262 |
| `--color-secondary-50…950` | sarcelle, teinte 172 |
| `--color-dark-50…950` | neutre, teinte 268 |
| `--font-sans` | Instrument Sans |
| `--font-display` | Sora (`font-display`) |

TallStackUI colore tous ses composants depuis ces trois rampes ; `App\Providers\TallStackUiServiceProvider`
et `App\View\Components\TallStackUi\Colors\*` remplacent ses gris et ses couleurs brutes.

## Sémantique

Variables `--mane-*`, redéfinies sous `.dark`, exposées à Tailwind par `@theme inline`.

| Utilitaire | Clair | Sombre | Usage |
|:--|:--|:--|:--|
| `bg-surface` | blanc | dark-900 | page |
| `bg-surface-raised` | blanc | dark-800 | carte, panneau, champ |
| `bg-surface-sunken` | dark-50 | dark-950 | en-tête de table, zone en retrait |
| `text-fg` | dark-900 | dark-100 | texte principal |
| `text-fg-muted` | dark-600 | dark-400 | texte secondaire (≥ 4.5:1) |
| `border-line` | dark-200 | dark-700 | séparateurs |
| `border-line-strong` / `ring-line-strong` | dark-500 | dark-400 | bordure de contrôle (≥ 3:1) |
| `ring-focus`, contour de focus | primary-600 | primary-400 | focus visible (≥ 3:1) |
| `text-danger` | primary-700 | primary-300 | erreur |
| `text-success` | secondary-600 | secondary-400 | succès |
| `text-warning` | dark-700 | dark-300 | avertissement |
| `text-info` | primary-600 | primary-400 | information |
| `text-brand` | primary-600 | primary-400 | accent de marque : liens, icônes, chiffres clés |
| `bg-brand-subtle` | primary-50 | primary-500 à 10 % | fond d'accent discret |
| `bg-surface-hover` | dark-800 à 5 % | blanc à 10 % | survol d'une ligne, d'un item de navigation |

Un ton n'est jamais porté par la couleur seule : `x-mane::status` et `x-mane::alert` affichent
toujours une icône propre au ton et un texte.

## Composant

| Utilitaire | Token | Confortable | Compacte | Dense |
|:--|:--|:--|:--|:--|
| `h-control` | `--mane-control-h` | 2.5rem | 2.25rem | 2rem |
| `px-cell-x` | `--mane-cell-px` | 1rem | 0.75rem | 0.5rem |
| `py-cell-y` | `--mane-cell-py` | 0.75rem | 0.5rem | 0.25rem |
| `gap-stack` | `--mane-stack` | 1.5rem | 1rem | 0.75rem |
| `rounded-control` | `--radius-control` | `--radius-md` | | |
| `rounded-surface` | `--radius-surface` | `--radius-xl` | | |

La densité (§467) s'applique avec `data-density="comfortable|compact|dense"` sur un conteneur.
Le DataTable la pose sur la table.

## Typographie éditoriale

| Utilitaire | Usage |
|:--|:--|
| `mane-eyebrow` | surtitre de section (Sora, capitales, accent de marque) |
| `mane-display` | titre éditorial fluide (Sora, `clamp(1.8rem, 4vw, 2.9rem)`) |

## Mouvement et superposition

| Token | Valeur |
|:--|:--|
| `--mane-duration-fast` / `-base` / `-slow` | 120 / 200 / 320 ms |
| `--mane-z-sticky` / `-overlay` / `-dropdown` / `-toast` | 30 / 50 / 55 / 60 |

Menus et popovers sont téléportés dans `<body>` et positionnés par Floating UI (`x-anchor`) : ils
échappent aux `overflow` et aux contextes d'empilement, se retournent et se décalent pour rester à
l'écran, et passent au-dessus des modales.

`prefers-reduced-motion: reduce` ramène animations et transitions à un instant (WCAG 2.3.3).

## Thème (§468)

Clair, sombre, système. `partials/head` applique la classe `dark` sur `<html>` avant le premier
rendu. `x-mane::theme-switch` choisit le mode, `x-mane::theme-toggle` bascule clair/sombre.

## Direction (§38)

`<html dir>` vient de `App\Enums\TextDirection::current()` (locale `ar`, `fa`, `he`, `ur`… → `rtl`).
Seules les propriétés logiques sont permises ; `x-mane::drawer side="start|end"` suit la direction.
