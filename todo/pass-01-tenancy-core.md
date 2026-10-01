# P01 — Tenancy core

> Spécification d'implémentation. Toute décision ci-dessous est figée pour cette passe.
> Référence : `todo/todo.md` §0.1, §2.1, §4.2, §11.1, §13, §15.1, §16–§16.4, §20.1, §263, §279–§281, §299.1, §494.
> Feuille de route : `todo/roadmap.md`. Prérequis : P00 accepté.
> Remplace les brouillons précédents de la passe 01.

## 0. Résultat attendu

Un utilisateur vérifié peut créer son tenant et son premier workspace, travailler dans
`/w/{workspace}`, créer, renommer et archiver des workspaces selon son rôle, basculer entre
ses workspaces (y compris d'un tenant à l'autre) et retrouver son dernier workspace à la
reconnexion. Il ne peut jamais lire, deviner ou modifier un tenant dont il n'est pas membre.

Ce que P01 n'est pas : la RLS (P02), l'audit (P03), les invitations (P05), les organisations
métier (P13), les médias et logos (P14). Ces sujets ont leur jalon ; ils n'entrent pas ici.

## 1. Périmètre

### Inclus

- Modèles `Tenant`, `Workspace`, `TenantMember`, enum `TenantRole`
- Contexte de requête `TenantContext`, résolu par l'URL `/w/{workspace}`
- Onboarding : création tenant + owner + premier workspace
- Workspaces : créer, renommer, archiver, basculer, mémoriser le dernier utilisé
- Tenant : renommer
- Policies, middleware, exception `TenantContextRequired`
- Blocage de la suppression de compte d'un owner (§20.1)
- Contrat de domaine, ADR, tests d'architecture minimum

### Reporté (avec jalon)

| Sujet | Jalon |
|:--|:--|
| PostgreSQL RLS sur les trois tables | P02 |
| Audit append-only, correlation id (les actions de P01 y seront branchées) | P03 |
| Invitations, liste des membres, changement de rôle, transfert de propriété | P05 |
| Restriction d'accès par workspace, Policy Engine, capabilities | P05 |
| Archivage / suppression d'un tenant | P05 |
| Locale, fuseau, devise du tenant ou du workspace | P07, P08 |
| Party, Organization, Legal Entity, Site | P12, P13 |
| Logo, avatar, tout upload | P14 |
| Slugs, sous-domaines, URL publiques | P18 |
| Redis, Meilisearch, S3, Reverb | §4.2 |

## 2. Décisions figées

| Sujet | Décision |
|:--|:--|
| Emplacement du code | `app/Domain/Tenancy/` (§13). Première création de `app/Domain`, validée par le cahier. |
| Identifiants | ULID sur les trois tables. `users.id` reste entier. |
| URL | `/w/{workspace}` avec l'ULID. Aucun slug en P01. |
| Source du contexte | L'URL. Pas de workspace courant en session. |
| Dernier workspace | `tenant_members.last_workspace_id`, mis à jour seulement s'il change. |
| Accès | Tout membre accède à tous les workspaces actifs de son tenant. |
| Non-membre | 404, jamais 403 : on ne révèle pas l'existence. |
| Membre sans le rôle requis | 403. |
| Owner | Exactement un par tenant, garanti par un index unique partiel. |
| Suppression | Workspace : archivage (`archived_at`), jamais de suppression ni de soft delete. Tenant : ni archivage ni suppression en P01. |
| Nom du premier workspace | Identique au nom du tenant saisi à l'onboarding. |
| Unicité des noms | Workspace : unique par tenant, insensible à la casse, archivés compris. Tenant : non unique. |
| Plusieurs tenants | Autorisé : un user peut en créer plusieurs ou être membre de plusieurs. |
| Fin de l'ancien `/dashboard` | Devient un routeur d'entrée : redirige vers le dernier workspace ou vers l'onboarding. |

## 3. Schéma

Toutes les dates en `timestampTz`. Clés étrangères en `restrictOnDelete` sauf mention.

### 3.1 `tenants`

| Colonne | Type | Contraintes |
|:--|:--|:--|
| `id` | ulid | PK |
| `name` | string(120) | not null |
| `created_at`, `updated_at` | timestampTz | |

### 3.2 `workspaces`

| Colonne | Type | Contraintes |
|:--|:--|:--|
| `id` | ulid | PK |
| `tenant_id` | ulid | not null, FK `tenants.id` |
| `name` | string(120) | not null |
| `archived_at` | timestampTz | nullable |
| `created_at`, `updated_at` | timestampTz | |

Index :

- `unique (tenant_id, id)` : cible des clés étrangères composites (§15.1)
- `unique (tenant_id, lower(name))` : expression index via `DB::statement`
- `index (tenant_id, archived_at)`

### 3.3 `tenant_members`

| Colonne | Type | Contraintes |
|:--|:--|:--|
| `id` | ulid | PK |
| `tenant_id` | ulid | not null, FK `tenants.id` |
| `user_id` | bigint | not null, FK `users.id` **cascadeOnDelete** (lien technique, §20.1) |
| `role` | string(20) | not null, cast `TenantRole` |
| `last_workspace_id` | ulid | nullable |
| `created_at`, `updated_at` | timestampTz | |

Index et contraintes :

- `unique (tenant_id, user_id)`
- `unique (tenant_id) where role = 'owner'` : index partiel via `DB::statement`
- `index (user_id)`
- FK composite `(tenant_id, last_workspace_id) → workspaces (tenant_id, id)` : le dernier workspace appartient forcément au tenant

### 3.4 Migrations

Ordre : `create_tenants_table`, `create_workspaces_table`, `create_tenant_members_table`.
Créées par `php artisan make:migration`. Aucune migration existante modifiée. Le `down()` est
implémenté. `migrate:fresh` doit passer sur une base vide.

## 4. Fichiers

```text
app/Domain/Tenancy/
├── Enums/TenantRole.php                  Owner, Admin, Member (+ canManageWorkspaces(), canManageTenant())
├── Models/Tenant.php                     HasUlids, hasMany workspaces/members
├── Models/Workspace.php                  HasUlids, belongsTo tenant, scope active()
├── Models/TenantMember.php               HasUlids, belongsTo tenant/user/lastWorkspace, cast role
├── Context/TenantContext.php             scoped : tenant(), workspace(), member() ; lève l'exception si absent
├── Http/Middleware/ResolveWorkspace.php  résolution + vérification + enregistrement du contexte
├── Actions/CreateTenant.php
├── Actions/RenameTenant.php
├── Actions/CreateWorkspace.php
├── Actions/RenameWorkspace.php
├── Actions/ArchiveWorkspace.php
├── Actions/ResolveEntryWorkspace.php     dernier workspace valide ou null
├── Policies/TenantPolicy.php
├── Policies/WorkspacePolicy.php
└── Exceptions/TenantContextRequired.php

database/factories/{Tenant,Workspace,TenantMember}Factory.php
resources/views/pages/onboarding/⚡tenant.blade.php
resources/views/pages/workspaces/⚡home.blade.php       reprend le contenu actuel du dashboard
resources/views/pages/workspaces/⚡create.blade.php
resources/views/pages/workspaces/⚡settings.blade.php    renommer, archiver
resources/views/pages/tenants/⚡settings.blade.php       renommer
composant de bascule de workspace dans le shell existant
docs/domains/tenancy/context.md                         contrat §12, version courte
docs/adr/0002-tenancy-model.md                          décisions de la section 2
tests/Architecture/DomainTest.php
```

Câblage :

- Modèles hors `App\Models` : `#[UseFactory]` sur les modèles, `#[UsePolicy]` ou `Gate::policy()` pour les policies.
- `User::tenantMemberships()` (hasMany `TenantMember`) : seul ajout au modèle `User`.
- `TenantContext` enregistré en `scoped` dans un provider dédié ajouté à `bootstrap/providers.php`.
- `ResolveWorkspace` déclaré en middleware persistant Livewire, pour que les requêtes `/livewire/update` retrouvent le contexte.

## 5. Routes

| Méthode | URI | Nom | Middleware |
|:--|:--|:--|:--|
| GET | `/dashboard` | `dashboard` | auth, verified : redirige vers `workspace.home` ou `onboarding` |
| GET | `/onboarding` | `onboarding` | auth, verified |
| GET | `/w/{workspace}` | `workspace.home` | auth, verified, ResolveWorkspace |
| GET | `/w/{workspace}/workspaces/create` | `workspace.create` | idem ; `authorize('create')` dans `mount()` |
| GET | `/w/{workspace}/settings` | `workspace.settings` | idem |
| GET | `/w/{workspace}/tenant/settings` | `tenant.settings` | idem |

Le nom `dashboard` est conservé : Fortify et le shell existant y redirigent déjà.

L'autorisation des pages de gestion se fait dans `mount()` plutôt que par le middleware `can` :
le tri de priorité des middlewares peut exécuter `Authorize` avant `ResolveWorkspace`, donc
avant l'installation du contexte.

## 6. Contexte de requête

`ResolveWorkspace` :

1. Lit l'ULID de la route. Un ULID mal formé donne 404.
2. Charge le workspace actif puis l'appartenance de l'utilisateur à son tenant (deux requêtes indexées).
3. Workspace inconnu, archivé ou user non membre : 404.
4. Installe `TenantContext` (tenant, workspace, member).
5. Met à jour `last_workspace_id` uniquement s'il diffère.

`TenantContext` :

- Aucun appel à `session()`, `request()` ou `auth()` dans les actions ou les vues pour obtenir le tenant : tout passe par le contexte.
- Lire le contexte avant son installation lève `TenantContextRequired` : fail closed.

`ResolveEntryWorkspace(User)` :

1. Si `last_workspace_id` pointe vers un workspace actif d'un tenant dont le user est membre, il est retenu. On prend l'appartenance mise à jour le plus récemment.
2. Sinon, premier workspace actif (par `created_at`, puis `id`) de la première appartenance (par `created_at`).
3. Sinon `null`, ce qui envoie vers l'onboarding.

## 7. Actions et règles

Toutes les actions s'exécutent dans `DB::transaction`. L'autorisation est vérifiée dans l'action
ou par la policy appelée par le composant, jamais seulement dans la vue. Les identifiants de
tenant et de workspace viennent du contexte, jamais de l'input.

| Action | Rôles | Règles |
|:--|:--|:--|
| `CreateTenant(user, name)` | tout user vérifié | Crée tenant, workspace du même nom, appartenance owner, `last_workspace_id`. Atomique. Limitée à 5 par heure et par user. |
| `RenameTenant(context, name)` | owner, admin | Nom validé. |
| `CreateWorkspace(context, name)` | owner, admin | Créé dans le tenant du contexte. Nom unique dans le tenant. Redirection vers le nouveau workspace. |
| `RenameWorkspace(context, workspace, name)` | owner, admin | Même tenant obligatoire. Nom unique. |
| `ArchiveWorkspace(context, workspace)` | owner, admin | Refus si c'est le dernier workspace actif du tenant. Si c'est le workspace courant, redirection vers le point d'entrée. Les `last_workspace_id` qui le visent sont remis à `null`. |

Validation des noms (§15.1) : `Str::squish`, puis `required|string|min:2|max:120`. Unicité
vérifiée par une règle insensible à la casse ; `UniqueConstraintViolationException` est convertie
en erreur de validation sur `name`. Messages via `__()`.

## 8. Matrice d'autorisation

| Action | owner | admin | member | non-membre |
|:--|:--|:--|:--|:--|
| Voir un workspace du tenant | ✅ | ✅ | ✅ | 404 |
| Créer un workspace | ✅ | ✅ | 403 | 404 |
| Renommer ou archiver un workspace | ✅ | ✅ | 403 | 404 |
| Renommer le tenant | ✅ | ✅ | 403 | 404 |
| Supprimer son compte | ❌ tant qu'il est owner | ✅ | ✅ | — |

## 9. UI

- Composants TallStackUI du projet, aucune nouvelle librairie UI.
- Formulaires conformes à §281 : validation, autorisation, état sale, `wire:loading`, bouton désactivé pendant l'envoi, erreurs par champ, toast de succès, remise à zéro après création.
- Livewire : identifiants en `#[Locked]`, rechargés via le contexte à chaque action. Aucune logique de domaine dans le composant (§280).
- Bascule de workspace : workspaces actifs groupés par tenant, workspace courant marqué, lien « Créer un workspace » si autorisé, lien « Créer un nouvel espace » vers l'onboarding.
- Actions non autorisées masquées dans l'UI et refusées côté serveur.
- Archivage : modale de confirmation.
- Textes via `__()`, traduits dans `lang/fr.json` et `lang/en.json` (D7).

## 10. Suppression de compte

Le formulaire existant `pages/settings/⚡delete-user-form` refuse la suppression si le user est
owner d'un tenant, avec un message invitant à transférer la propriété (fonction disponible en P05).
Pour un admin ou un member, la cascade sur `tenant_members.user_id` retire ses appartenances.

## 11. Seeder

`DatabaseSeeder` crée, en environnement `local` uniquement, un tenant et un workspace pour
le compte seeder existant. Aucune donnée de démo par défaut (§491).

## 12. Tests

Pest, factories avec états `owner()`, `admin()`, `member()` et `archived()`. Noms de tests
écrits comme des règles métier (§495). Suite exécutée sur PostgreSQL `manecms_testing` (P00,
§299.1) : index partiels, expressions `lower()` et clés composites sont testés sur le moteur réel.

`tests/Feature/Tenancy/OnboardingTest.php`

- redirige un utilisateur sans tenant de /dashboard vers l'onboarding
- crée tenant, workspace et owner en une seule opération
- n'écrit rien si la création échoue en cours de transaction
- rejette les noms vides, trop courts ou trop longs, après normalisation des espaces
- limite le nombre de tenants créés par heure

`tests/Feature/Tenancy/WorkspaceAccessTest.php`

- un membre voit les workspaces actifs de son tenant
- un non-membre reçoit 404 sur un workspace existant
- un ULID mal formé ou un workspace archivé donne 404
- une requête Livewire sans workspace valide est refusée

`tests/Feature/Tenancy/WorkspaceManagementTest.php`

- owner et admin créent, renomment et archivent ; member reçoit 403
- un nom de workspace est unique dans le tenant, sans tenir compte de la casse, archivés compris
- deux tenants peuvent avoir des workspaces du même nom
- le dernier workspace actif ne peut pas être archivé
- un `workspace_id` falsifié ne cible jamais le workspace d'un autre tenant

`tests/Feature/Tenancy/EntryWorkspaceTest.php`

- revient au dernier workspace utilisé
- se replie de façon déterministe quand le dernier workspace est archivé
- un user membre de deux tenants retrouve le bon workspace

`tests/Feature/Tenancy/DatabaseInvariantsTest.php`

- la base refuse un second owner pour un tenant
- la base refuse un doublon `(tenant_id, user_id)`
- la base refuse un `last_workspace_id` appartenant à un autre tenant
- la base refuse un doublon de nom de workspace qui ne diffère que par la casse

`tests/Feature/Settings/DeleteAccountTest.php`

- un owner ne peut pas supprimer son compte
- la suppression d'un member retire son appartenance sans toucher au tenant

`tests/Architecture/DomainTest.php`

- `App\Domain` ne dépend pas de `Livewire` (§279)

Tests existants : adapter ceux qui ciblent `/dashboard` (`DashboardTest`, shell, sidebar, menu
utilisateur) au nouveau parcours. Aucun test supprimé.

## 13. Ordre d'implémentation

Un commit par étape, gates verts à chaque commit.

1. **Schéma** : enum, migrations, modèles, factories, `DatabaseInvariantsTest`.
2. **Contexte** : `TenantContext`, exception, middleware, `ResolveEntryWorkspace`, routes, `WorkspaceAccessTest`, `EntryWorkspaceTest`.
3. **Actions** : actions, policies, `OnboardingTest`, `WorkspaceManagementTest`.
4. **UI** : pages Livewire, bascule dans le shell, adaptation des tests existants.
5. **Compte et finition** : suppression de compte, seeder, tests d'architecture, `context.md`, ADR 0002.

Gates :

```bash
herd php artisan migrate:fresh
herd php artisan test --compact
herd php bin/check.php --full
pnpm run build
```

## 14. Rejet

La passe est refusée si un seul de ces points est vrai :

- un non-membre obtient autre chose qu'un 404 sur une ressource d'un tenant ;
- un identifiant de tenant ou de workspace est lu depuis l'input sans passer par le contexte ;
- un invariant de la section 3 n'est protégé que par l'application ;
- une création peut laisser des lignes orphelines ;
- un composant Livewire contient une règle métier ;
- une migration existante est modifiée ;
- un sujet reporté de la section 1 est implémenté ;
- un gate de la section 13 est rouge.
