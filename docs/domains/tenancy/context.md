# Contexte Tenancy

> Contrat de domaine (cahier §12), version P05. Décisions : `docs/adr/0002-tenancy-model.md`,
> `docs/adr/0003-authorization-policy-engine.md`.

## Mission

Délimiter l'espace de travail de chaque client, gérer qui en fait partie et garantir qu'aucun
utilisateur ne lit ni ne modifie les données d'un tenant dont il n'est pas membre.

## Périmètre

- Tenants, workspaces et appartenances.
- Contexte de requête : tenant, workspace et appartenance courants.
- Onboarding, création, renommage, archivage et bascule de workspace.
- Invitations, changement de rôle, restriction de workspaces, retrait et départ des membres.
- Transfert de propriété, archivage du tenant, exigence MFA du tenant.

Hors périmètre : décision d'autorisation et rôles (contexte Authorization), RLS (P02),
audit (P03), structure organisationnelle (P13).

## Concepts possédés

| Concept | Rôle |
|:--|:--|
| `Tenant` | Compte client et frontière d'isolation ; `require_mfa`, `archived_at` |
| `Workspace` | Espace de travail d'un tenant ; un tenant en possède au moins un actif |
| `TenantMember` | Appartenance d'un user à un tenant, avec un `role_id` et `is_owner` (calculé) |
| `TenantInvitation` | Invitation par email ; seul le hash SHA-256 du jeton est stocké |
| `TenantContext` | Tenant, workspace et appartenance de la requête courante |
| `TenancyPermission` | Permissions déclarées par le contexte (voir Authorization) |

## Tables possédées

`tenants`, `workspaces`, `tenant_members`, `tenant_member_workspaces`, `tenant_invitations`.

## Invariants

| Invariant | Protection |
|:--|:--|
| Un tenant a au plus un owner | `is_owner` posé par trigger depuis le rôle système + index unique partiel `tenant_members_single_owner` |
| Le rôle d'un membre appartient à son tenant | FK composite `(tenant_id, role_id)` |
| Une appartenance par user et tenant | unique `(tenant_id, user_id)` |
| Nom de workspace unique par tenant, sans casse, archivés compris | index unique `(tenant_id, lower(name))` |
| Le dernier workspace d'une appartenance est dans son tenant | FK composite `(tenant_id, last_workspace_id)` |
| Une restriction vise un workspace du même tenant | FK composites de `tenant_member_workspaces` |
| Une invitation en attente par email et tenant | index unique partiel `tenant_invitations_one_pending` |
| Email d'invitation normalisé ; une seule issue (acceptée ou révoquée) | `CHECK` |
| Jeton d'invitation jamais stocké en clair | `token_hash` unique ; lecture par `tenant_invitation_by_token` (SECURITY DEFINER) |
| Un tenant garde au moins un workspace actif | `ArchiveWorkspace`, verrou sur le tenant |
| Un owner d'un tenant actif ne supprime pas son compte | `App\Domain\Identity\Actions\DeleteUser` |
| Un transfert à la fois | verrou `FOR UPDATE` sur le tenant, owner relu sous verrou |
| Aucune lecture sans contexte installé | `TenantContext` lève `TenantContextRequired` |

## Commandes

`CreateTenant`, `RenameTenant`, `CreateWorkspace`, `RenameWorkspace`, `ArchiveWorkspace`,
`InviteMember`, `RevokeInvitation`, `AcceptInvitation`, `UpdateMemberRole`,
`RestrictMemberWorkspaces`, `RemoveMember`, `LeaveTenant`, `TransferOwnership`, `ArchiveTenant`,
`UpdateTenantSecurity`.

## Requêtes

`ResolveEntryWorkspace` (workspace d'atterrissage), scope `Workspace::accessibleBy()`
(tenant actif, restriction appliquée), `AcceptInvitation::find()`.

## Permissions

Toutes les décisions passent par le Policy Engine (`docs/domains/authorization/context.md`).

| Action | owner | admin | member | non-membre |
|:--|:--|:--|:--|:--|
| Voir un workspace autorisé du tenant | oui | oui | oui | 404 |
| Voir un workspace hors restriction, un tenant archivé | — | 404 | 404 | 404 |
| Voir les membres | oui | oui | oui | 404 |
| Inviter, changer un rôle, restreindre, retirer | oui | oui (pas l'owner ni un admin) | 403 | 404 |
| Gérer les rôles | oui | oui | 403 | 404 |
| Créer, renommer, archiver un workspace ; renommer le tenant | oui | oui | 403 | 404 |
| Sécurité, transfert, archivage du tenant | oui | 403 | 403 | 404 |
| Quitter le tenant | non (transfert d'abord) | oui | oui | — |

## Événements

Audit : `tenancy.invitation.created|expired|revoked|accepted`, `tenancy.member.joined|role_changed|restricted|removed|left`,
`tenancy.tenant.ownership_transferred|archived|security_updated`, plus ceux de P01.
Email : `TenantInvitationNotification` (l'outbox arrive en P10).

## Liens avec les autres contextes

- **Authorization** : rôles, permissions et Policy Engine ; `EnsureTenantMfa` suit `ResolveWorkspace`.
- **Identity** : `ConfirmIdentity` (mot de passe + code 2FA) pour le transfert et l'archivage.
- **Audit** : chaque action écrit son événement dans sa transaction.

## Tests

`tests/Feature/Tenancy/*`, `tests/Feature/Authorization/*`, `tests/Feature/Settings/DeleteAccountTest.php`,
`tests/Architecture/DomainTest.php`.
