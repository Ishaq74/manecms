# ADR 0003 — Autorisation : RBAC en base et Policy Engine unique

- **Statut** : accepté
- **Date** : 2026-10-02
- **Passe** : P05
- **Cahier** : §20, §21, §28, §28.1, §29, §30

## Contexte

Jusqu'à P04, le rôle d'un membre était une énumération `owner`, `admin`, `member` stockée
dans `tenant_members.role`, et chaque policy comparait ce rôle. Le cahier demande des rôles
personnalisés par tenant, des capabilities (§30), la ségrégation des tâches, l'approbation et
des décisions typées et auditées (§28.1).

## Problème

Où vivent les permissions, comment un rôle les reçoit, et qui décide qu'une action est
permise, sans laisser de comparaison de rôle se disperser dans les policies et les vues ?

## Options

1. **Garder l'énumération** et ajouter des cas : aucun rôle personnalisé, comparaisons
   partout.
2. **Permissions en base seulement** : modifiables à chaud, mais le code ne sait plus quelles
   permissions existent et une faute de frappe devient un refus silencieux.
3. **Permissions déclarées en code, copiées en base, rôles en base, un Policy Engine** : le
   code est la source de vérité, la base porte les attributions et les contraintes.

## Décision

Option 3.

- Chaque contexte déclare ses permissions dans une énumération qui implémente
  `App\Domain\Authorization\Permission` : clé `<contexte>.<ressource>.<action>`, capability,
  rôles système qui la reçoivent, SoD, approbation. Le registre est `config/authorization.php`.
- `authorization:sync-permissions` copie le catalogue dans `permissions` et réattribue les rôles
  système de chaque tenant. Elle tourne aussi après chaque `migrate` (événement
  `MigrationsEnded`). Le rôle applicatif ne peut que lire `permissions`.
- `roles` par tenant : trois rôles système (`system_key`), immuables par trigger, et des rôles
  personnalisés qui ne reçoivent que des permissions `safe` ou `guarded`.
- `tenant_members.role_id` (FK composite vers le même tenant) ; `is_owner` est calculé par
  trigger depuis le rôle, et l'index unique partiel `(tenant_id) WHERE is_owner` garantit un
  owner unique. Migration en deux temps : expand (`role_id` + backfill) puis contract (suppression
  de `role`).
- `PolicyEngine::decide()` rend `ALLOW`, `ALLOW_WITH_CONSTRAINTS`, `DENY` (raison stable) ou
  `REQUIRE_APPROVAL` selon un ordre fixe : permission inconnue, contexte, acteur, tenant archivé,
  restriction de workspace, capability, RBAC, MFA, garde-fous d'escalade, SoD, approbation.
- `PolicyEngine::authorize()` audite chaque refus (`authorization.denied`) avant de lever
  `AuthorizationDenied` (403) ou `ApprovalRequired` (409). Les actions l'appellent avant
  d'ouvrir leur transaction, pour que l'audit du refus survive.
- Les policies Laravel et les `@can` des vues délèguent au moteur : chaque clé de permission
  est une ability Gate. Un test interdit toute comparaison de rôle dans les policies, vues et
  composants.
- Garde-fous d'escalade : personne ne devient owner hors transfert, ne modifie son propre rôle
  ou celui de l'owner ; un admin ne gère pas un autre admin ; sous l'owner, on n'attribue pas un
  rôle plus riche que le sien.

## Conséquences

- Une nouvelle permission = un cas d'énumération ; aucune migration.
- Les approbations réelles arrivent en P15 ; d'ici là `REQUIRE_APPROVAL` refuse l'action.
- Le transfert de propriété et l'archivage du tenant permettent à un owner de supprimer son
  compte (§20.1).
- Les tests qui créent des membres passent par les états de factory (`owner()`, `admin()`,
  `member()`, `withRole()`), jamais par une colonne de rôle.
