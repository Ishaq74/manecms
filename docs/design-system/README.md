# ManeUI — design system

ManeUI est le design system de ManeCMS (`todo/todo.md` §6, §7, §37–§43, §283).

```text
TallStackUI  →  ManeUI (resources/views/mane)  →  vues applicatives
```

- [Tokens](tokens.md) : primitives, sémantique, composant, densité, mouvement, sombre, RTL.
- [Composants](components.md) : API et états de chaque composant.
- Catalogue interne : `/_mane` (environnements `local` et `testing` uniquement).

## Règles d'usage

1. **Les vues applicatives consomment ManeUI.** `<x-ts-*>` n'existe que dans `resources/views/mane/`.
   Les contrôles HTML bruts (`<button>`, `<input>` hors `type="hidden"`, `<select>`, `<textarea>`)
   aussi. Un test d'architecture (`tests/Architecture/ManeUiTest.php`) l'impose.
2. **Une API sémantique, pas des styles.** On choisit une intention (`variant="danger"`,
   `tone="warning"`, `kind="no-results"`), jamais une couleur. Une valeur inconnue lève une exception.
3. **Trois rampes de couleur.** `primary`, `secondary`, `dark`. Un statut associe toujours une icône
   et un texte : la couleur seule ne porte aucune information (WCAG 1.4.1).
4. **Propriétés logiques.** `ms-`, `me-`, `ps-`, `pe-`, `start-`, `end-`, `text-start`, `border-e`…
   Les utilitaires physiques (`ml-`, `left-`, `text-right`…) sont refusés par les tests. Une icône
   directionnelle se retourne avec `rtl:rotate-180`.
5. **Focus toujours visible.** La base CSS dessine un contour `--mane-focus` sur `:focus-visible`.
6. **Désactivé ≠ caché.** `disabled` rend `aria-disabled="true"` : le bouton reste atteignable au
   clavier, il est annoncé comme désactivé et ne déclenche rien (§407).
7. **Pas de chargement complet.** Une liste potentiellement longue passe par le DataTable, qui pagine
   toujours (§485).
8. **Pas de Flux** (§6).

## Formulaires (§281)

Un formulaire Livewire combine `<x-mane::form>` et le trait `App\Livewire\Concerns\HasFormContract` :

```blade
<x-mane::form wire:submit="renameWorkspace">
    <x-mane::input wire:model="name" :label="__('Name')" required />

    <x-slot:actions>
        <x-mane::button variant="ghost" wire:click="resetForm" :text="__('Discard changes')" />
        <x-mane::button type="submit" loading="renameWorkspace" :text="__('Save')" />
    </x-slot:actions>
</x-mane::form>
```

| Contrat | Où |
|:--|:--|
| Validation | action applicative ; résumé d'erreurs annoncé (`role="alert"`) en tête de formulaire |
| Autorisation | `mount()` et action applicative (`Gate::authorize`) |
| État sale | `wire:dirty` : « You have unsaved changes. » |
| Envoi, chargement | `loading="méthode"` : bouton désactivé et indicateur ; `aria-busy` sur le formulaire |
| Erreur | erreurs de champ, erreur métier (`DomainError`) en toast (P03) |
| Succès | `formSucceeded(message)` : toast de succès |
| Réinitialisation | `resetForm()` → `fillForm()` remet les valeurs persistées et efface les erreurs |

## Tables (§282)

Un composant Livewire utilise le trait `App\Livewire\DataTable\WithDataTable` et inclut sa vue :

```php
use WithDataTable;

protected function tableQuery(): Builder { return AuditEvent::query(); }  // RLS en plus
protected function tableColumns(): array
{
    return [
        Column::make('occurred_at', __('When'))->sortable()->alwaysVisible(),
        Column::make('action', __('Action'))->sortable()->searchable()->monospace(),
    ];
}
protected function tableFilters(): array { return [Filter::make('source', __('Source'), ['http' => 'http'])]; }
protected function tableBulkActions(): array { return [BulkAction::make('archive', __('Archive'), fn ($rows) => ...)]; }
protected function tableKey(): string { return 'audit.events'; }
protected function tableDefaultSort(): array { return ['occurred_at', 'desc']; }
protected function tableCaption(): string { return __('Audit events of this space'); }
```

```blade
@include('mane.livewire.data-table')
```

| Fonction | Garantie |
|:--|:--|
| Tri | colonnes déclarées `sortable()` uniquement ; toute autre valeur (URL comprise) est ignorée |
| Recherche | `ILIKE` sur les colonnes `searchable()`, jokers échappés, 100 caractères au plus |
| Filtres | clés déclarées, valeurs parmi les options |
| Pagination | serveur, 10/25/50/100 lignes ; `tableUsesSimplePagination()` évite le comptage |
| Sélection, actions groupées | page courante ; lignes relues par `tableQuery()` (100 au plus) |
| Colonnes visibles | au moins une ; `alwaysVisible()` ne se masque pas |
| Densité | confortable, compacte, dense |
| Vues enregistrées | `saved_table_views`, RLS par tenant et policy restrictive par auteur |

Reporté : export (P14) ; tri multiple, redimensionnement, réordonnancement, épinglage, édition en
ligne, regroupement, agrégation (P66).

## Tests

- `tests/Architecture/ManeUiTest.php` : frontières ManeUI, direction, palette, documentation, catalogue.
- `tests/Feature/ManeUi/` : sémantique des composants, DataTable, formulaire, catalogue.
- `tests/Browser/` : axe, clavier, focus, sombre, RTL, §407 (Pest Browser + Playwright Chromium).
