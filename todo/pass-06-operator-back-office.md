# P06 — Back-office opérateur

> Spécification d'implémentation. Toute décision ci-dessous est figée pour cette passe.
> Référence : `todo/todo.md` §0.1 (D1), §20.2, §269, §475–§479.
> Feuille de route : `todo/roadmap.md`. Prérequis : P05 acceptée.

## 0. Résultat attendu

Un opérateur de l'éditeur, désigné en ligne de commande, ouvre `/platform` avec sa 2FA et son
mot de passe. Il y voit les tenants et la santé du système, suspend ou réactive un tenant, et se
connecte temporairement à la place d'un utilisateur pour l'aider, sans pouvoir rien modifier.
Chacune de ses actions est auditée et consultable depuis le back-office.

## 1. Périmètre

### Inclus

- Rôle plateforme `operator`, attribué et retiré par `platform:grant-operator` / `platform:revoke-operator`
- Zone `/platform` : layout propre, 404 pour un non-opérateur, 2FA obligatoire, reconfirmation du mot de passe
- Liste des tenants (owner, création, statut, membres, workspaces), fiche tenant, suspension, réactivation
- Tenant suspendu : message explicite sur HTTP et Livewire, jobs refusés, Policy Engine `DENY`
- Impersonation : 30 minutes au plus, raison obligatoire, bannière, lecture seule, jamais sur un opérateur
- Santé : base, file `database` (en attente, plus ancien), jobs en échec, disque
- Journal d'audit plateforme consultable (reporté de P03)

### Reporté

| Sujet | Passe |
|:--|:--|
| Break-glass (§269) et mode incident (§479) | P39 |
| Outils de support métier (§476 : parties, cas, intégrations) | passes métier |
| Export support (§478) | P34 |
| Redis, recherche, paiements, sauvegardes dans la santé (§475) | passes concernées |

## 2. Décisions figées

| Sujet | Décision |
|:--|:--|
| Rôle plateforme | `users.platform_role` (énumération `PlatformRole::Operator`) ; non assignable par l'application : un trigger refuse tout changement qui ne vient pas du rôle propriétaire ; les commandes tournent en propriétaire et sont auditées |
| Lecture inter-tenants (à trancher) | Vues agrégées possédées par le propriétaire (`platform_tenants`, `platform_tenant_members`, `platform_audit_events`), chacune filtrée sur « l'utilisateur courant (`app.user_id`) est opérateur ». Pas de connexion `BYPASSRLS` : l'application ne lit que des agrégats et seulement pour un opérateur |
| Écriture inter-tenants | Fonction `SECURITY DEFINER` `platform_set_tenant_suspension(tenant, reason)` qui revérifie l'opérateur ; l'audit est un événement plateforme |
| Accès `/platform` | `auth`, `verified`, `EnsurePlatformOperator` (404), `EnsureOperatorMfa` (vers la sécurité), `password.confirm` ; rejoués pour Livewire |
| Suspension | `tenants.suspended_at`, `suspension_reason` (raison obligatoire) ; `ResolveWorkspace` lève `TENANT_SUSPENDED` (403) avec la raison ; Policy Engine `DENY tenant_suspended` ; `RunInTenantContext` refuse le job |
| Impersonation | Table `impersonations` (opérateur, utilisateur, raison, début, fin prévue, fin réelle). Bascule de l'utilisateur de session sans événement `Login` ; régénération de session au début et à la fin ; expiration contrôlée à chaque requête ; fin explicite par la bannière |
| Lecture seule | Pendant l'impersonation : requêtes HTTP non `GET` refusées sauf `logout` et fin d'impersonation ; appels de méthodes Livewire refusés par défaut sauf navigation de table et méthodes magiques (`$set`, `$refresh`…) ; erreur `IMPERSONATION_READ_ONLY` (403) |
| Cibles interdites | Un opérateur, soi-même, un compte non vérifié |
| Santé | `PlatformHealth` : latence SQL, jobs en attente et âge du plus ancien, jobs en échec, espace disque libre du stockage, seuils `ok` / `warning` / `critical` |
| Audit | `platform.operator.granted|revoked`, `platform.tenant.suspended|reactivated`, `platform.impersonation.started|ended|expired`, refus `platform.impersonation.blocked` |

## 3. Tests

- Non-opérateur, invité : 404 sur toutes les pages `/platform` ; opérateur sans 2FA redirigé.
- Le rôle plateforme ne change que par la commande (trigger testé).
- Les vues plateforme ne renvoient rien à un non-opérateur, même en SQL direct.
- Suspension : HTTP, Livewire, job, Policy Engine ; réactivation ; raison obligatoire.
- Impersonation : raison obligatoire, durée bornée, opérateur et soi-même refusés, expirée inutilisable,
  action Livewire et POST refusés, fin qui rend la session à l'opérateur, aucun email « nouvel appareil ».
- Audit de chaque action opérateur.

## 4. Acceptance

GIVEN un opérateur en impersonation, WHEN il tente une action qui modifie des données, THEN elle
est refusée et la tentative est auditée ; WHEN les 30 minutes sont écoulées, THEN la session
revient à l'opérateur.
