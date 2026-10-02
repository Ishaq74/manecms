# P03 — Audit, corrélation, erreurs typées

> Spécification d'implémentation. Toute décision ci-dessous est figée pour cette passe.
> Référence : `todo/todo.md` §153, §154, §191, §263, §264, §325, §367, §368, §385, §392, §473.
> Feuille de route : `todo/roadmap.md`. Prérequis : P02 accepté.

## 0. Résultat attendu

Chaque opération sensible laisse une trace inaltérable qui répond à qui, quoi, quand, où,
pourquoi, avant, après, source et corrélation. Une requête, ses jobs, ses logs et ses
événements d'audit partagent le même identifiant de corrélation. Une erreur métier a un
code stable, et l'utilisateur reçoit un message clair avec une référence.

## 1. Périmètre

### Inclus

- Contexte Audit (`App\Domain\Audit`) : table `audit_events`, modèle, `AuditLog`, `Redactor`
- Append-only garanti en base, RLS
- Corrélation : middleware, propagation aux jobs et aux logs, `causation_id`
- Logs JSON structurés avec rédaction
- Erreurs métier typées, rendu HTTP JSON (§137), page d'erreur et toast Livewire
- Audit des actions de P01 et des événements d'authentification
- UI d'audit owner / admin
- Seeds d'audit pour les espaces de démonstration

### Reporté

| Sujet | Passe |
|:--|:--|
| Lecture de l'audit plateforme (événements sans tenant) par les opérateurs | P06 |
| Rétention de l'audit et pseudonymisation des acteurs supprimés | P36 |
| Expédition des logs, alertes | P39 |
| Source `api` et `ai` | P34, P35 |

## 2. Décisions figées

| Sujet | Décision |
|:--|:--|
| Owner | Contexte `Audit` ; table `audit_events` déclarée dans `config/domains.php` |
| Identifiant | ULID ; pas de `updated_at` ; `occurred_at` en `timestampTz` |
| Acteur | `actor_id` (users.id) sans clé étrangère, pour qu'aucune cascade ne modifie l'audit |
| Tenant | `tenant_id` nullable : `null` = événement plateforme (authentification) |
| Append-only | Trigger `BEFORE UPDATE OR DELETE` et `BEFORE TRUNCATE` levant `AUDIT_APPEND_ONLY` pour tous les rôles, plus `REVOKE UPDATE, DELETE, TRUNCATE` pour `manecms_app` |
| Tentative de modification | Rapportée au niveau `critical` par le gestionnaire d'exceptions |
| RLS | Lecture et écriture limitées au tenant courant ; insertion d'un événement plateforme (`tenant_id` nul) autorisée, jamais sa lecture |
| Écriture | `AuditLog::record()` utilise la connexion courante : il écrit dans la transaction de l'action |
| Rédaction | Clés correspondant à `password`, `secret`, `token`, `api_key`, `authorization`, `cookie`, `recovery_codes` remplacées par `[redacted]`, récursivement, dans l'audit et dans les logs |
| Corrélation | `X-Correlation-ID` accepté s'il respecte `^[A-Za-z0-9-]{8,64}$`, sinon ULID généré ; renvoyé dans la réponse ; `request_id` ULID par requête et par job |
| Propagation | Façade `Context` : `correlation_id`, `request_id`, `source`, `tenant_id`, `user_id` ; un job reçoit la corrélation de son émetteur, `causation_id` = `request_id` de l'émetteur |
| Source | `http` (middleware), `queue` (job), `cli` par défaut |
| Logs | Canal `json` (Monolog `JsonFormatter`) ajouté à la pile ; processeur de rédaction sur les canaux `single` et `json` |
| Erreurs | Classe abstraite `DomainError` : `code` stable, `status` HTTP, `retryable` ; `TenantContextRequired` en hérite |
| Rendu | JSON §137 si la requête attend du JSON ; sinon page `errors.domain` avec message, possibilité de réessayer et référence ; dans Livewire, toast d'erreur avec la référence |
| Audit auth | Connexion réussie, échec, déconnexion, mot de passe changé, 2FA activée, désactivée, échouée, codes régénérés, compte supprimé |
| UI | `/w/{workspace}/audit` (`audit.index`), owner et admin ; filtres action et corrélation ; détail avant / après |

## 3. Schéma `audit_events`

| Colonne | Type | Contraintes |
|:--|:--|:--|
| `id` | ulid | PK |
| `tenant_id` | ulid | nullable, FK `tenants.id` restrict |
| `actor_id` | bigint | nullable |
| `action` | string(100) | not null, `contexte.ressource.verbe` |
| `subject_type` | string(100) | nullable |
| `subject_id` | string(64) | nullable |
| `before` | jsonb | nullable |
| `after` | jsonb | nullable |
| `reason` | text | nullable |
| `source` | string(10) | not null, check `http`, `cli`, `queue`, `api`, `ai` |
| `ip` | string(45) | nullable |
| `user_agent` | string(255) | nullable |
| `correlation_id` | string(64) | not null |
| `causation_id` | string(64) | nullable |
| `occurred_at` | timestampTz | not null |

Index : `(tenant_id, occurred_at)`, `(correlation_id)`, `(subject_type, subject_id)`.

## 4. Actions auditées

| Action | Événement |
|:--|:--|
| `CreateTenant` | `tenancy.tenant.created`, `tenancy.workspace.created` |
| `RenameTenant` | `tenancy.tenant.renamed` (avant / après) |
| `CreateWorkspace` | `tenancy.workspace.created` |
| `RenameWorkspace` | `tenancy.workspace.renamed` (avant / après) |
| `ArchiveWorkspace` | `tenancy.workspace.archived` |
| `DeleteUser` | `identity.account.deleted` |
| Login, Failed, Logout | `identity.login.succeeded`, `identity.login.failed`, `identity.logout` |
| Changement du mot de passe | `identity.password.changed` (observateur du modèle `User`) |
| Fortify 2FA | `identity.two_factor.enabled`, `.disabled`, `.failed`, `.recovery_codes_regenerated` |

## 5. Tests obligatoires

- `UPDATE` et `DELETE` refusés par la base, en rôle applicatif et en propriétaire
- rollback de l'action : aucun événement écrit
- secrets absents de l'audit et des logs
- corrélation identique de la requête au job, à l'audit et à la réponse ; en-tête invalide remplacé
- audit du tenant B invisible depuis A ; événements plateforme illisibles
- événements d'authentification enregistrés
- erreur métier : JSON §137, page avec référence, toast Livewire
- UI : owner et admin voient l'audit, member reçoit 403, filtres, avant / après
- acceptance : un admin renomme un workspace, l'owner voit qui, quoi, quand, avant, après, corrélation

## 6. Rejet

- une ligne d'audit peut être modifiée ou supprimée par un rôle de l'application ;
- un audit est écrit hors de la transaction de son action ;
- un secret apparaît dans l'audit ou dans un log ;
- une erreur métier atteint l'utilisateur sans code ni référence ;
- un gate est rouge, en local ou en CI.
