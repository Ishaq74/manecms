# Contexte Authorization

> Contrat de domaine (cahier §12), version P05. Décisions : `docs/adr/0003-authorization-policy-engine.md`.

## Mission

Décider, en un seul endroit, si un membre peut faire une action dans son tenant, et laisser une
trace de chaque refus.

## Périmètre

- Catalogue des permissions déclarées par les contextes, synchronisé en base.
- Rôles par tenant (système et personnalisés) et leurs permissions.
- Policy Engine : décisions typées, capabilities, garde-fous d'escalade, SoD, approbation, MFA.
- Exigence MFA par tenant pour les membres privilégiés (`EnsureTenantMfa`).

Hors périmètre : workflow d'approbation (P15), rôle plateforme et back-office opérateur (P06),
ABAC sur attributs métier (passes métier).

## Concepts possédés

| Concept | Rôle |
|:--|:--|
| `Permission` | Contrat des énumérations de permissions de chaque contexte |
| `Capability` | `safe`, `guarded`, `privileged`, `code_only`, `immutable` (§30) |
| `Role` | Rôle d'un tenant ; `system_key` pour owner, admin, member |
| `SystemRole` | Nom en code des trois rôles système |
| `Decision` | `ALLOW`, `ALLOW_WITH_CONSTRAINTS`, `DENY` + `DenialReason`, `REQUIRE_APPROVAL` |
| `PolicyEngine` | `decide()` sans effet, `authorize()` qui audite et lève |
| `PermissionRegistry` | Permissions déclarées dans `config/authorization.php` |

## Tables possédées

`permissions` (globale, lecture seule pour l'application), `roles`, `role_permissions` (RLS).

## Invariants

| Invariant | Protection |
|:--|:--|
| Clé de permission `<contexte>.<ressource>.<action>` | `CHECK permissions_key_format` |
| Capability connue | `CHECK permissions_capability_known` |
| Le catalogue n'est modifié que par la synchronisation | `REVOKE INSERT, UPDATE, DELETE` au rôle applicatif |
| Rôles système non renommables ni supprimables | trigger `roles_protect_system` |
| Clé et nom de rôle uniques par tenant (nom sans casse) | unique `(tenant_id, key)`, index `(tenant_id, lower(name))` |
| Un rôle ne reçoit que des permissions de son catalogue | FK `role_permissions.permission_key` |
| Une attribution reste dans le tenant du rôle | FK composite `(tenant_id, role_id)` |
| Rôle personnalisé sans permission privilégiée | `ValidateCustomRole`, `SyncPermissions` |
| Un seul owner par tenant | `tenant_members.is_owner` par trigger + index unique partiel |

## Commandes

`CreateRole`, `UpdateRole`, `DeleteRole`, `SyncPermissions`, `ProvisionSystemRoles`.
Console : `authorization:sync-permissions`.

## Requêtes

`PolicyEngine::decide()`, `PolicyEngine::requiresMfaEnrolment()`, abilities Gate (une par clé).

## Ordre d'évaluation

1. permission inconnue → `DENY unknown_permission`
2. pas de contexte → `DENY no_tenant_context` ; autre utilisateur → `DENY actor_mismatch`
3. tenant archivé → `DENY tenant_archived`
4. workspace restreint → `DENY workspace_restricted`
5. capability `code_only` / `immutable` → `DENY not_grantable`
6. RBAC → `DENY missing_permission`
7. `privileged` sans 2FA dans un tenant qui l'exige → `DENY mfa_required`
8. escalade : `foreign_target`, `owner_protected`, `self_target`, `peer_admin`,
   `owner_role_not_assignable`, `role_exceeds_actor`
9. SoD → `DENY segregation_of_duties`
10. approbation → `REQUIRE_APPROVAL`
11. `safe` → `ALLOW` ; sinon `ALLOW_WITH_CONSTRAINTS [audit]`

## Permissions déclarées

| Clé | Capability | Rôles système |
|:--|:--|:--|
| `tenancy.member.view` | safe | owner, admin, member |
| `tenancy.member.invite`, `.update-role`, `.restrict`, `.remove` | guarded | owner, admin |
| `tenancy.role.manage` | privileged | owner, admin |
| `tenancy.workspace.create`, `.update`, `.archive` | guarded | owner, admin |
| `tenancy.tenant.update` | guarded | owner, admin |
| `tenancy.tenant.security`, `.transfer`, `.archive` | privileged | owner |
| `audit.event.view` | guarded | owner, admin |

## Événements

Audit : `authorization.denied`, `authorization.role.created|updated|deleted`.

## Liens avec les autres contextes

- **Tenancy** : fournit `TenantContext` et déclare `TenancyPermission` ; ses actions appellent
  `PolicyEngine::authorize()`.
- **Audit** : déclare `AuditPermission` ; reçoit les refus.
- **Identity** : la 2FA de l'utilisateur alimente l'étape MFA.

## Tests

`tests/Feature/Authorization/*`, `tests/Feature/Tenancy/DatabaseInvariantsTest.php`.
