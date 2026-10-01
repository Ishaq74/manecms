# ADR 0002 — Modèle de tenancy

- **Statut** : accepté
- **Date** : 2026-10-02
- **Passe** : P01
- **Cahier** : §0.1 (D1), §15.1, §16–§16.4, §20.1, §494

## Contexte

ManeCMS est un SaaS multi-tenant (D1). Chaque client doit être isolé, un utilisateur peut
travailler pour plusieurs clients, et un client peut cloisonner son activité en plusieurs
espaces de travail.

## Problème

Quelles entités porter, comment les relier aux utilisateurs, et comment chaque requête
connaît-elle son tenant ?

## Options

1. **Tenant unique par utilisateur** — simple, mais exclut les agences et les groupes.
2. **Workspace en session** — un seul contexte par navigateur, des onglets qui se marchent
   dessus, un contexte implicite difficile à tester.
3. **Tenant → workspaces, appartenance au tenant, contexte porté par l'URL** — plusieurs
   tenants par utilisateur, un contexte explicite par requête, vérifié à chaque appel.

## Décision

Option 3.

- `Tenant` est la frontière d'isolation ; `Workspace` appartient à un seul tenant ;
  `TenantMember` relie un user à un tenant avec le rôle `owner`, `admin` ou `member`.
- Tout membre accède à tous les workspaces actifs de son tenant jusqu'à P05.
- Les pages tenant-scoped vivent sous `/w/{workspace}` ; le middleware `ResolveWorkspace`
  installe `TenantContext` et le rejoue pour les requêtes Livewire.
- Un non-membre reçoit 404, jamais 403 ; un membre sans le rôle requis reçoit 403.
- Identifiants ULID ; `users.id` reste entier.
- Un workspace s'archive, il ne se supprime pas ; le dernier workspace actif ne s'archive pas.
- Le dernier workspace utilisé est mémorisé par appartenance (`last_workspace_id`).
- Les invariants sont garantis en base : index unique partiel pour l'owner unique, index sur
  `lower(name)`, clé étrangère composite pour le dernier workspace.

## Conséquences

- Toute nouvelle table tenant-scoped porte `tenant_id` et lit le tenant depuis
  `TenantContext`, jamais depuis l'input.
- P02 ajoute la RLS PostgreSQL sur ces trois tables et propage le contexte aux jobs et
  commandes.
- Le transfert de propriété (P05) est requis pour qu'un owner puisse supprimer son compte.
