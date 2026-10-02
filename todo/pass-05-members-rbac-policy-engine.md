# P05 — Membres, invitations, RBAC, Policy Engine

> Spécification d'implémentation. Toute décision ci-dessous est figée pour cette passe.
> Référence : `todo/todo.md` §16.3, §20, §21, §28, §28.1, §29, §30, §378.
> Feuille de route : `todo/roadmap.md`. Prérequis : P03 et P04 acceptés.

## 0. Résultat attendu

Un owner invite des personnes par email, leur attribue un rôle et, au besoin, les restreint à
certains workspaces. Chaque décision d'accès passe par un Policy Engine unique qui combine
rôles, capabilities, ségrégation des tâches et restriction de workspace, et rend une décision
typée. Toute tentative refusée sur une action est auditée. La propriété se transfère de façon
atomique, le tenant s'archive, les sessions se révoquent et la MFA peut être exigée.

## 1. Périmètre

### Inclus

- Contexte `Authorization` : permissions déclarées en code, synchronisation, rôles par tenant,
  Policy Engine, décisions, capabilities, SoD
- Invitations : création, email, acceptation, révocation, expiration
- Membres : liste, changement de rôle, restriction de workspaces, retrait, départ volontaire
- Transfert de propriété, archivage du tenant, exigence MFA par tenant
- Sessions et appareils (contexte `Identity`) : liste, révocation, alerte de nouvelle connexion
- Écrans ManeUI : membres, invitations, rôles, sécurité de l'espace, sessions
- Seeds, traductions `fr` et `en`, contrats de contexte, ADR 0003

### Reporté

| Sujet | Passe |
|:--|:--|
| Approbations réelles (`REQUIRE_APPROVAL` répond `APPROVAL_REQUIRED`) | P15 |
| ABAC sur attributs métier riches (montants, statuts) | passes métier |
| Rôle plateforme et back-office opérateur (§20.2) | P06 |
| Envoi des emails par l'outbox | P10 |
| Pseudonymisation de l'acteur d'un compte supprimé | P36 |

## 2. Décisions figées

| Sujet | Décision |
|:--|:--|
| Contexte owner | `Authorization` : `permissions`, `roles`, `role_permissions`. `Tenancy` : `tenant_invitations`, `tenant_member_workspaces`. `Identity` : `user_devices` |
| Permission | Enum par contexte implémentant `App\Domain\Authorization\Permission` : clé `<context>.<resource>.<action>`, capability (§30), rôles système qui la reçoivent, SoD, approbation. Registre : `config/authorization.php` |
| Synchronisation | `authorization:sync-permissions` : upsert de `permissions`, suppression des clés retirées, réattribution aux rôles système de chaque tenant. Lancée après `migrate` (déploiement) et dans les seeds |
| Rôle | `roles` par tenant : `key` unique par tenant, nom unique (insensible à la casse), `system_key` (`owner`, `admin`, `member`) pour les rôles système non supprimables, non modifiables |
| Owner unique | `tenant_members.is_owner`, posé par trigger depuis le rôle système `owner`, index unique partiel `(tenant_id) WHERE is_owner` ; FK composite `(tenant_id, role_id) → roles(tenant_id, id)` |
| Migration du rôle | Expand : `role_id` + backfill depuis `role` + rôles système de chaque tenant. Contract : suppression de `role` (migration distincte) |
| Capabilities (§30) | `SAFE_CONFIG`, `GUARDED_CONFIG` : attribuables aux rôles personnalisés. `PRIVILEGED_CONFIG` : réservée aux rôles système owner et admin, exige la MFA si l'espace l'impose. `CODE_ONLY`, `IMMUTABLE` : jamais accordées à un utilisateur |
| Décisions | `ALLOW`, `ALLOW_WITH_CONSTRAINTS` (contraintes nommées, ex. `audit`), `DENY` (raison stable), `REQUIRE_APPROVAL` |
| Ordre d'évaluation | permission inconnue → `DENY` ; tenant archivé → `DENY` ; workspace interdit → `DENY` ; capability `CODE_ONLY`/`IMMUTABLE` → `DENY` ; RBAC → `DENY` ; MFA requise absente → `DENY` ; SoD → `DENY` ; approbation → `REQUIRE_APPROVAL` ; `GUARDED_CONFIG` → `ALLOW_WITH_CONSTRAINTS` ; sinon `ALLOW` |
| Policies Laravel | Délèguent au Policy Engine (`decide`) ; aucune comparaison de rôle hors du contexte `Authorization` (test d'architecture) |
| Actions | Appellent `PolicyEngine::authorize()` : `DENY` est audité (`authorization.denied`) puis lève `AuthorizationDenied` (403, `AUTHORIZATION_DENIED`) ; `REQUIRE_APPROVAL` lève `ApprovalRequired` (409, `APPROVAL_REQUIRED`) |
| Escalade | Personne ne devient owner hors transfert ; personne ne change son propre rôle ; le rôle de l'owner ne change pas ; on n'attribue pas un rôle système `owner` ; un admin ne gère pas un autre admin |
| Invitation | Email `lower(trim)`, jeton aléatoire de 48 caractères stocké en SHA-256, 7 jours, révocation, une seule en attente par email et tenant (index unique partiel). Une invitation expirée en attente est marquée expirée avant d'en créer une nouvelle |
| Lecture de l'invitation | Fonction `SECURITY DEFINER` `tenant_invitation_by_token(hash)` (le rôle applicatif ne contourne pas la RLS) |
| Acceptation | Compte connecté, email vérifié et identique ; échec si expirée, révoquée, déjà acceptée, déjà membre ou tenant archivé |
| Restriction de workspace | `tenant_member_workspaces` ; aucune ligne = tous les workspaces. L'owner n'est jamais restreint. Appliquée par `ResolveWorkspace` (404) sur HTTP et Livewire, par le switcher et par le Policy Engine |
| Transfert de propriété | Verrou `FOR UPDATE` sur le tenant, revérification de l'owner, mot de passe et code 2FA si active ; l'ancien owner devient admin |
| Archivage du tenant | `tenants.archived_at` par l'owner, nom recopié + mot de passe ; l'espace disparaît du switcher et répond 404 ; un owner peut alors supprimer son compte (§20.1) |
| MFA par tenant | `tenants.require_mfa` ; un owner ou admin sans 2FA confirmée est redirigé vers ses réglages de sécurité, les requêtes Livewire reçoivent `MFA_REQUIRED` |
| Sessions | Table `sessions` (driver `database`) : liste, révocation d'une session ou de toutes les autres (mot de passe), rotation du `remember_token` |
| Nouvelle connexion | `user_devices` (empreinte SHA-256 de l'agent et du réseau /24) ; email « Nouvelle connexion » quand un compte déjà connu se connecte depuis un appareil inconnu |

## 3. Permissions initiales

| Clé | Capability | Rôles système |
|:--|:--|:--|
| `tenancy.member.view` | SAFE | owner, admin, member |
| `tenancy.member.invite` | GUARDED | owner, admin |
| `tenancy.member.update-role` | GUARDED | owner, admin |
| `tenancy.member.restrict` | GUARDED | owner, admin |
| `tenancy.member.remove` | GUARDED | owner, admin |
| `tenancy.role.manage` | PRIVILEGED | owner, admin |
| `tenancy.workspace.create` | GUARDED | owner, admin |
| `tenancy.workspace.update` | GUARDED | owner, admin |
| `tenancy.workspace.archive` | GUARDED | owner, admin |
| `tenancy.tenant.update` | GUARDED | owner, admin |
| `tenancy.tenant.security` | PRIVILEGED | owner |
| `tenancy.tenant.transfer` | PRIVILEGED | owner |
| `tenancy.tenant.archive` | PRIVILEGED | owner |
| `audit.event.view` | GUARDED | owner, admin |

## 4. Tests

- Invitations : expirée, révoquée, réutilisée, autre email, déjà membre → refus ; une seule en attente.
- Escalade : admin → owner `DENY` audité ; member n'invite pas ; admin ne modifie pas l'owner ni un admin.
- Transfert concurrent : un seul aboutit (revérification sous verrou, index unique en dernier recours).
- Restriction : routes HTTP, requêtes Livewire, switcher, Policy Engine.
- MFA imposée ; session révoquée inutilisable.
- IDOR : identifiants de membres, d'invitations, de rôles et de sessions d'un autre tenant ou utilisateur.
- Policy Engine : chaque étape de l'ordre d'évaluation ; SoD et approbation via une permission de test.
- Base : unicité owner, invitation en attente, nom et clé de rôle, clé de permission ; montée depuis le schéma P04.
- Audit de chaque action.

## 5. Acceptance

GIVEN un admin, WHEN il tente de se promouvoir owner, THEN la décision est `DENY` et l'audit
contient la tentative.

## 6. Interdits

- un test de rôle par chaîne ou par enum dans une vue, un composant ou une policy ;
- un jeton d'invitation stocké en clair ;
- une lecture d'invitation qui contourne la RLS autrement que par la fonction dédiée ;
- un transfert de propriété en deux écritures non verrouillées.
