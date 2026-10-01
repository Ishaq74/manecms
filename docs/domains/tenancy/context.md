# Contexte Tenancy

> Contrat de domaine (cahier §12), version P01. Décisions : `docs/adr/0002-tenancy-model.md`.

## Mission

Délimiter l'espace de travail de chaque client et garantir qu'aucun utilisateur ne lit ni ne
modifie les données d'un tenant dont il n'est pas membre.

## Périmètre

- Tenants, workspaces et appartenances.
- Contexte de requête : tenant, workspace et appartenance courants.
- Onboarding, création, renommage, archivage et bascule de workspace.

Hors périmètre : invitations, rôles personnalisés et transfert de propriété (P05), RLS (P02),
audit (P03), structure organisationnelle (P13).

## Concepts possédés

| Concept | Rôle |
|:--|:--|
| `Tenant` | Compte client et frontière d'isolation |
| `Workspace` | Espace de travail d'un tenant ; un tenant en possède au moins un actif |
| `TenantMember` | Appartenance d'un user à un tenant, avec un rôle `owner`, `admin` ou `member` |
| `TenantContext` | Tenant, workspace et appartenance de la requête courante |

## Tables possédées

`tenants`, `workspaces`, `tenant_members`.

## Invariants

| Invariant | Protection |
|:--|:--|
| Un tenant a au plus un owner | index unique partiel `tenant_members_one_owner_per_tenant` |
| Une appartenance par user et tenant | unique `(tenant_id, user_id)` |
| Nom de workspace unique par tenant, sans casse, archivés compris | index unique `(tenant_id, lower(name))` |
| Le dernier workspace d'une appartenance est dans son tenant | FK composite `(tenant_id, last_workspace_id)` |
| Un tenant garde au moins un workspace actif | `ArchiveWorkspace`, verrou sur les workspaces actifs |
| Un owner ne supprime pas son compte | `App\Domain\Identity\Actions\DeleteUser` |
| Aucune lecture sans contexte installé | `TenantContext` lève `TenantContextRequired` |

## Commandes

`CreateTenant`, `RenameTenant`, `CreateWorkspace`, `RenameWorkspace`, `ArchiveWorkspace`.

## Requêtes

`ResolveEntryWorkspace` : workspace d'atterrissage après connexion.

## Permissions

| Action | owner | admin | member | non-membre |
|:--|:--|:--|:--|:--|
| Voir un workspace du tenant | oui | oui | oui | 404 |
| Créer, renommer, archiver un workspace | oui | oui | 403 | 404 |
| Renommer le tenant | oui | oui | 403 | 404 |

## Événements

Aucun en P01. Les événements de tenancy seront publiés via l'outbox à partir de P10.

## Tests

`tests/Feature/Tenancy/*`, `tests/Feature/Settings/DeleteAccountTest.php`,
`tests/Architecture/DomainTest.php`.
