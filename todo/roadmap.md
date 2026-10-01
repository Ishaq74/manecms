# ManeCMS — Feuille de route intégrale

> Source : `todo/todo.md` (cahier des charges, lu intégralement le 2026-10-01).
> Décisions produit provisoires D1 à D7 : cahier §0.1. Trains de release : cahier §2.1.
> Ce document liste **toutes** les passes nécessaires pour atteindre la V1 absolue (§503).
> Chaque case cochée doit pouvoir être prouvée par un test, une contrainte en base ou un
> artefact vérifiable (§1 du cahier : « Verified »).

---

## 0. Mode d'emploi

1. Les passes s'exécutent dans l'ordre de leurs dépendances. Une passe ne démarre que lorsque
   toutes ses dépendances sont **acceptées**.
2. **Avant de coder une passe**, on écrit sa spécification détaillée `todo/pass-XX-<slug>.md`
   avec le gabarit de la section 2, et on tranche ses questions ouvertes. La passe 01 sert
   d'exemple : `todo/pass-01-tenancy-core.md`.
3. Le contenu d'une passe ci-dessous est son **périmètre contractuel** : la spec détaillée peut
   préciser, jamais retirer un livrable sans le reporter explicitement vers une autre passe.
4. Une passe est acceptée quand ses cases et la Definition of Done commune (section 1) sont
   cochées, gates verts.
5. Toute décision d'architecture prise dans une passe est reportée dans le cahier et dans un ADR.
6. Statuts du tableau de la section 3 : `à faire`, `spec prête`, `en cours`, `acceptée`.

Pourquoi les passes lointaines ne sont pas détaillées colonne par colonne : pour la plupart des
domaines, le cahier ne fixe que des listes de concepts. Détailler maintenant les obligerait à
inventer des règles métier qui dépendent des passes précédentes et de choix encore ouverts
(section 9). Le périmètre, les invariants et les preuves exigées sont en revanche figés ici.

---

## 1. Definition of Done commune (s'applique à chaque passe)

### Avant le code

- [ ] Spécification `todo/pass-XX-*.md` écrite selon le gabarit, questions ouvertes tranchées
- [ ] Contrat de contexte `docs/domains/<context>/context.md` créé ou mis à jour (§12)
- [ ] Liaisons inter-domaines documentées : owner des données, de la commande, de l'invariant, event, projection, policy, audit, retry (§348)
- [ ] Revue données faite pour chaque nouvelle colonne (§399)
- [ ] Toute nouvelle dépendance Composer ou npm approuvée explicitement par le product owner

### Données

- [ ] Chaque table a un contexte owner déclaré (§371) et respecte §15.1
- [ ] Toute table tenant-scoped porte `tenant_id`, RLS activée et forcée (§17.1), à partir de P02
- [ ] Invariants garantissables en base protégés par contrainte : unique, check, FK, exclusion, not null (§374, §498)
- [ ] Migrations créées par `make:migration`, aucune migration commitée modifiée, stratégie expand/contract (§198)
- [ ] `migrate:fresh` et montée depuis le schéma précédent testés (§227)

### Domaine et application

- [ ] Commandes transactionnelles, autorisées par policy (§28.1), erreurs typées (§263)
- [ ] Opérations sensibles auditées (§153), à partir de P03
- [ ] Effets inter-domaines via outbox et inbox (§122, §123), à partir de P10
- [ ] Logique métier hors Livewire (§279, §280)
- [ ] Stratégie de panne documentée : doublon, concurrence, timeout, rollback, retry (§320)

### Interface

- [ ] Composants ManeUI uniquement (§283), à partir de P04 ; aucune référence à Flux (§284)
- [ ] États obligatoires couverts : vide, chargement, erreur, refus, succès (§41, §464–§466)
- [ ] Contrats de formulaire et de table respectés (§281, §282)
- [ ] Textes traduits `fr` et `en` (D7) ; accessibilité clavier et lecteur d'écran (§43)

### Tests (selon le tier de la passe, §293–§296)

- [ ] Noms de tests = règles métier (§495), structure Given/When/Then pour les critiques (§496)
- [ ] Isolation tenant A ↛ B testée pour chaque nouvelle ressource (§19)
- [ ] Autorisation : refus testés pour chaque rôle non autorisé
- [ ] **VITAL** : chemins heureux, négatifs, concurrence, contrainte base, autorisation, rejeu (§499) ; cible de mutation atteinte (§213)
- [ ] **STANDARD** : feature + intégration, sécurité, navigateur pour les parcours critiques
- [ ] Type coverage 100 % (§209), PHPStan `max` sans erreur (§210)

### Clôture

- [ ] Gates verts : `herd php artisan migrate:fresh`, `herd php artisan test --compact`, `herd php bin/check.php --full`, `pnpm run build`, CI
- [ ] Critères d'acceptance de la passe démontrés (Given/When/Then + preuve)
- [ ] ADR rédigés, cahier et feuille de route mis à jour, cases cochées

---

## 2. Gabarit de spécification de passe

Copier dans `todo/pass-XX-<slug>.md` :

```text
# PXX — Titre
> Références cahier, prérequis, feuille de route.

0. Résultat attendu          (ce que l'utilisateur peut faire à la fin)
1. Périmètre                 (inclus / reporté avec passe cible)
2. Décisions figées          (tableau sujet → décision)
3. Schéma                    (tables, colonnes, types, contraintes, index, RLS)
4. Fichiers                  (arborescence exacte)
5. Routes                    (méthode, URI, nom, middleware)
6. Flux et contexte          (séquences, résolution du contexte)
7. Commandes et règles       (action, rôles, règles, transaction)
8. Matrice d'autorisation    (action × rôle)
9. UI                        (pages, composants, états)
10. Événements               (nom.version, producteur, consommateurs, payload)
11. Seeders et données       (seeds obligatoires, démo explicite)
12. Tests                    (fichiers et noms de tests)
13. Ordre d'implémentation   (étapes = commits, gates)
14. Rejet                    (conditions de refus de la passe)
```

---

## 3. Vue d'ensemble

| ID | Passe | Train | Tier | Dépend de | Statut |
|:--|:--|:--|:--|:--|:--|
| P00 | Mise en conformité du socle | V1.0 | STANDARD | — | en cours (CI à valider) |
| P01 | Tenancy core | V1.0 | VITAL | P00 | spec prête |
| P02 | Isolation PostgreSQL (RLS) | V1.0 | VITAL | P01 | à faire |
| P03 | Audit, corrélation, erreurs typées | V1.0 | VITAL | P02 | à faire |
| P04 | ManeUI : fondations | V1.0 | STANDARD | P01 | à faire |
| P05 | Membres, invitations, RBAC, Policy Engine | V1.0 | VITAL | P03, P04 | à faire |
| P06 | Back-office opérateur | V1.0 | STANDARD | P05 | à faire |
| P07 | Localisation et temps | V1.0 | STANDARD | P04 | à faire |
| P08 | Money et Quantity | V1.0 | VITAL | P04 | à faire |
| P09 | Configuration, settings, feature flags | V1.0 | STANDARD | P05 | à faire |
| P10 | Infrastructure d'événements | V1.0 | VITAL | P03 | à faire |
| P11 | Notifications et emails | V1.0 | STANDARD | P07, P10 | à faire |
| P12 | Party et MDM core | V1.0 | STANDARD | P05, P07, P10 | à faire |
| P13 | Organisation, entités légales, sites | V1.0 | STANDARD | P08, P09, P12 | à faire |
| P14 | Fichiers et médias | V1.0 | VITAL | P03, P04, P09 | à faire |
| P15 | Workflow : états, approbations, process managers | V1.0 | STANDARD | P05, P10, P11 | à faire |
| P16 | Numérotation et rendu documentaire | V1.0 | VITAL | P13, P14 | à faire |
| P17 | CMS core | V1.0 | VITAL | P14, P15 | à faire |
| P18 | Canaux, sites publics, SEO | V1.0 | STANDARD | P17 | à faire |
| P19 | Page builder | V1.0 | STANDARD | P18 | à faire |
| P20 | Blog et Knowledge | V1.0 | STANDARD | P18 | à faire |
| P21 | CRM | V1.0 | STANDARD | P12, P15, P18 | à faire |
| P22 | Catalogue et tarification de base | V1.0 | STANDARD | P08, P13 | à faire |
| P23 | Moteur fiscal et pack France | V1.0 | VITAL | P22 | à faire |
| P24 | Devis et contrats légers | V1.0 | STANDARD | P16, P21, P23 | à faire |
| P25 | Finance core (grand livre) | V1.0 | VITAL | P13, P15, P23 | à faire |
| P26 | Facturation client et facture électronique FR | V1.0 | VITAL | P24, P25 | à faire |
| P27 | Paiements | V1.0 | VITAL | P26 | à faire |
| P28 | Banque et rapprochement | V1.0 | VITAL | P27 | à faire |
| P29 | Projets, temps, facturation au temps | V1.0 | STANDARD | P24, P26 | à faire |
| P30 | Dépenses et fournisseurs (AP léger) | V1.0 | VITAL | P26, P28 | à faire |
| P31 | Portail client | V1.0 | STANDARD | P27, P29 | à faire |
| P32 | Recherche | V1.0 | STANDARD | P21, P26 | à faire |
| P33 | Analytics, rapports, tableaux de bord | V1.0 | STANDARD | P28, P29 | à faire |
| P34 | API v1, webhooks, import/export | V1.0 | STANDARD | P30 | à faire |
| P35 | Plateforme IA | V1.0 | VITAL | P32, P34 | à faire |
| P36 | Conformité RGPD core | V1.0 | VITAL | P30 | à faire |
| P37 | Business templates V1.0 | V1.0 | STANDARD | P31, P33 | à faire |
| P38 | Abonnement SaaS de la plateforme | V1.0 | VITAL | P27, P06 | à faire |
| P39 | Préparation production | V1.0 | VITAL | P35–P38 | à faire |
| **G1** | **Gate V1.0** | V1.0 | — | P00–P39 | — |
| P40 | Marketing, consentement, communication center | V1.x | STANDARD | G1 | à faire |
| P41 | Service et case management | V1.x | STANDARD | G1 | à faire |
| P42 | Collaboration et temps réel | V1.x | STANDARD | G1 | à faire |
| P43 | Community | V1.x | EXTENSIBLE | P42 | à faire |
| P44 | LMS | V1.x | EXTENSIBLE | P42 | à faire |
| P45 | DMS, contrats, e-signature, OCR | V1.x | VITAL | G1 | à faire |
| P46 | PIM, catalogues, promotions | V1.x | VITAL | G1 | à faire |
| P47 | Inventaire (stock ledger) | V1.x | VITAL | P46 | à faire |
| P48 | Commerce : panier, checkout, commandes, retours | V1.x | VITAL | P47 | à faire |
| P49 | Abonnements clients | V1.x | VITAL | P48 | à faire |
| P50 | WMS et portail entrepôt | V1.x | STANDARD | P48 | à faire |
| P51 | Achats et portail fournisseur | V1.x | VITAL | P47, P41 | à faire |
| P52 | Booking et ressources | V1.x | VITAL | G1 | à faire |
| P53 | Événements et billetterie | V1.x | VITAL | P52 | à faire |
| P54 | Adhésions et dons | V1.x | STANDARD | P49 | à faire |
| P55 | POS et mode offline | V1.x | VITAL | P48 | à faire |
| P56 | Marketplace et portail vendeur | V1.x | VITAL | P48 | à faire |
| P57 | Multi-devises, consolidation, intercompany | V1.x | VITAL | G1 | à faire |
| P58 | Immobilisations, maintenance, flotte | V1.x | STANDARD | P51 | à faire |
| P59 | Production, MRP, qualité | V1.x | VITAL | P51 | à faire |
| P60 | RH / HCM et portail employé | V1.x | VITAL | G1 | à faire |
| P61 | Paie | V1.x | VITAL | P60 | à faire |
| P62 | Moteur d'automatisation | V1.x | VITAL | G1 | à faire |
| P63 | Champs personnalisés et no-code | V1.x | STANDARD | P62 | à faire |
| P64 | Extensions et SDK | V1.x | VITAL | P63 | à faire |
| P65 | Portails partenaire et intervention terrain | V1.x | STANDARD | P55 | à faire |
| P66 | UX avancée, vues 360, espagnol et arabe | V1.x | STANDARD | G1 | à faire |
| P67 | Packs verticaux | V1.x | STANDARD | P52–P59 | à faire |
| P68 | Juridictions supplémentaires | V1.x | VITAL | P57, P61 | à faire |
| P69 | Excellence opérationnelle | V1.x | VITAL | P68 | à faire |
| **G2** | **Gate V1 absolue** | V1 | — | P00–P69 | — |

---

## 4. Train V1.0 — noyau universel + freelance / services France

### P00 — Mise en conformité du socle

`STANDARD` · Dépend : — · Cahier : §4.2, §200–§212, §284, §288–§292, §299.1, §371, §396, D7

**Objectif.** Aligner le socle existant sur le cahier avant tout code métier.

**Livrables**
- [x] `.env.example` : `CACHE_STORE=database`, `QUEUE_CONNECTION=database`, `FILESYSTEM_DISK=local`, `BROADCAST_CONNECTION=log` ; variables Redis, Meilisearch, S3, Reverb commentées « activées par leur passe » (§4.2)
- [x] `.env` local vérifié sur les mêmes valeurs (non commité)
- [x] `composer.json` : `name` et `description` du projet (`manecms/manecms`) au lieu du starter kit
- [x] Script Composer `dev` sans `php artisan serve` (le site est servi par Herd)
- [x] Base `manecms_testing` créée ; `phpunit.xml` passe à `pgsql` (§299.1) ; suite verte sur PostgreSQL
- [x] `APP_LOCALE=fr`, `APP_FALLBACK_LOCALE=en`, `APP_FAKER_LOCALE=fr_FR` ; `lang/fr.json` couvre auth, settings et shell existants
- [x] PHPStan `level: max` sans baseline ; erreurs corrigées (§210)
- [x] `bin/check.php` : type coverage avec `--min=100` (§209)
- [x] Suite `Architecture` déclarée dans `phpunit.xml` : presets Pest `php`, `security`, `laravel` ; aucune référence à Flux dans `composer.json`, `composer.lock` et les sources (§284)
- [x] Registre de propriété des tables `config/domains.php` + test : toute table de la base appartient à un contexte (§371)
- [x] Groupes Pest conventionnels (§292)
- [x] CI GitHub Actions : `composer validate`, `composer audit`, Pint, Rector dry-run, PHPStan, tests sur service PostgreSQL, type coverage, `pnpm run build` (§288, sous-ensemble V1.0)
- [x] `docs/adr/0000-template.md` (§287) et `docs/adr/0001-modular-monolith.md` (§286)
- [x] Modèle de PR `.github/pull_request_template.md` reprenant §396 à §399

**Tests et preuves**
- [x] Tests d'architecture verts ; un import Flux factice fait échouer la suite
- [ ] CI verte sur la branche principale
- [x] Test de registre : une table non déclarée fait échouer la suite

**Acceptance**
- [ ] GIVEN un clone propre sous Herd, sans Redis, Meilisearch, S3 ni Reverb démarrés, WHEN `composer setup` puis les gates sont lancés, THEN tout est vert.

---

### P01 — Tenancy core

`VITAL` · Dépend : P00 · Cahier : §15.1, §16–§16.4, §20.1 · Spec : **`todo/pass-01-tenancy-core.md`**

**Livrables** (détail dans la spec)
- [ ] Tables `tenants`, `workspaces`, `tenant_members` avec contraintes et index de la spec
- [ ] `TenantContext`, middleware `ResolveWorkspace`, exception `TenantContextRequired`
- [ ] Onboarding tenant + premier workspace ; création, renommage, archivage, bascule de workspace
- [ ] Policies, matrice d'autorisation, 404 pour les non-membres
- [ ] Blocage de suppression de compte pour un owner
- [ ] `context.md` Tenancy, ADR 0002 modèle de tenancy

**Acceptance**
- [ ] GIVEN deux tenants A et B, WHEN un membre de A tente d'ouvrir un workspace de B par URL ou Livewire, THEN il reçoit 404 et rien n'est modifié.

---

### P02 — Isolation PostgreSQL (RLS)

`VITAL` · Dépend : P01 · Cahier : §17, §17.1, §18, §19, §207, §494

**Objectif.** Rendre l'isolation tenant garantie par la base, pas seulement par l'application.

**Livrables**
- [ ] Rôles PostgreSQL : propriétaire (migrations), applicatif `NOSUPERUSER NOBYPASSRLS`, test ; procédure de provisioning Herd documentée
- [ ] Connexion dédiée aux migrations ; runtime sur le rôle applicatif ; privilèges par défaut
- [ ] Helper de migration activant `ENABLE` + `FORCE` RLS et les quatre policies (`SELECT`, `INSERT`, `UPDATE`, `DELETE`) avec `USING` / `WITH CHECK`
- [ ] RLS sur `tenants`, `workspaces`, `tenant_members` ; policy de pré-résolution sur `app.user_id`
- [ ] `TenantContext` installe et réinitialise `app.tenant_id` et `app.user_id`
- [ ] Contexte des jobs : middleware de job + `tenant_id` dans le payload ; job sans contexte en échec fermé
- [ ] Commandes Artisan métier avec option `--tenant` obligatoire ; scheduler itérant explicitement les tenants
- [ ] Commande `tenancy:verify-rls` : échoue si une table avec `tenant_id` n'a pas la RLS forcée

**Invariants et base**
- [ ] Variable de tenant absente : zéro ligne lue, écriture refusée
- [ ] Aucune connexion applicative avec un rôle superuser ou propriétaire

**Tests obligatoires** (exécutés avec le rôle applicatif)
- [ ] A → A et A ↛ B pour `SELECT`, `INSERT`, `UPDATE`, `DELETE`, jointures
- [ ] Insert avec `tenant_id` de B depuis le contexte A refusé par la base
- [ ] Job sans contexte refusé ; job avec contexte A ne lit pas B
- [ ] Aucune fuite de contexte entre deux requêtes successives du même processus
- [ ] `tenancy:verify-rls` vert sur toutes les tables

**Acceptance**
- [ ] GIVEN un bug applicatif simulé qui oublie le filtre tenant, WHEN la requête s'exécute, THEN la base ne renvoie que les lignes du tenant courant.

**À trancher** — Type PostgreSQL des ULID (`char(26)` Laravel ou `uuid` converti) ; nom des rôles.

---

### P03 — Audit, corrélation, erreurs typées

`VITAL` · Dépend : P02 · Cahier : §153, §154, §191, §263, §264, §325, §367, §368, §385, §392, §473

**Livrables**
- [ ] Table `audit_events` : acteur, action, sujet, `before` et `after` en `jsonb`, raison, source (http, cli, queue, api, ai), IP, user agent, `correlation_id`, `causation_id`, `occurred_at`, `tenant_id` (nullable pour les événements plateforme)
- [ ] Append-only : trigger refusant `UPDATE` et `DELETE`, privilèges révoqués pour le rôle applicatif ; tentative journalisée en alerte
- [ ] RLS sur `audit_events`
- [ ] `AuditLog::record()` écrit dans la transaction de l'action
- [ ] Redaction avant écriture et dans les logs : mots de passe, tokens, secrets (§367, §385)
- [ ] Middleware de corrélation (`X-Correlation-ID` validé ou généré), propagé aux logs, jobs, réponses
- [ ] Logs structurés JSON avec `request_id`, `correlation_id`, `tenant_id`, `user_id` (§191)
- [ ] Erreurs métier typées avec code stable (§263), rendu HTTP, toast Livewire, page d'erreur avec référence (§392)
- [ ] Branchement de l'audit sur les actions P01 et les événements d'authentification (connexion, échec, déconnexion, 2FA, mot de passe)
- [ ] UI d'audit owner/admin : liste filtrable, détail avant/après (§473)

**Tests obligatoires**
- [ ] `UPDATE` et `DELETE` sur `audit_events` refusés par la base
- [ ] Rollback de l'action : aucun audit écrit
- [ ] Secrets absents de l'audit et des logs
- [ ] Corrélation identique de la requête au job et à l'audit
- [ ] Audit du tenant B invisible depuis A

**Acceptance**
- [ ] GIVEN un admin qui renomme un workspace, WHEN l'owner ouvre l'audit, THEN il voit qui, quoi, quand, avant, après et la corrélation.

---

### P04 — ManeUI : fondations

`STANDARD` · Dépend : P01 · Cahier : §6, §7, §37–§43, §280–§283, §338, §339, §407, §464–§469

**Livrables**
- [ ] Tokens primitive → sémantique → composant dans le thème Tailwind 4 ; mode sombre ; propriétés logiques CSS pour le RTL (§38)
- [ ] Namespace de composants ManeUI encapsulant TallStackUI : Button, IconButton, Input, Textarea, Select, Checkbox, Radio, Switch, Badge, Status, Alert, Toast, Tooltip, Popover, Dropdown, Modal, Drawer, Tabs, Accordion, Card, Breadcrumb, Avatar, Pagination, EmptyState, ErrorState, LoadingState, Skeleton
- [ ] Contrat de formulaire Livewire (§281) : validation, autorisation, état sale, envoi, chargement, erreur, succès, réinitialisation
- [ ] DataTable Livewire (§282) : tri sur colonnes autorisées, filtres, recherche, pagination serveur, sélection, actions groupées, colonnes visibles, vues enregistrées, densité ; jamais de chargement complet (§485)
- [ ] États obligatoires documentés par composant (§41)
- [ ] Catalogue interne des composants, disponible en environnement local uniquement
- [ ] Pages existantes (auth, settings, shell, P01) migrées vers ManeUI
- [ ] `docs/design-system/` : tokens, composants, règles d'usage

Les composants spécialisés arrivent avec la passe qui en a besoin : dates (P07), montants et quantités (P08), arbre (P13), upload (P14), Kanban (P21), calendrier (P52), palette de commandes (P66).

**Tests obligatoires**
- [ ] Accessibilité axe sans violation sérieuse ou critique sur les pages clés (navigateur)
- [ ] Navigation clavier et focus visible sur formulaires, modales, tables
- [ ] Tri DataTable limité aux colonnes déclarées (pas d'injection par paramètre)
- [ ] Rendu sombre et `dir="rtl"` sans casse de mise en page

**Acceptance**
- [ ] GIVEN un bouton d'envoi désactivé, WHEN on navigue au clavier, THEN le focus reste visible, l'état désactivé est annoncé et l'action ne s'exécute pas (§407).

---

### P05 — Membres, invitations, RBAC, Policy Engine

`VITAL` · Dépend : P03, P04 · Cahier : §16.3, §20, §21, §28, §28.1, §29, §30, §378

**Livrables**
- [ ] Invitations : email normalisé, rôle, token haché, expiration 7 jours, révocation ; une seule invitation en attente par email et tenant
- [ ] Email d'invitation ; acceptation par compte existant ou inscription ; l'email doit correspondre
- [ ] Liste des membres, changement de rôle, retrait, départ volontaire
- [ ] Transfert de propriété atomique, confirmé par mot de passe et 2FA si active
- [ ] RBAC §28.1 : permissions déclarées en code et synchronisées, rôles par tenant, rôles système non supprimables ; migration expand/contract de `tenant_members.role`
- [ ] Restriction d'un membre à certains workspaces
- [ ] Policy Engine : décisions `ALLOW`, `ALLOW_WITH_CONSTRAINTS`, `DENY`, `REQUIRE_APPROVAL` ; `REQUIRE_APPROVAL` répond `APPROVAL_REQUIRED` jusqu'à P15
- [ ] Capabilities §30 et règle SoD « créateur ≠ approbateur » déclarable par permission
- [ ] Archivage du tenant par l'owner
- [ ] Sessions et appareils : liste, révocation, alerte de nouvelle connexion
- [ ] MFA exigible par tenant pour owner et admin (§378)

**Invariants et base**
- [ ] Un seul owner (existant), une invitation en attente par email et tenant, noms de rôles uniques par tenant, clés de permission uniques

**Tests obligatoires**
- [ ] Invitation expirée, révoquée, réutilisée ou acceptée avec un autre email : refus
- [ ] Escalade : un admin ne se donne pas owner, un member n'invite pas
- [ ] Deux transferts de propriété simultanés : un seul aboutit
- [ ] Restriction de workspace appliquée sur toutes les routes et requêtes Livewire
- [ ] MFA imposée ; session révoquée inutilisable
- [ ] IDOR sur les identifiants de membres et d'invitations ; audit de chaque action

**Acceptance**
- [ ] GIVEN un admin, WHEN il tente de se promouvoir owner, THEN la décision est `DENY` et l'audit contient la tentative.

---

### P06 — Back-office opérateur

`STANDARD` · Dépend : P05 · Cahier : §20.2, §269, §475–§479, D1

**Livrables**
- [ ] Rôle plateforme distinct, attribué uniquement par commande Artisan
- [ ] Zone `/platform` : layout séparé, MFA obligatoire, reconfirmation du mot de passe
- [ ] Liste des tenants (owner, création, statut, volumétrie), suspension et réactivation
- [ ] Tenant suspendu : accès refusé avec message explicite, y compris Livewire et jobs
- [ ] Impersonation (§477) : 30 minutes maximum, raison obligatoire, bannière visible, actions destructives bloquées, jamais sur un opérateur, auditée
- [ ] Santé minimale (§475) : base, file `database`, jobs en échec, disque

**Tests obligatoires**
- [ ] Non-opérateur : 404 sur `/platform`
- [ ] Impersonation expirée inutilisable ; action destructive refusée pendant l'impersonation
- [ ] Toutes les actions opérateur auditées

**À trancher** — Lecture inter-tenants : connexion dédiée avec rôle `BYPASSRLS` en lecture seule limité aux tables de tenancy, ou vues agrégées.

---

### P07 — Localisation et temps

`STANDARD` · Dépend : P04 · Cahier : §32–§34, §163, §182, §447, D7

**Livrables**
- [ ] Préférences locale et fuseau : user, workspace, tenant ; résolution user > workspace > tenant > application
- [ ] Middleware de locale ; `lang/fr` et `lang/en` complets
- [ ] Formatage nombres, dates, devises via `intl` (extension vérifiée sous Herd)
- [ ] Primitives temps (§34) : `LocalDate`, `DateRange`, `TimeRange`, `Duration` ; stockage UTC avec fuseau explicite
- [ ] Interface `Clock` injectable et horloge de test ; `now()` interdit dans `App\Domain` (test d'architecture)
- [ ] Calendriers : jours fériés France versionnés, heures ouvrées (§182, §447)
- [ ] Composants DatePicker, DateRangePicker, TimePicker
- [ ] Attribut `dir` piloté par la locale (RTL complet en P66)
- [ ] Page d'accueil (`home.blade.php`) passée en clés anglaises traduites en `fr` ; exclusion retirée de `tests/Unit/TranslationsTest.php`

**Tests obligatoires**
- [ ] Changements d'heure Europe/Paris (mars, octobre)
- [ ] Chevauchement de plages ; jours fériés français 2026–2030 connus
- [ ] Test échouant si une clé `fr` manque
- [ ] Affichage dans le fuseau de l'utilisateur

---

### P08 — Money et Quantity

`VITAL` · Dépend : P04 · Cahier : §35, §36, §64, §214, §438, §490

**Livrables**
- [ ] Table `currencies` (ISO 4217, unités mineures) semée (§490)
- [ ] Value object `Money` : entier en unité mineure + devise, arithmétique, répartition sans perte, modes d'arrondi documentés (§438)
- [ ] Cast Eloquent : colonne `bigint` + `char(3)` ; aucun `float`
- [ ] `Quantity` + `uoms` et conversions par catégorie, semées (kg, g, pièce, litre, mètre, heure, carton, palette)
- [ ] Composants MoneyInput, QuantityInput, MoneyCell

Les taux de change et les montants fonctionnels ou de reporting arrivent en P57 ; en V1.0, une opération porte une seule devise.

**Tests obligatoires**
- [ ] Propriété : la somme d'une répartition égale toujours le total (§214)
- [ ] Opération entre devises différentes refusée
- [ ] Conversion d'unité aller-retour sans dérive
- [ ] Contrainte base : montant entier, devise existante

---

### P09 — Configuration, settings, feature flags

`STANDARD` · Dépend : P05 · Cahier : §163, §164, §199, §267, §268, §440

**Livrables**
- [ ] Registre des settings déclarés en code : clé, type, défaut, niveaux autorisés, capability (§30), validation
- [ ] Table `settings` (niveau, identifiant, clé, valeur `jsonb`, version), unique par niveau et clé
- [ ] Résolution hiérarchique System → Tenant → Workspace (Organization, Legal Entity, Site, Channel ajoutés par P13 et P18)
- [ ] Changement de configuration audité avec raison (§440)
- [ ] Secrets séparés : colonnes chiffrées, jamais dans `settings`, procédure de rotation documentée (§267, §268)
- [ ] Feature flags par tenant (§199), chacun avec owner et date de retrait
- [ ] UI générique de settings tenant et workspace, générée depuis le registre

**Tests obligatoires**
- [ ] Priorité de résolution entre niveaux
- [ ] `PRIVILEGED_CONFIG` refusé à un admin si réservé à l'owner
- [ ] Secret jamais sérialisé, journalisé ni renvoyé à l'UI

**À trancher** — Laravel Pennant pour les feature flags (dépendance à approuver).

---

### P10 — Infrastructure d'événements

`VITAL` · Dépend : P03 · Cahier : §122–§124, §193, §217, §325, §343, §357–§362, §393, §400, §413, §480–§482

**Livrables**
- [ ] `outbox_messages` écrite dans la transaction métier : `event_id`, type, version, payload `jsonb`, tenant, corrélation, causalité, publication, tentatives, dernière erreur
- [ ] Événements versionnés (`PagePublished.v1`) avec schéma, sensibilité, producteur, consommateurs (§343)
- [ ] Relais de publication sur la file `database` ; ordre par agrégat ; livraison au moins une fois
- [ ] `inbox_messages` unique par consommateur et `event_id` : traitement unique (§123)
- [ ] Retry, backoff, nombre maximal, dead letter, commande de rejeu protégée (§124)
- [ ] Files nommées et prioritaires (§193, §362), compatibles Redis/Horizon plus tard
- [ ] Garde de récursion par profondeur de causalité (§481) ; métriques de volume (§482)
- [ ] Table `operations` + composant de suivi pour les opérations longues (§393)

**Tests obligatoires**
- [ ] Rollback métier : aucun message en outbox
- [ ] Double livraison : un seul traitement
- [ ] Échec consommateur : retry puis dead letter ; rejeu idempotent
- [ ] Contexte tenant restauré dans le consommateur (RLS)
- [ ] Tests de contrat d'événement (§217)

**À trancher** — Rester sur la file `database` jusqu'à P39 sauf besoin mesuré (recommandé).

---

### P11 — Notifications et emails

`STANDARD` · Dépend : P07, P10 · Cahier : §125, §322, §331, §455, §456, §471

**Livrables**
- [ ] Canaux V1.0 : in-app et email (SMS et push en P40)
- [ ] Templates versionnés (§331) : version, locale, sujet, corps, variables déclarées, aperçu, envoi de test, statut
- [ ] Préférences (§455) : transactionnel (non désactivable), opérationnel ; marketing en P40
- [ ] Centre de notifications in-app : lu / non lu, clé de déduplication
- [ ] Suivi d'envoi : envoyé, échec ; rebonds via webhook du fournisseur
- [ ] Emails d'authentification et d'invitation migrés vers les templates
- [ ] Envoi via outbox : un échec d'email n'annule jamais l'opération métier (§322)

**Tests obligatoires**
- [ ] Locale du destinataire respectée ; variable manquante détectée à l'aperçu
- [ ] Déduplication ; préférence respectée
- [ ] Rendu email : HTML valide, variables, CTA (§471)

**À trancher** — Fournisseur d'email transactionnel (hébergé en UE).

---

### P12 — Party et MDM core

`STANDARD` · Dépend : P05, P07, P10 · Cahier : §22–§26, §141, §156, §184, §185, §258, §275, §276

**Livrables**
- [ ] `parties` avec sous-types `Person`, `Company`, `Household` (§11.1)
- [ ] `Company` : raison sociale, SIREN, SIRET, numéro de TVA (formats validés)
- [ ] Points de contact : emails, téléphones normalisés E.164, adresses structurées (pays ISO), avec usage et dates de validité
- [ ] Rôles de Party (§23) comme références : client, fournisseur, contact, partenaire…
- [ ] Relations datées : personne ↔ société, membres d'un foyer
- [ ] Identifiants externes génériques (§141)
- [ ] Déduplication (§276) : clés normalisées + candidats approchés, file de revue, fusion avec règles de survie et réaffectation des références, auditée
- [ ] Indicateurs de qualité : complétude, unicité, validité (§275)
- [ ] Classification des champs personnels (§156)
- [ ] UI : liste DataTable, fiche avec en-tête et onglets Aperçu, Activité, Relations, Audit (§185)

**Invariants et base**
- [ ] SIREN unique par tenant lorsqu'il est renseigné ; email normalisé unique par Party
- [ ] Une fusion ne supprime jamais une transaction : elle redirige les références

**Tests obligatoires**
- [ ] Vrais et faux positifs de détection de doublons ; fusion conservant l'historique (§26)
- [ ] Isolation tenant ; données personnelles absentes des logs

---

### P13 — Organisation, entités légales, sites

`STANDARD` · Dépend : P08, P09, P12 · Cahier : §16.2, §27, §163

**Livrables**
- [ ] `Organization` par workspace ; Business Units, Departments, Teams, Cost Centers, Profit Centers
- [ ] `LegalEntity` : pays, forme, SIREN, TVA, adresse, devise fonctionnelle, début d'exercice
- [ ] `Site` / Branch : lieu opérationnel
- [ ] Relations organisationnelles datées
- [ ] Niveaux de settings Organization, Legal Entity, Site (P09)
- [ ] UI organigramme (composant Tree)

**Invariants et base**
- [ ] Hiérarchie sans cycle
- [ ] Contrainte d'exclusion PostgreSQL sur les périodes de validité qui se chevauchent

**Tests obligatoires**
- [ ] Création de cycle refusée ; chevauchement refusé par la base ; isolation tenant

---

### P14 — Fichiers et médias

`VITAL` · Dépend : P03, P04, P09 · Cahier : §50, §221, §271, §271.1, §272, §301

**Livrables**
- [ ] `files` (§271) : ULID, tenant, propriétaire, disque, chemin, nom d'origine, MIME détecté, extension, taille, SHA-256, classification, statut d'analyse
- [ ] Pipeline d'upload Livewire selon §271.1 ; purge planifiée des temporaires ; déduplication par checksum dans le tenant
- [ ] Disque privé ; livraison par contrôleur autorisé et URL signée temporaire (§272) ; publication explicite
- [ ] Images : ré-encodage, suppression EXIF, variantes (vignette, tailles, WebP)
- [ ] Politique d'analyse antivirus : interface + adaptateur ClamAV optionnel
- [ ] `MediaAsset` (§50) : texte alternatif, droits, dossiers, tags, versions
- [ ] Médiathèque : grille/liste, glisser-déposer, sélecteur réutilisable
- [ ] Logos tenant et workspace, avatar utilisateur (reportés depuis P01)
- [ ] Comptage du stockage par tenant (quotas en P38)

**Tests obligatoires**
- [ ] Fichier PHP déguisé en JPEG refusé ; SVG refusé ; taille dépassée refusée
- [ ] EXIF supprimé ; nom malveillant sans effet sur le chemin
- [ ] URL sans signature ou expirée : refus ; identifiant d'un autre tenant : 404
- [ ] Déduplication par checksum

**À trancher** — Bibliothèque de traitement d'image (dépendance) ; GD ou Imagick sous Herd ; analyse antivirus en V1.0.

---

### P15 — Workflow : états, approbations, process managers

`STANDARD` · Dépend : P05, P10, P11 · Cahier : §28, §29, §116–§121, §441

**Livrables**
- [ ] Convention de state machine de domaine (§117) : états en enum, transitions déclarées, gardes, événement par transition ; colonne d'état modifiable seulement par transition (test d'architecture)
- [ ] Approval Engine (§118) : demandeur, ensemble d'approbateurs, policy, quorum, délégation, échéance, escalade, décision, preuve
- [ ] `REQUIRE_APPROVAL` du Policy Engine crée une demande d'approbation (P05)
- [ ] Boîte d'approbation (ApprovalInbox §40)
- [ ] Process manager / saga (§121) : état persisté, étapes avec retry, timeout, compensation, corrélation
- [ ] Versionnage des processus (§441)

**Tests obligatoires**
- [ ] Quorum ; délégation ; le demandeur n'approuve pas sa propre demande (SoD)
- [ ] Escalade à échéance avec horloge simulée
- [ ] Deux approbations simultanées sur un quorum de 1 : une seule décision
- [ ] Compensation d'une saga en échec

---

### P16 — Numérotation et rendu documentaire

`VITAL` · Dépend : P13, P14 · Cahier : §112, §240, §332–§334, §470, §472

**Livrables**
- [ ] Moteur de numérotation (§332) : séquences par Legal Entity, type de document, exercice (+ site ou pays si besoin), format configurable
- [ ] Allocation sans trou dans la transaction métier (verrou de ligne sur la séquence) ; jamais de réutilisation
- [ ] Gabarits Blade versionnés par locale et marque
- [ ] Rendu PDF ; artefacts immuables stockés (P14) avec hash, version, métadonnées (§333)
- [ ] Styles d'impression (§470)

**Tests obligatoires**
- [ ] Deux workers allouent simultanément : aucun doublon, aucun trou
- [ ] Rollback métier : le numéro n'est pas consommé
- [ ] Changement d'exercice
- [ ] Rendu : valeurs, locale, numéro vérifiés (§334, §472)

**À trancher** — Bibliothèque PDF compatible PDF/A-3 (requis par Factur-X en P26).

---

### P17 — CMS core

`VITAL` (Publishing) · Dépend : P14, P15 · Cahier : §44, §45, §221, §231, §337, §354

**Livrables**
- [ ] Types de contenu définis en code pour V1.0 : Page, Article, Article KB ; champs typés (types personnalisés en P63)
- [ ] Entrées et révisions immuables ; brouillon = dernière révision ; pointeur de révision publiée
- [ ] Cycle de vie (§45) en state machine : Draft, Review, Approved, Scheduled, Published, Unpublished, Archived
- [ ] Revue et approbation via P15, activable par tenant
- [ ] Programmation de publication et dépublication, idempotente
- [ ] Aperçu par URL signée et expirante, jamais indexé
- [ ] Rollback = republication d'une révision antérieure comme nouvelle révision
- [ ] Taxonomies, tags, menus de navigation (arbre), redirections 301/302 avec détection de boucles
- [ ] Texte riche assaini côté serveur (XSS §221)
- [ ] Variantes de traduction liées par groupe (§337)
- [ ] Événements `PagePublished.v1`, `PageUnpublished.v1`, `RevisionPublished.v1` via outbox (§354)
- [ ] Permissions `content.*` (create, edit, review, publish) ; SoD optionnelle

**Tests obligatoires** (recette §231)
- [ ] Brouillon jamais public ; révision publiée exacte visible
- [ ] Publication programmée exécutée une seule fois malgré deux passages du scheduler
- [ ] Charges XSS neutralisées ; aperçu expiré refusé
- [ ] Rollback ; boucle de redirection refusée ; audit présent ; isolation tenant

**Acceptance**
- [ ] GIVEN un rédacteur, un relecteur et un éditeur, WHEN la page est créée, approuvée puis publiée, THEN le visiteur voit exactement la révision publiée (§231).

**À trancher** — Éditeur de texte riche et bibliothèque d'assainissement HTML (dépendances).

---

### P18 — Canaux, sites publics, SEO

`STANDARD` · Dépend : P17 · Cahier : §31, §44.1, §49, §261, §270, §337, §361, D5

**Livrables**
- [ ] Primitive Channel (§31) : type Web en V1.0, locales, fuseau, thème, flags
- [ ] Site = Channel Web ; sous-domaine plateforme selon les règles de slug §15.1 (liste réservée)
- [ ] Domaines personnalisés : ajout, vérification DNS TXT, statut, TLS automatique
- [ ] Résolution publique par hôte → site → tenant, en lecture seule ; hôte inconnu ou non vérifié : 404
- [ ] Un ou deux thèmes officiels versionnés ; en-tête et pied depuis la navigation
- [ ] Rendu des révisions publiées ; cache par site, locale, révision, invalidé à la publication (§361)
- [ ] SEO (§49) : title, description, canonical, robots, OpenGraph, Twitter, schema.org, hreflang, sitemap et robots.txt par site
- [ ] Rapport de santé SEO (§49)
- [ ] En-têtes de sécurité et CSP sur les sites publics (§270)

**Tests obligatoires**
- [ ] Résolution d'hôte, domaine non vérifié, aucune fuite entre sites
- [ ] Invalidation de cache à la publication
- [ ] Sitemap limité au contenu publié ; aperçu absent ; hreflang correct
- [ ] Présence des en-têtes de sécurité

**À trancher** — Domaine de la plateforme et mécanisme TLS des domaines clients (dépend de l'hébergement, P39).

---

### P19 — Page builder

`STANDARD` · Dépend : P18 · Cahier : §46, §47, §340

**Livrables**
- [ ] Blocs en liste blanche définis en code : héro, texte, image, galerie, appel à l'action, colonnes, formulaire, témoignages, tarifs, FAQ, intégrations autorisées
- [ ] Arbre de document stocké par révision avec version de schéma et migration de schéma
- [ ] Éditeur : canevas, arbre, inspecteur, glisser-déposer, aperçu responsive, annuler/rétablir, sauvegarde automatique
- [ ] Liaisons de données et conditions de visibilité limitées (§46)
- [ ] Alternative clavier au glisser-déposer ; accessibilité de l'éditeur
- [ ] Definition of Done §340 cochée

**Tests obligatoires**
- [ ] Bloc ou propriété inconnus refusés ; XSS dans les propriétés neutralisé
- [ ] Deux onglets sur la même page : verrou optimiste, aucune perte silencieuse
- [ ] Annuler/rétablir ; tests navigateur glisser-déposer et clavier

---

### P20 — Blog et Knowledge

`STANDARD` · Dépend : P18 · Cahier : §48, §57

**Livrables**
- [ ] Blog : articles, auteurs, catégories, tags, flux RSS par site (commentaires en P43, newsletter en P40)
- [ ] Knowledge : collections, audiences (public, interne, clients via P31), visibilité, versions, approbation
- [ ] Base de connaissances publique sur les sites

**Tests obligatoires**
- [ ] RSS valide ; article KB interne jamais public ; audience client respectée

---

### P21 — CRM

`STANDARD` · Dépend : P12, P15, P18 · Cahier : §52, §53, §184, §232, §459

**Livrables**
- [ ] Leads : source, campagne, score par règles, owner, qualification, référence de consentement, conversion
- [ ] Capture depuis les formulaires des sites : honeypot, limitation de débit, consentement enregistré
- [ ] Comptes = Party avec rôle client ; contacts ; hiérarchie
- [ ] Opportunités : pipelines et étapes configurables, montant, probabilité, clôture prévue, owner, lignes produits/services (P22)
- [ ] Activités (§53) : appel, rendez-vous, tâche, note, email journalisé, rappel
- [ ] Timeline en read model (§184, §459)
- [ ] Vue Kanban du pipeline
- [ ] Conversion lead → Party / contact / opportunité, avec dédoublonnage P12

**Invariants et base**
- [ ] Un lead ne peut être converti qu'une fois (contrainte d'unicité sur le lien)

**Tests obligatoires** (recette §232)
- [ ] Double conversion simultanée : une seule réussit
- [ ] Visibilité limitée au propriétaire si l'option est active
- [ ] Timeline mise à jour par projection ; limitation de débit des formulaires ; consentement stocké

**Acceptance**
- [ ] GIVEN un visiteur qui remplit le formulaire de contact du site, WHEN un commercial qualifie et convertit le lead, THEN un contact, un compte et une opportunité existent, et la timeline retrace l'historique.

---

### P22 — Catalogue et tarification de base

`STANDARD` · Dépend : P08, P13 · Cahier : §93, §95, §157

**Livrables**
- [ ] Produits et services minimaux : type, nom, description, unité (P08), catégorie fiscale (P23), prix par défaut, actif
- [ ] Listes de prix : prix de base, prix spécifiques client, validité datée, devise de la Legal Entity
- [ ] Service de résolution de prix retournant un snapshot (§157)

**Invariants et base**
- [ ] Contrainte d'exclusion : pas deux prix valides en même temps pour le même produit dans la même liste

**Tests obligatoires**
- [ ] Priorité et dates de résolution ; snapshot inchangé après modification du prix

---

### P23 — Moteur fiscal et pack France

`VITAL` · Dépend : P22 · Cahier : §69, §157, §214, §439, §444–§446, D4

**Livrables**
- [ ] Contexte Jurisdiction et packs versionnés (§444) : juridiction, dates d'effet, version, source, date de revue
- [ ] TVA France datée : 20 %, 10 %, 5,5 %, 2,1 %, 0 % ; codes de taxe
- [ ] Cas : franchise en base (art. 293 B du CGI), autoliquidation, B2B intracommunautaire, export
- [ ] Calcul par ligne et par document ; règle d'arrondi documentée
- [ ] Lieu de prestation de base pour les services B2B et B2C dans l'UE
- [ ] Snapshot fiscal sur les documents (§157)
- [ ] Liste des mentions obligatoires transmise à P26

**Tests obligatoires**
- [ ] Propriété : total des taxes = somme des lignes, selon la règle d'arrondi (§214)
- [ ] Bascule de taux à une date d'effet
- [ ] Exemples connus fournis par le pack (§445) : franchise, autoliquidation, intracommunautaire

**À trancher** — Validation du pack par un expert-comptable (§446 : aucune garantie juridique sans validation).

---

### P24 — Devis et contrats légers

`STANDARD` · Dépend : P16, P21, P23 · Cahier : §54, §113, §222

**Livrables**
- [ ] Devis : lignes avec snapshot de prix et de taxe, remises, totaux, validité, versions
- [ ] États : brouillon, envoyé, accepté, refusé, expiré, converti ; numérotation (P16) ; PDF (P16)
- [ ] Approbation des remises au-delà d'un seuil (P15, policy `CanDiscount`)
- [ ] Envoi par email avec lien public sécurisé
- [ ] Acceptation en ligne avec preuve simple : nom, horodatage, IP, hash du PDF (signature avancée en P45)
- [ ] Conversion en projet (P29) ou en facture et facture d'acompte (P26)
- [ ] Lettre de mission simple liée au devis

**Tests obligatoires**
- [ ] Modification du prix via le lien d'acceptation refusée (§222)
- [ ] Devis accepté immuable ; double acceptation idempotente
- [ ] Expiration avec horloge simulée ; seuil d'approbation des remises

---

### P25 — Finance core (grand livre)

`VITAL` · Dépend : P13, P15, P23 · Cahier : §61–§63, §65, §157, §213–§215, §233, §405, §433

**Livrables**
- [ ] Plan comptable PCG semé par Legal Entity
- [ ] Journaux (ventes, achats, banque, opérations diverses), exercices et périodes : Open, Soft Closed, Closed, Locked
- [ ] Écritures et lignes ; écriture postée immuable
- [ ] Pipeline de comptabilisation (§63) : événement → mapping → brouillon → validation → policy → post → audit → outbox
- [ ] Clôture et verrouillage de période (policy `CanClosePeriod`), audités
- [ ] Contre-passation au lieu de suppression
- [ ] Export FEC (obligation française)
- [ ] États : balance, grand livre, journal

**Invariants et base**
- [ ] Débits = crédits par écriture, vérifié par trigger différé au commit
- [ ] Lignes postées non modifiables (trigger)
- [ ] Unicité (source, mapping) : pas de double comptabilisation

**Tests obligatoires**
- [ ] Propriété : toute écriture postée est équilibrée (§214)
- [ ] Course entre clôture de période et comptabilisation (§215)
- [ ] Écriture dans une période close refusée, aucune ligne créée, refus audité (§405)
- [ ] Double comptabilisation refusée ; format FEC validé ; cible de mutation (§213)

---

### P26 — Facturation client et facture électronique France

`VITAL` · Dépend : P24, P25 · Cahier : §67, §70, §157, §439

**Livrables**
- [ ] Factures depuis devis, projet ou saisie ; lignes avec snapshots (prix, taxe, adresses, informations de la Legal Entity)
- [ ] États : brouillon, émise, partiellement payée, payée ; annulation par avoir uniquement
- [ ] Mentions obligatoires françaises (liste P23), dont pénalités de retard, indemnité forfaitaire de 40 €, mention de franchise de TVA
- [ ] Numérotation sans trou (P16) ; PDF (P16)
- [ ] Comptabilisation via P25 sur `InvoiceIssued.v1`
- [ ] Conditions de paiement, échéances, relances programmées (P11)
- [ ] Lettrage des paiements, passage en perte
- [ ] Factur-X (PDF/A-3 + XML CII, profil EN 16931)
- [ ] Émission et réception via une Plateforme Agréée : interface + un connecteur, statuts de cycle de vie, e-reporting
- [ ] Factures fournisseurs reçues transmises à P30

**Invariants et base**
- [ ] Facture émise non modifiable (trigger)

**Tests obligatoires**
- [ ] Un avoir contre-passe la facture en comptabilité
- [ ] Numérotation concurrente ; XML Factur-X valide selon le schéma
- [ ] Mentions obligatoires présentes dans le PDF ; relances avec horloge simulée
- [ ] Rapprochement des taxes (§439) ; états de paiement partiel

**À trancher** — Plateforme Agréée partenaire ; calendrier de la réforme à revérifier au démarrage (réception obligatoire pour toutes les entreprises depuis le 1er septembre 2026, émission par les PME et micro-entreprises à partir du 1er septembre 2027).

---

### P27 — Paiements

`VITAL` · Dépend : P26 · Cahier : §104–§106, §139, §161, §222, §266, §302, §321, §404, §452, §453

**Livrables**
- [ ] Agrégat Payment et son cycle (§104) ; interface d'adaptateur fournisseur (§105) ; un premier fournisseur
- [ ] Liens de paiement sur factures et acomptes de devis
- [ ] Clés d'idempotence uniques par tenant, réponse mémorisée (§106)
- [ ] Webhooks entrants (§139, §266) : signature, horodatage, payload brut conservé, `provider_event_id` unique, inbox, commande de domaine
- [ ] Remboursements (§452) et litiges de base (§453)
- [ ] Échec partiel (§321) : paiement capturé mais facture non mise à jour → outbox + alerte opérationnelle
- [ ] Comptabilisation (P25) et lettrage des factures
- [ ] Identifiants fournisseur par tenant stockés comme secrets (P09)
- [ ] Tests d'intégration sandbox (§302)

**Tests obligatoires**
- [ ] Commande envoyée deux fois : un seul effet, même réponse (§404)
- [ ] Webhook dupliqué, rejoué ou à signature invalide
- [ ] Montant falsifié ; capture concurrente ; remboursement supérieur au capturé refusé
- [ ] Cible de mutation atteinte

**À trancher** — Fournisseur (Stripe recommandé) et modèle : chaque tenant connecte son propre compte.

---

### P28 — Banque et rapprochement

`VITAL` · Dépend : P27 · Cahier : §68, §183, §277, §433, §488

**Livrables**
- [ ] Comptes bancaires par Legal Entity (IBAN validé)
- [ ] Import de relevés CAMT.053, OFX, CSV avec mapping ; import idempotent (§488)
- [ ] Transactions bancaires, rapprochement par règles et manuel (assisté par IA en P35)
- [ ] Moteur générique de rapprochement (§277) : rapproché, partiel, non rapproché, exception
- [ ] Ajustements comptabilisés (P25)

**Tests obligatoires**
- [ ] Réimport du même fichier sans doublon ; règles de rapprochement
- [ ] Rapprochement grand livre ↔ banque ↔ paiements (§433) ; isolation entre Legal Entities

**À trancher** — Agrégation bancaire par API (open banking) en V1.0 ou plus tard.

---

### P29 — Projets, temps, facturation au temps

`STANDARD` · Dépend : P24, P26 · Cahier : §87, §88, §157, §255

**Livrables**
- [ ] Projets : phases, jalons, budget, affectation des ressources
- [ ] `Task` minimal dans Collaboration (§11.1), rattaché aux phases
- [ ] Saisie des temps : personne, projet, tâche, date, durée, facturable, snapshot de taux
- [ ] Soumission hebdomadaire et approbation (P15) ; temps approuvés verrouillés
- [ ] Taux par personne, rôle ou projet, datés
- [ ] Facturation : régie vers brouillon de facture (P26), forfait par jalons, abonnement de services (retainer)
- [ ] Rapport de marge et rentabilité (§255)

**Invariants et base**
- [ ] Une saisie de temps n'est facturée qu'une fois (lien unique vers la ligne de facture)

**Tests obligatoires**
- [ ] Temps approuvé non modifiable ; double facturation impossible
- [ ] Snapshot de taux insensible à un changement ultérieur ; totaux de durée

---

### P30 — Dépenses et fournisseurs (AP léger)

`VITAL` · Dépend : P26, P28 · Cahier : §29, §66

**Livrables**
- [ ] Fournisseurs = Party avec rôle fournisseur
- [ ] Factures fournisseurs saisies ou reçues via la Plateforme Agréée (P26) ; approbation (P15) ; comptabilisation (P25)
- [ ] Statut de paiement, manuel ou par rapprochement bancaire (P28)
- [ ] Notes de frais avec justificatifs (P14), approbation, remboursement
- [ ] Refacturation des frais aux projets (P29)

**Invariants et base**
- [ ] Unicité (fournisseur, numéro de facture)

**Tests obligatoires**
- [ ] SoD : le créateur n'approuve pas ; justificatif obligatoire au-delà d'un seuil ; doublon refusé

---

### P31 — Portail client

`STANDARD` · Dépend : P27, P29 · Cahier : §172, §173, §341

**Livrables**
- [ ] Accès portail pour les contacts clients : table `users` existante + lien explicite vers la Party cliente
- [ ] Profil, devis (acceptation), factures (téléchargement, paiement P27), paiements, documents partagés, avancement des projets
- [ ] KB réservée aux clients (P20)
- [ ] Marque du workspace ou du site
- [ ] Definition of Done §341 : expiration de session, confidentialité, audit

**Tests obligatoires**
- [ ] Client A ne voit pas les factures du client B du même tenant (IDOR)
- [ ] Utilisateur portail refusé sur le back-office ; expiration de session ; acceptation de devis

---

### P32 — Recherche

`STANDARD` · Dépend : P21, P26 · Cahier : §127–§130, §278, §300, §386, §414, §430, §431, §483

**Livrables**
- [ ] Activation de Meilisearch (§4.2) : configuration, `.env.example`, health check, runbook
- [ ] Projections depuis l'outbox : contenus, Parties, CRM, devis, factures, projets, KB, métadonnées de fichiers
- [ ] Sécurité (§129, §386) : filtres tenant, visibilité et permission imposés à chaque requête ; données classifiées exclues
- [ ] Recherche globale dans l'application ; recherche publique des sites (publié uniquement)
- [ ] Réindexation versionnée avec bascule, limitée en débit (§414, §483)
- [ ] Jeu de recall et de classement (§430, §431)

**Tests obligatoires** (§130, Meilisearch réel en CI)
- [ ] Isolation, autorisation, propagation des suppressions, cohérence à terme, locale, filtres
- [ ] Commentaire ou document privé jamais renvoyé

---

### P33 — Analytics, rapports, tableaux de bord

`STANDARD` · Dépend : P28, P29 · Cahier : §131–§135, §259, §260, §387, §415, §421, §432

**Livrables**
- [ ] Ingestion depuis l'outbox vers des faits et dimensions (schéma PostgreSQL dédié)
- [ ] Couche sémantique (§132) : chiffre d'affaires, créances, pipeline, rentabilité projet, trésorerie
- [ ] Tableaux de bord personnels et par rôle ; widgets KPI, graphique, table, entonnoir
- [ ] Générateur de rapports minimal : métrique, dimensions, filtres, export (P34)
- [ ] Lignage des données (§259) et sémantique temporelle (§260)
- [ ] Reconstruction depuis les sources (§415, §421)

**Tests obligatoires**
- [ ] Jeux de référence : chiffre d'affaires connu (§432)
- [ ] Reconstruction complète = calcul incrémental ; dimensions sensibles protégées (§387)

**À trancher** — Bibliothèque de graphiques (dépendance).

---

### P34 — API v1, webhooks, import/export

`STANDARD` · Dépend : P30 · Cahier : §136–§143, §216, §265, §342, §376, §379, §426, §484–§488

**Livrables**
- [ ] `/api/v1`, tokens Sanctum par tenant avec abilities, expiration, révocation, rotation, audit (§376)
- [ ] Ressources : Parties, CRM, produits, devis, factures, paiements, projets, temps, contenus
- [ ] Contrat d'erreur (§137), pagination (§486), idempotence (§138), limites de débit (§265)
- [ ] Documentation OpenAPI générée et tests de contrat (§216)
- [ ] Webhooks sortants : abonnements, signature, retries, journal de livraison
- [ ] Integration Hub de base (§140) : connexions, identifiants secrets, identifiants externes, mapping
- [ ] Import (§142) CSV/XLSX : mapping, validation, essai à blanc, import, rapport de rapprochement (§426), idempotent (§488), par lots (§484)
- [ ] Export (§143, §379) CSV/XLSX/PDF/JSON, asynchrone au-delà d'un seuil, autorisé et audité

**Tests obligatoires**
- [ ] Contrat d'API ; portée des tokens ; isolation tenant ; rejeu idempotent
- [ ] Réimport sans doublon ; export soumis à permission et audité
- [ ] Definition of Done API §342 cochée

**À trancher** — Générateur OpenAPI et bibliothèque XLSX (dépendances).

---

### P35 — Plateforme IA

`VITAL` · Dépend : P32, P34 · Cahier : §119, §144–§152, §224, §241, §335, §336, §380–§385, §406

**Livrables**
- [ ] Adaptateur fournisseur (§381), registre de modèles, repli (§382)
- [ ] Gouvernance des données (§380) : classification vérifiée avant envoi, activation par tenant, fournisseurs autorisés
- [ ] Outils (§147) avec schéma, permission, tier de risque, portée tenant, audit, limite, budget : recherche client, lecture facture, préparation de devis, recherche documentaire, rédaction et SEO de contenu, traduction
- [ ] Copilote respectant les permissions de l'utilisateur (§146)
- [ ] Actions sensibles en proposition + approbation (§119, §148)
- [ ] RAG (§150) avec métadonnées tenant, visibilité, classification, provenance
- [ ] Contrôle des coûts (§152), audit des exécutions (§384), reprise en main humaine (§383)
- [ ] Mémoire de traduction et traduction IA avec revue humaine (§335, §336)
- [ ] Suite d'évaluation (§151) et de sécurité (§224)

**Tests obligatoires**
- [ ] Injection de prompt directe et indirecte ; RAG inter-tenants impossible
- [ ] Outil non autorisé refusé ; plafond de coût respecté
- [ ] Une proposition de remboursement crée une approbation et n'exécute rien (§406)

**À trancher** — `laravel/ai` (dépendance), fournisseurs autorisés, stockage vectoriel (pgvector ou recherche hybride).

---

### P36 — Conformité RGPD core

`VITAL` · Dépend : P30 · Cahier : §155, §156, §326–§329, §416, §492

**Livrables**
- [ ] Registre de classification couvrant toutes les colonnes existantes (§156)
- [ ] Politiques de rétention par domaine, exécution planifiée, legal hold (§326, §327)
- [ ] Droits des personnes (§328) : accès et export, rectification, limitation, suppression sous réserve des obligations (factures conservées 10 ans)
- [ ] Registre des consentements (§329)
- [ ] Export du registre des traitements ; liste des sous-traitants
- [ ] Suppression de compte complète avec acteur d'audit pseudonymisé (§20.1)
- [ ] Gestion du consentement aux cookies sur les sites publics si un traceur est utilisé
- [ ] Rétention de l'audit (§416)

**Tests obligatoires**
- [ ] Suppression respectant le legal hold et la conservation des factures
- [ ] Export complet des données d'une personne ; tâche de rétention idempotente

---

### P37 — Business templates V1.0

`STANDARD` · Dépend : P31, P33 · Cahier : §165–§167, §443, §491

**Livrables**
- [ ] Setup Engine (§165) : modules, rôles, permissions, défauts fiscaux et comptables, formats de numérotation, gabarits de documents, tableaux de bord, thème et pages de site
- [ ] Templates versionnés : Personnel, Freelance, Agence, Services professionnels, Vierge
- [ ] Onboarding P01 étendu : choix → revue → installation transactionnelle, idempotente, auditée (§167)
- [ ] Données de démonstration activées explicitement (§491)

**Tests obligatoires**
- [ ] Installation rejouée sans doublon
- [ ] Nouvelle version d'un template sans effet silencieux sur un tenant existant (§443)
- [ ] Template Freelance : le fil de l'acceptance G1 fonctionne sans configuration manuelle

---

### P38 — Abonnement SaaS de la plateforme

`VITAL` · Dépend : P27, P06 · Cahier : §199, D1

**Livrables**
- [ ] Plans : fonctionnalités (flags P09) et quotas (utilisateurs, stockage, sites, domaines personnalisés, budget IA)
- [ ] Facturation des tenants par le compte de l'éditeur, distinct des paiements des tenants (P27)
- [ ] Essai, montée et descente de gamme avec prorata, relances, suspension pour impayé (P06)
- [ ] Factures de l'éditeur conformes au droit français
- [ ] Application des quotas en échec fermé avec message explicite

**Tests obligatoires**
- [ ] Quota atteint : création refusée ; états de facturation pilotés par webhook
- [ ] Descente de gamme au-delà des quotas : comportement défini et testé

**À trancher** — Laravel Cashier (dépendance) ; l'éditeur comme tenant de sa propre plateforme pour réutiliser P26.

---

### P39 — Préparation production

`VITAL` · Dépend : P35–P38 · Cahier : §189–§198, §267–§270, §345, §363–§368, §409–§419, §475, §479

**Livrables**
- [ ] Hébergement UE, déploiement et environnement de staging (§409)
- [ ] Activation de Redis (cache, files) et Horizon avec les files du §193 (§4.2)
- [ ] Stockage S3-compatible pour les fichiers ; Reverb si besoin temps réel en V1.0
- [ ] Rôles PostgreSQL de production (P02) ; ADR sur le pooling de connexions
- [ ] Sauvegardes base et fichiers ; restauration testée automatiquement ; RPO et RTO (§194, §195, §228)
- [ ] Observabilité : expédition des logs, métriques (§192), alertes (§417), health checks (§364), santé opérateur (§475)
- [ ] En-têtes de sécurité (§270), gestion des secrets (§267), break-glass (§269), mode incident (§479)
- [ ] Runbooks V1.0 (§196) : panne base, retard de file, retard webhooks paiement, restauration, rollback, incident d'isolation, panne email
- [ ] Ingénierie de release (§197, §410, §411) : pipeline, compatibilité des migrations, notes de rollback
- [ ] Budgets de performance définis avec population et jeu de données (§189, §190) ; scénarios de charge
- [ ] Matrice navigateurs pour les parcours critiques V1.0 (§218)
- [ ] Smoke tests post-déploiement (§418, §419)

**Tests obligatoires**
- [ ] Exercice de restauration réussi ; smoke post-déploiement vert ; seuils de performance atteints

**À trancher** — Hébergeur (Laravel Cloud, Forge + fournisseur UE, autre).

---

### G1 — Gate V1.0

- [ ] P00 à P39 acceptées
- [ ] Catégories §395 applicables vertes : isolation tenant, autorisation, intégrité finance, intégrité paiements, rétention, audit, sécurité critique, compatibilité des migrations, sauvegarde et restauration
- [ ] Acceptance Freelance §306 en test navigateur : formulaire du site → lead → opportunité → devis → acceptation → projet → temps → facture Factur-X → paiement sandbox → rapprochement bancaire → grand livre équilibré → export FEC
- [ ] Acceptance Personnel §305, partie V1.0 : profil, contacts, tâches, notes, documents, dépenses, assistant IA (calendrier et réservations en P52)
- [ ] Definition of Done de release §345
- [ ] Revue de sécurité et test d'intrusion externe
- [ ] Documents légaux de l'éditeur : CGU, CGV, DPA, politique de confidentialité, mentions légales

---

## 5. Trains V1.x — vers la V1 absolue

Chaque passe ci-dessous reçoit sa spécification détaillée au moment de sa planification. Les
livrables listés sont son périmètre minimum contractuel ; les tests listés sont obligatoires.

### P40 — Marketing, consentement, communication center
`STANDARD` · Cahier : §55, §329–§331, §455–§457
- [ ] Campagnes, segments, audiences (requêtes sur Party et CRM)
- [ ] Emails de masse : désinscription en un clic, liste de suppression, préférences
- [ ] SMS et push via adaptateurs
- [ ] Parcours simples (avancés après P62), attribution
- [ ] Newsletter liée au blog (§48)
- [ ] Communication center et conversations (§456, §457)
- [ ] Tests : destinataire supprimé jamais contacté (§330), version de consentement, limitation d'envoi

### P41 — Service et case management
`STANDARD` · Cahier : §56, §273, §274, §454, §474
- [ ] Cases : files, affectation, priorité, SLA sur heures ouvrées (P07), escalade, preuves, tâches, communications, résolution
- [ ] Création de case depuis un email entrant
- [ ] Cases automatiques sur écarts finance, paiement, intégration
- [ ] Customer success : santé, renouvellement, risque, signaux non décisionnels (§454)
- [ ] Tests : minuteries SLA avec horloge et heures ouvrées, escalade, isolation

### P42 — Collaboration et temps réel
`STANDARD` · Cahier : §60, §126, §458–§460
- [ ] Activation de Reverb (§4.2)
- [ ] Tâches complètes, commentaires avec historique et modération, mentions, présence
- [ ] Flux d'activité (read model), calendrier partagé, notifications en direct
- [ ] Tests : commentaire privé absent de la recherche et du RAG (§460) ; mention sans fuite d'accès ; le temps réel n'est jamais source de vérité

### P43 — Community
`EXTENSIBLE` · Cahier : §59
- [ ] Profils, groupes, forums, sujets, commentaires, avis, réactions, signalements
- [ ] Modération manuelle, par règles, assistée par IA, avec appel et audit
- [ ] Tests : file de modération, limitation des abus, XSS, utilisateurs bloqués

### P44 — LMS
`EXTENSIBLE` · Cahier : §58, §253, §311
- [ ] Cours, modules, leçons (contenu P17), quiz, devoirs, inscriptions payantes, progression, présence
- [ ] Certificats PDF avec QR de vérification (P16)
- [ ] Tests : intégrité de la progression, vérification de certificat, inscription payée

### P45 — DMS, contrats, e-signature, OCR
`VITAL` · Cahier : §51, §112–§115, §428, §429
- [ ] DMS : dossiers, permissions, partage, versions, legal hold, aperçu
- [ ] Contrats complets : clauses, obligations, renouvellement, alertes d'expiration
- [ ] Signature électronique via adaptateur (niveaux eIDAS), archivage des preuves
- [ ] OCR avec revue humaine : brut, extrait, validé, preuve source
- [ ] Factures fournisseurs extraites par OCR en brouillon, jamais comptabilisées automatiquement (§429)
- [ ] Tests : hash de preuve, ordre des signataires, seuil de confiance vers revue humaine

### P46 — PIM, catalogues, promotions
`VITAL` · Cahier : §93–§96
- [ ] Variantes, attributs, jeux d'attributs, catégories, marques, médias, traductions, bundles, relations
- [ ] Catalogues multiples par canal, locale, B2B, B2C, région
- [ ] Tarification complète : segments, paliers de quantité, prix contractuels
- [ ] Promotions : éligibilité, priorité, cumul, limites, compteurs d'usage
- [ ] Tests : course sur un coupon (§215), propriétés de cumul, snapshot de prix

### P47 — Inventaire (stock ledger)
`VITAL` · Cahier : §73, §74, §76, §77, §160, §235, §434, §449
- [ ] Emplacements, ledger de mouvements append-only, disponibilité reconstructible
- [ ] Réservations (§449), transferts, ajustements, inventaires, lots, séries, péremption
- [ ] Réapprovisionnement : min/max, point de commande, délai
- [ ] Méthode de valorisation et comptabilisation (P25)
- [ ] Tests : survente concurrente, formule de rapprochement (§434), reconstruction = état courant

### P48 — Commerce : panier, checkout, commandes, retours
`VITAL` · Cahier : §97–§99, §121, §222, §238, §307, §450–§452
- [ ] Boutique sur les sites (P18), panier avec snapshots, checkout idempotent et validé
- [ ] Commandes en state machine, préparation, transporteurs via adaptateur, étiquettes, suivi
- [ ] Retours, remboursements, remise en stock ; saga commande → paiement → stock → expédition → facture
- [ ] Tests : falsification (§222), checkout idempotent, compensation de saga, survente

### P49 — Abonnements clients
`VITAL` · Cahier : §100
- [ ] Plans, prix, cycles, usage, factures récurrentes (P26), retries, relances
- [ ] Pause, reprise, résiliation, changement de plan avec prorata ; mandats SEPA
- [ ] Tests : propriétés du prorata, renouvellement idempotent (scheduler rejoué), relances

### P50 — WMS et portail entrepôt
`STANDARD` · Cahier : §75, §177, §181
- [ ] Réception, rangement, déplacement, prélèvement, emballage, expédition, inventaire, transfert, cross-dock
- [ ] Scan codes-barres et QR (§181) sur mobile ; portail entrepôt
- [ ] Tests : prélèvement concurrent, parcours de scan sur mobile

### P51 — Achats et portail fournisseur
`VITAL` · Cahier : §71, §72, §78, §79, §175, §234, §316
- [ ] Demandes d'achat, appels d'offres, devis fournisseurs, sélection, bons de commande, réceptions (P47)
- [ ] Rapprochement à trois voies avec tolérance, case sur écart (P41)
- [ ] Coûts d'approche (§78), stock en consignation (§79), portail fournisseur
- [ ] Tests : cas de tolérance, répartition des coûts d'approche sans perte, SoD

### P52 — Booking et ressources
`VITAL` · Cahier : §107, §108, §159, §223, §239, §303, §403, §436, §447, §448
- [ ] Services, ressources et groupes, personnel, salles, équipements, capacités
- [ ] Disponibilités (heures ouvrées, jours fériés), créneaux
- [ ] Réservation protégée par contrainte d'exclusion PostgreSQL sur `tstzrange` par ressource (§159)
- [ ] Acompte (P27), politiques d'annulation, widget public sur les sites, calendrier et AvailabilityGrid
- [ ] Rapprochement réservations ↔ paiements ↔ capacité (§436)
- [ ] Tests : deux réservations simultanées, une seule réussit avec `BOOKING_CONFLICT` (§403) ; exploitation de fuseau (§223) ; contournement de capacité

### P53 — Événements et billetterie
`VITAL` · Cahier : §109, §110
- [ ] Événements, lieux, sessions, intervenants, types de billets, places ou capacité
- [ ] Réservation temporaire avec expiration, paiement, billets QR signés, check-in, transfert, remboursement
- [ ] Tests : survente de places en concurrence, QR falsifié, double check-in

### P54 — Adhésions et dons
`STANDARD` · Cahier : §111, §254, §312
- [ ] Plans d'adhésion, inscriptions, renouvellements, cotisations, règles d'accès aux contenus, expiration
- [ ] Donateurs, campagnes, dons ponctuels et récurrents (P49), fonds, bénévoles, subventions
- [ ] Reçus fiscaux français numérotés
- [ ] Tests : numérotation des reçus, expiration de l'accès, échec d'un don récurrent

### P55 — POS et mode offline
`VITAL` · Cahier : §101, §102, §179–§181, §244
- [ ] Appareils, caisses, sessions caissier, tickets, paiements via TPE, mouvements d'espèces, clôtures
- [ ] Exigences légales françaises des logiciels de caisse (inaltérabilité, sécurisation, conservation, archivage) à revérifier au démarrage
- [ ] PWA offline : journal d'opérations, matrice autorisé / interdit / à rapprocher, stratégies de conflit (§180)
- [ ] Tests : rejeu offline idempotent, conflits, rapprochement des clôtures

### P56 — Marketplace et portail vendeur
`VITAL` · Cahier : §103, §176, §256, §313, §437
- [ ] Onboarding vendeurs et KYC via le fournisseur de paiement
- [ ] Catalogues vendeurs, éclatement des commandes, commissions, frais plateforme, reversements, litiges, modération, portail vendeur
- [ ] Séparation conceptuelle des fonds vendeurs et plateforme
- [ ] Tests : propriétés des commissions, rapprochement des reversements (§437)

### P57 — Multi-devises, consolidation, intercompany
`VITAL` · Cahier : §64, §314, §438
- [ ] Taux de change avec source et date ; montants transaction, fonctionnel, reporting ; réévaluation
- [ ] Groupes multi-entités, transactions intercompany et éliminations, consolidation, administration déléguée
- [ ] Tests : arrondis (§438), éliminations équilibrées

### P58 — Immobilisations, maintenance, flotte
`STANDARD` · Cahier : §84–§86
- [ ] Registre des immobilisations, plans d'amortissement, comptabilisation, transferts, cessions
- [ ] Maintenance préventive et corrective, ordres de travail, techniciens, pièces (P47), main-d'œuvre
- [ ] Flotte : véhicules, assurance, kilométrage, carburant, conducteurs, affectations
- [ ] Tests : jeux de référence d'amortissement, cycle d'un ordre de travail

### P59 — Production, MRP, qualité
`VITAL` · Cahier : §80–§83, §236, §308
- [ ] Nomenclatures versionnées, gammes, postes de charge, ordres de production en state machine
- [ ] Calcul MRP (besoins bruts → nets → ordres planifiés), capacité, ordonnancement
- [ ] Contrôles qualité, non-conformités, actions correctives et préventives, coûts de revient
- [ ] Tests : jeux MRP connus, version de nomenclature figée sur l'ordre, consommation dans le ledger

### P60 — RH / HCM et portail employé
`VITAL` · Cahier : §89, §90, §174, §317
- [ ] Salariés (Party), relations de travail, contrats, postes, managers, présences, congés, notes de frais (P30), évaluations, formation (P44), documents RH
- [ ] Compartiments de confidentialité (§90) avec policies dédiées ; portail employé
- [ ] Tests : un manager ne voit pas la paie ; règles de solde de congés du pack de juridiction

### P61 — Paie
`VITAL` · Cahier : §91, §92, §162, §237, §435, D6
- [ ] Étape 1 : export des variables de paie vers un logiciel de paie externe
- [ ] Étape 2 : moteur interne par pack de juridiction, validé par un expert
- [ ] Verrou par Legal Entity et période, bulletins (P16), fichiers de virement SEPA, comptabilisation (P25), déclarations
- [ ] Tests : double calcul impossible en concurrence (§162), exemples connus du pack, rapprochement (§435)

### P62 — Moteur d'automatisation
`VITAL` · Cahier : §120, §442, §480–§482
- [ ] Déclencheurs, conditions, branches, attentes, boucles bornées, tâches humaines
- [ ] Commandes soumises aux policies, notifications, webhooks, retry, timeout, compensation
- [ ] Versionnage des définitions, limites de sécurité, éditeur visuel
- [ ] Tests : garde de récursion, nombre maximal d'exécutions, version figée par exécution

### P63 — Champs personnalisés et no-code
`STANDARD` · Cahier : §186–§188
- [ ] Champs personnalisés typés avec validation, visibilité, indexabilité, classification, éligibilité IA
- [ ] Types de contenu personnalisés ; vues, formulaires, tableaux de bord et menus configurables
- [ ] Garde-fous : aucun invariant critique désactivable (§188)
- [ ] Tests : un champ personnalisé ne contourne aucun invariant de facture ; éligibilité recherche et IA respectée

### P64 — Extensions et SDK
`VITAL` · Cahier : §168–§171, §344
- [ ] Manifeste, compatibilité, permissions, capabilities, cycle de vie complet, nettoyage des données
- [ ] Contrats SDK : champ, bloc, type de contenu, fournisseurs paiement, réservation, taxe, recherche, outil IA, agent, générateur de documents, canal, rapport, widget
- [ ] Tests : une extension malveillante de test ne contourne ni l'isolation, ni les policies, ni l'audit

### P65 — Portails partenaire et intervention terrain
`STANDARD` · Cahier : §172, §178
- [ ] Portail partenaire ; interventions terrain : missions, tournées, checklists, photos (P14), signature, pièces, temps
- [ ] Mobile et offline (infrastructure P55)
- [ ] Tests : synchronisation offline, preuve de signature

### P66 — UX avancée, vues 360, espagnol et arabe
`STANDARD` · Cahier : §32, §33, §184, §185, §315–§319, §388–§391, §461–§463, §467
- [ ] Vues 360 (client, fournisseur, salarié, produit, projet, contenu, document) en read models
- [ ] Command center et palette de commandes, raccourcis clavier documentés, densités, mises en page mobile et tablette
- [ ] Locales `es` et `ar` complètes, RTL avec régression visuelle (§220)
- [ ] Tests : permissions par section des vues 360, baselines visuelles RTL

### P67 — Packs verticaux
`STANDARD` · Cahier : §165, §166, §245–§256, §305–§313
- [ ] Un template versionné et ses ajouts de domaine par vertical : commerce de détail, restaurant, hôtel, immobilier, santé, logistique, construction, automobile, éducation, associatif, marketplace, SaaS par abonnement, événementiel, distribution, industrie
- [ ] Le pack santé exige un hébergement certifié HDS en France ; aucune conformité sans pack validé (§249)
- [ ] Test d'acceptance §305 à §313 par vertical
- [ ] Chaque vertical reçoit sa propre spécification de passe à sa planification

### P68 — Juridictions supplémentaires
`VITAL` · Cahier : §314, §444–§446, D4
- [ ] Packs Espagne, Maroc, Émirats arabes unis : fiscalité, facturation électronique, obligations documentaires
- [ ] Packs de paie séparés, validés par juridiction
- [ ] Tests : exemples connus et dates d'effet par pack (§445)

### P69 — Excellence opérationnelle
`VITAL` · Cahier : §189–§196, §213–§228, §363–§370, §419–§421
- [ ] Campagnes de mutation sur les domaines VITAL, extension des tests de propriété
- [ ] Soak tests, budgets de performance par scénario, matrice navigateurs complète (§218), régression visuelle complète (§220)
- [ ] Exercices de reprise après sinistre, préparation multi-région (§369), mise à l'échelle de la base (§370)
- [ ] Tous les runbooks (§196), tableau de bord d'observabilité (§363), gestion d'incident et postmortems (§365, §366)
- [ ] Smoke métier et universel (§419, §420)

### G2 — Gate V1 absolue

- [ ] P00 à P69 acceptées
- [ ] Définition §394 vérifiée pour chaque contexte de §503
- [ ] Gate §395 entièrement vert
- [ ] Walking skeleton §346 et fils verticaux §347 démontrés
- [ ] Acceptances §305 à §319 vertes
- [ ] Definition of Done de release §345

---

## 6. Traçabilité §503 → passes

| Contexte | Passes | Contexte | Passes |
|:--|:--|:--|:--|
| Platform | P00, P09, P10 | Payroll | P61 |
| Identity | socle existant, P05 | Commerce | P48 |
| Tenancy | P01, P02 | PIM | P22, P46 |
| Organization | P13 | Pricing | P22, P46 |
| Party | P12 | Subscriptions | P49 |
| MDM | P12 | POS | P55 |
| Policy | P05, P15 | Marketplace | P56 |
| Channel | P18 | Payments | P27 |
| Localization | P07, P66 | Booking | P52 |
| Jurisdiction | P23, P68 | Events | P53 |
| CMS | P17 | Ticketing | P53 |
| Media | P14 | Membership | P54 |
| SEO | P18 | Documents | P16, P45 |
| CRM | P21 | Contracts | P24, P45 |
| Marketing | P40 | Signatures | P24, P45 |
| Service | P41 | OCR | P45 |
| Community | P43 | Workflow | P15 |
| Knowledge | P20 | Approval | P15 |
| LMS | P44 | Automation | P62 |
| Collaboration | P29, P42 | Notifications | P11 |
| Finance | P25, P26, P28, P57 | Search | P32 |
| Tax | P23 | Analytics | P33 |
| Procurement | P51 | API | P34 |
| Inventory | P47 | Integration Hub | P34 |
| WMS | P50 | AI | P35 |
| Manufacturing | P59 | Audit | P03 |
| MRP | P59 | Compliance | P36 |
| Quality | P59 | Extensions | P64 |
| Assets | P58 | Business Templates | P37, P67 |
| Maintenance | P58 | Portals | P31, P50, P51, P56, P60, P65 |
| Fleet | P58 | Offline | P55, P65 |
| Projects, PSA, Timesheets | P29 | Reporting | P33 |
| HR | P60 | Observability | P03, P39, P69 |
| | | DR | P39, P69 |

---

## 7. Traçabilité des sections du cahier → passes

| Sections | Passes |
|:--|:--|
| §0–§15 (statut, règles, stack, architecture, identifiants) | P00 et toutes les passes |
| §16–§19 Tenancy, RLS | P01, P02 |
| §20–§21 Identity, authentification | socle existant, P05, P06 |
| §22–§26 Party, MDM | P12 |
| §27 Organization | P13 |
| §28–§30 Policy, SoD, capabilities | P05, P15 |
| §31 Channel | P18 |
| §32–§34 Localisation, RTL, temps | P07, P66 |
| §35–§36 Money, Quantity | P08 |
| §37–§43 ManeUI, accessibilité | P04, P66 |
| §44–§49 CMS, page builder, blog, SEO | P17, P18, P19, P20 |
| §50 Media | P14 |
| §51 DMS | P45 |
| §52–§54 CRM, ventes | P21, P24 |
| §55 Marketing | P40 |
| §56 Service | P41 |
| §57 Knowledge | P20 |
| §58 LMS | P44 |
| §59 Community | P43 |
| §60 Collaboration | P29, P42 |
| §61–§65 Finance | P25, P57 |
| §66 AP | P30 |
| §67 AR | P26 |
| §68 Banking | P28 |
| §69 Tax | P23 |
| §70 E-invoicing | P26 |
| §71–§72, §78–§79 Procurement | P51 |
| §73–§77 Inventory, WMS | P47, P50 |
| §80–§83 Manufacturing, quality | P59 |
| §84–§86 Assets, maintenance, fleet | P58 |
| §87–§88 Projects, timesheets | P29 |
| §89–§90 HR | P60 |
| §91–§92 Payroll | P61 |
| §93–§96 PIM, pricing, promotions | P22, P46 |
| §97–§99 Cart, checkout, orders | P48 |
| §100 Subscriptions | P49 |
| §101–§102 POS | P55 |
| §103 Marketplace | P56 |
| §104–§106 Payments | P27 |
| §107–§108 Booking | P52 |
| §109–§110 Events, ticketing | P53 |
| §111 Membership | P54 |
| §112–§115 Documents, contracts, signature, OCR | P16, P24, P45 |
| §116–§119, §121 Workflow, approval, saga | P15 |
| §120 Automation | P62 |
| §122–§124 Outbox, inbox, retry | P10 |
| §125 Notifications | P11 |
| §126 Realtime | P42 |
| §127–§130 Search | P32 |
| §131–§135 Analytics, BI, rapports | P33 |
| §136–§143 API, webhooks, intégrations, import, export | P34 |
| §144–§152 AI | P35 |
| §153–§154 Audit | P03 |
| §155–§156 Compliance, classification | P36 |
| §157 Snapshots | P22–P26 et passes transactionnelles |
| §158–§162 Concurrence | passe du domaine concerné |
| §163–§164 Configuration | P09 |
| §165–§167 Templates | P37, P67 |
| §168–§171 Extensions, SDK | P64 |
| §172–§178 Portails | P31, P50, P51, P56, P60, P65 |
| §179–§181 Offline, codes-barres | P50, P55 |
| §182 Business day | P07 |
| §183 Rapprochement transverse | P28 et passes concernées |
| §184–§185 Timeline, 360 | P12, P21, P66 |
| §186–§188 Custom fields, no-code | P63 |
| §189–§197 Performance, observabilité, sauvegardes, runbooks, release | P39, P69 |
| §198 Migrations | DoD commune |
| §199 Feature flags | P09 |
| §200–§212 Dépendances, qualité, PHPStan, Pint, Rector | P00 |
| §213–§228 Stratégies de test | DoD commune, P39, P69 |
| §229–§230 Standard d'un domaine | DoD commune |
| §231–§244 Recettes | passe du domaine concerné |
| §245–§256 Packs verticaux | P67 |
| §257–§260 Universalité, master data, lignage | P12, P33 |
| §261–§262 Cache, contexte de session | P01, P09 |
| §263–§264 Erreurs | P03 |
| §265–§266 Limites d'API, sécurité webhooks | P27, P34 |
| §267–§270 Secrets, crypto, break-glass, en-têtes | P09, P39 |
| §271–§272 Fichiers | P14 |
| §273–§277 Cases, qualité, dédoublonnage, rapprochement | P12, P28, P41 |
| §278–§284 Séparations, Livewire, TallStackUI, Flux | P00, P04, DoD commune |
| §285–§287 Documentation, ADR | P00, DoD commune |
| §288–§292 CI, workflow local, scripts, groupes | P00 |
| §293–§303 Tiers, données et infrastructure de test | DoD commune, P00 |
| §304–§319 Maîtrise et acceptances | G1, G2, P67 |
| §320–§325 Pannes, idempotence, corrélation | DoD commune, P03, P10 |
| §326–§330 Rétention, consentement, emails | P36, P40 |
| §331 Templates email | P11 |
| §332–§334 Numérotation, PDF | P16 |
| §335–§337 Traduction, SEO multilingue | P18, P35 |
| §338–§345 Definitions of Done | DoD commune, passes concernées |
| §346–§356 Walking skeleton, fils verticaux, règles inter-domaines | G2, DoD commune |
| §357–§362 Commandes, queries, projections, cache, files | P10, DoD commune |
| §363–§370 Opérations | P39, P69 |
| §371–§375 Propriété des tables, contraintes | P00, DoD commune |
| §376–§379 Sécurité API, OAuth, admin, export | P05, P34 |
| §380–§387 Gouvernance IA, recherche, analytics | P32, P33, P35 |
| §388–§393 UX mobile, tablette, desktop, erreurs, async | P04, P10, P66 |
| §394–§395 Définition V1, gate | G1, G2 |
| §396–§401 Contrats de revue | P00 (modèle de PR), DoD commune |
| §402–§407 Exemples | tests des passes concernées |
| §408–§421 Staging, déploiement, smoke | P39, P69 |
| §422–§425 Réparation, actions admin, opérations de masse | P04, P06 |
| §426–§429 Imports | P34, P45 |
| §430–§439 Classement, rapprochements | P32 et passes concernées |
| §440–§446 Versionnage, juridictions | P09, P15, P23, P37, P62, P68 |
| §447–§453 Heures ouvrées, capacité, réservations, expédition, retours | P07, P27, P47, P48, P52 |
| §454–§460 Customer success, notifications, conversations, commentaires | P11, P40, P41, P42 |
| §461–§472 Command center, navigation, états, thème, impression, rendu | P04, P16, P66 |
| §473 Audit UI | P03 |
| §474 Case UI | P41 |
| §475–§479 Santé, support, impersonation, mode incident | P06, P39 |
| §480–§488 Sécurité des automatisations, tempêtes, volumétrie, pagination | P10, P32, P34, P62 |
| §489–§491 Migration de données, seeds, démo | P34, P37 |
| §492–§499 Par défaut, tests critiques | DoD commune |
| §500–§506 Définition finale | G2 |

---

## 8. Dépendances à approuver (AGENTS.md : aucune dépendance sans accord)

| Besoin | Passe | Candidat à évaluer |
|:--|:--|:--|
| Feature flags | P09 | `laravel/pennant` |
| Traitement d'image | P14 | `intervention/image` |
| Texte riche et assainissement HTML | P17 | éditeur à choisir, `symfony/html-sanitizer` |
| PDF et PDF/A-3 | P16, P26 | à évaluer selon Factur-X |
| Factur-X | P26 | à évaluer |
| Paiements | P27 | SDK du fournisseur retenu |
| OpenAPI | P34 | générateur à choisir |
| XLSX | P34 | `openspout/openspout` |
| Graphiques | P33 | à choisir |
| IA | P35 | `laravel/ai` |
| Facturation SaaS | P38 | `laravel/cashier` |

---

## 9. Questions ouvertes globales

| # | Question | Bloque |
|:--|:--|:--|
| Q1 | Valider ou modifier les décisions D1 à D7 (cahier §0.1) | toute la feuille de route |
| Q2 | Hébergeur et région (UE recommandée pour le RGPD) | P18, P39 |
| Q3 | Domaine de la plateforme pour les sous-domaines des sites | P18 |
| Q4 | Fournisseur d'email transactionnel | P11 |
| Q5 | Fournisseur de paiement et modèle de connexion des tenants | P27 |
| Q6 | Plateforme Agréée pour la facture électronique | P26 |
| Q7 | Expert-comptable pour valider TVA, mentions et FEC | P23, P25, P26 |
| Q8 | Fournisseurs d'IA autorisés et stockage vectoriel | P35 |
| Q9 | Politique antivirus des uploads en V1.0 | P14 |
| Q10 | Ouverture de l'agrégation bancaire en V1.0 | P28 |
