# P02 — Isolation PostgreSQL (RLS)

> Spécification d'implémentation. Toute décision ci-dessous est figée pour cette passe.
> Référence : `todo/todo.md` §15.1, §17, §17.1, §18, §19, §207, §299.1, §494.
> Feuille de route : `todo/roadmap.md`. Prérequis : P01 accepté.

## 0. Résultat attendu

L'isolation entre tenants est garantie par PostgreSQL, plus seulement par l'application.
L'application tourne avec un rôle qui ne contourne pas la RLS : même une requête qui oublie
son filtre ne renvoie que les lignes du tenant courant, et une écriture dans un autre tenant
est refusée par la base.

## 1. Périmètre

### Inclus

- Trois rôles PostgreSQL et leur provisioning par une commande Artisan
- Migrations, seeders et `db:wipe` exécutés avec le rôle propriétaire
- Variables de session `app.tenant_id` et `app.user_id`, installées et remises à zéro
- RLS activée et forcée sur `tenants`, `workspaces`, `tenant_members`
- Helper de migration pour toute future table tenant-scoped
- Middleware de job pour les jobs tenant-scoped, échec fermé sans tenant
- Commande `tenancy:verify-rls`
- Suite de tests exécutée avec un rôle soumis à la RLS

### Reporté (avec passe)

| Sujet | Passe |
|:--|:--|
| Commandes Artisan métier `--tenant` et scheduler itérant les tenants : aucune commande métier n'existe encore ; elles arrivent avec la première, ainsi qu'une fonction `SECURITY DEFINER` listant les tenants | P10 |
| Lecture inter-tenants pour les opérateurs | P06 |
| Pooler de connexions en mode transaction (`SET LOCAL`) | P39, ADR dédié |
| Tests parallèles (`--parallel`) : la base parallèle doit être migrée par le rôle propriétaire | P39 |

## 2. Décisions figées

| Sujet | Décision |
|:--|:--|
| Rôles | `manecms_owner` (LOGIN, propriétaire des tables, `BYPASSRLS`, migrations et seeders), `manecms_app` (LOGIN, `NOSUPERUSER NOBYPASSRLS`, DML uniquement, runtime), `manecms_test` (LOGIN, `NOBYPASSRLS`, hérite de `manecms_app`, peut faire `SET ROLE manecms_owner` sans en hériter les droits) |
| Noms | Configurables : `DB_OWNER_USERNAME`, `DB_USERNAME`, `DB_TEST_USERNAME` ; valeurs par défaut ci-dessus |
| Provisioning | `php artisan database:provision`, lancé une fois avec le superuser (option `--superuser`), idempotent, par base (option `--database`, répétable) |
| Propriété | Toutes les tables et séquences du schéma `public` appartiennent à `manecms_owner` ; privilèges par défaut accordés à `manecms_app` |
| Migrations | Les commandes `migrate*`, `db:seed`, `db:wipe` basculent la connexion par défaut sur `pgsql_owner` le temps de la commande, si `DB_OWNER_USERNAME` est configuré. Laravel ne relaie pas `CommandStarting` sous PHPUnit : la suite passe `--database=pgsql_owner` à `migrate:fresh` (trait `RefreshDatabaseAsOwner`) |
| Variables | `app.tenant_id` (texte, ULID) et `app.user_id` (texte, entier) posées par `set_config(..., false)` ; vides = non définies |
| Prédicat tenant | `tenant_id = nullif(current_setting('app.tenant_id', true), '')` |
| Pré-résolution | Lecture seule : un user voit ses appartenances, les tenants et workspaces de ces appartenances (`app.user_id`) |
| Écritures | Toujours limitées au tenant courant (`app.tenant_id`), `USING` et `WITH CHECK` |
| Création d'un tenant | `CreateTenant` génère l'ULID, installe ce tenant comme contexte le temps de la transaction |
| Requête HTTP | Le middleware `ScopeDatabaseSession` (groupe `web`) pose `app.user_id`, puis remet les deux variables à zéro en fin de requête |
| Workspace | `TenantContext::install()` pose `app.tenant_id` et `app.user_id` |
| Jobs | Interface `TenantScopedJob` + middleware `RunInTenantContext` ; tenant absent : `TenantContextRequired` |
| Tests | Connexion `manecms_test` ; fixtures (factories, seeders) créées via `SET LOCAL ROLE manecms_owner` dans un savepoint, qui restaure le rôle de l'appelant ; vérifications globales via le helper `asOwner()` |

## 3. Policies

Pour chaque table : `ENABLE` + `FORCE ROW LEVEL SECURITY`, une policy par commande.

| Table | SELECT | INSERT / UPDATE / DELETE |
|:--|:--|:--|
| `tenants` | `id` = tenant courant **ou** `id` dans les appartenances du user | `id` = tenant courant |
| `workspaces` | `tenant_id` = tenant courant **ou** dans les appartenances du user | `tenant_id` = tenant courant |
| `tenant_members` | `tenant_id` = tenant courant **ou** `user_id` = user courant | `tenant_id` = tenant courant |

Les vérifications de clés étrangères et les cascades contournent la RLS (comportement
PostgreSQL) : la suppression d'un user retire toujours ses appartenances.

## 4. Fichiers

```text
app/Console/Commands/ProvisionDatabaseCommand.php     database:provision
app/Domain/Tenancy/Database/TenantDatabaseContext.php apply, clear, runAs, current, withoutRowSecurity
app/Domain/Tenancy/Database/TenantRowSecurity.php     enable / disable sur une table
app/Domain/Tenancy/Console/VerifyRowSecurity.php      tenancy:verify-rls
app/Domain/Tenancy/Http/Middleware/ScopeDatabaseSession.php
app/Domain/Tenancy/Jobs/TenantScopedJob.php
app/Domain/Tenancy/Jobs/Middleware/RunInTenantContext.php
database/factories/Concerns/BypassesRowSecurity.php   store() en SET ROLE propriétaire
database/migrations/…_enable_row_level_security_on_tenancy_tables.php
tests/Feature/Tenancy/RowLevelSecurityTest.php
tests/Feature/Tenancy/TenantJobTest.php
```

Modifiés : `config/database.php` (connexion `pgsql_owner`, `roles`), `TenantContext`,
`CreateTenant`, `TenancyServiceProvider`, `bootstrap/app.php`, `phpunit.xml`, `.env.example`,
`composer.json` (scripts), CI, factories Tenancy, tests Tenancy existants.

## 5. Tests obligatoires (rôle `manecms_test`, soumis à la RLS)

- le rôle de test n'est ni superuser, ni `BYPASSRLS`, ni propriétaire des tables
- sans contexte : aucune ligne lue dans les trois tables
- contexte A : lit A, ne lit pas B, sur chaque table et en jointure
- insertion d'un workspace du tenant B depuis le contexte A : refusée par la base (`42501`)
- `UPDATE` et `DELETE` visant B depuis A : aucune ligne touchée
- contexte user : voit ses appartenances, pas celles des autres
- requête qui oublie son filtre (`Workspace::query()->count()`) : seulement le tenant courant
- aucune variable ne subsiste après une requête HTTP
- job tenant-scoped : lit son tenant seulement ; sans tenant, échec fermé
- `tenancy:verify-rls` vert ; rouge si une table avec `tenant_id` n'a pas de RLS forcée
- toutes les suites existantes vertes avec le rôle de test

## 6. Rejet

- un test passe parce qu'il s'exécute avec un rôle qui contourne la RLS ;
- une table tenant-scoped n'a pas la RLS forcée ;
- une variable de contexte survit à la requête ou au job qui l'a posée ;
- une migration commitée est modifiée ;
- un gate est rouge, en local ou en CI.
