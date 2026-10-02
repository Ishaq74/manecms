# MANECMS : CAHIER DES CHARGES UNIVERSAL BUSINESS, CONTENT, COMMERCE, OPERATIONS, KNOWLEDGE, AUTOMATION & AI OPERATING SYSTEM

---

# 0. STATUT DU DOCUMENT

**Produit :** ManeCMS
**Version cible :** V1 intégrale production-grade
**Statut :** spécification normative de référence
**Nature :** produit + architecture + données + UX + sécurité + tests + exploitation + recette
**Philosophie :**

```text
ONE PLATFORM
ANY PERSON
ANY ORGANIZATION
ANY LANGUAGE
ANY CHANNEL
ANY BUSINESS
```

ManeCMS n'est pas un CMS auquel viennent se greffer des plugins métiers.

ManeCMS est une plateforme métier universelle dans laquelle :

```text
CMS
CRM
ERP
COMMERCE
PAYMENTS
BOOKINGS
COMMUNITY
COLLABORATION
KNOWLEDGE
PROJECTS
HR
PAYROLL
OPERATIONS
ANALYTICS
AUTOMATION
INTEGRATIONS
AI
```

partagent les mêmes primitives fondamentales :

```text
Identity
Tenant
Workspace
Organization
Legal Entity
Party
Master Data
Policy
Channel
Money
Quantity
Time
Documents
Events
Transactions
Audit
Search
Analytics
AI
```


## 0.1 Décisions produit (provisoires, à valider par le product owner)

Décisions prises par défaut le 2026-10-01 pour débloquer la planification. Toute révision met à jour ce tableau, `todo/roadmap.md` et l'ADR concerné.

| ID | Sujet | Décision provisoire | Conséquence |
|:--|:--|:--|:--|
| D1 | Distribution | SaaS hébergé multi-tenant, opéré par l'éditeur | RLS obligatoire (§17), back-office opérateur (§20.2), abonnement plateforme |
| D2 | Releases | V1.0 commercialisable, puis trains V1.x jusqu'à la V1 absolue (§503) | §2.1 |
| D3 | Premier segment | Freelance, agence, services professionnels (§255, §306) | Fil V1.0 : site → CRM → devis → projet → temps → facture → paiement → comptabilité |
| D4 | Juridiction initiale | France : TVA, facturation électronique 2026, PCG | Autres pays en V1.x ; §446 s'applique |
| D5 | Sites publics | Servis par ManeCMS : sous-domaine plateforme et domaines personnalisés | §44.1 |
| D6 | Paie | Hors V1.0 : export vers un logiciel de paie d'abord, moteur interne en V1.x par juridiction | §91 |
| D7 | Langues | Interface `fr` par défaut + `en` ; code, base et identifiants en anglais ; `es` et `ar` (RTL) en V1.x | §32 |

---

# 1. RÈGLES NORMATIVES

Les mots suivants ont une signification contractuelle.

```text
DOIT
→ obligation V1.

NE DOIT PAS
→ interdiction V1.

DEVRAIT
→ règle forte ; dérogation documentée.

PEUT
→ capacité optionnelle mais compatible avec l'architecture.
```

Une exigence n'est pas considérée comme réalisée parce que du code existe.

Une exigence n'est considérée comme **Verified** que si elle possède une preuve :

```text
Automated Test
ou
Browser Test
ou
Security Test
ou
Architecture Test
ou
Performance Evidence
ou
Manual Acceptance Evidence
```

---

# 2. PRINCIPE ABSOLU DE V1

V1 n'est pas :

```text
V1 CMS
V2 CRM
V3 ERP
V4 Commerce
```

V1 est :

```text
                 MANECMS V1
                     │
       ┌─────────────┼─────────────┐
       │             │             │
      CMS           CRM           ERP
       │             │             │
       ├─────────────┼─────────────┤
       │             │             │
   Commerce       Payments      Bookings
       │             │             │
       ├─────────────┼─────────────┤
       │             │             │
 Documents      Projects         HR
       │             │             │
       ├─────────────┼─────────────┤
       │             │             │
 Automation     Analytics          AI
```

Toutes les capacités fondamentales doivent exister dans la même V1.


## 2.1 Trains de release et passes (amendement D2)

Le principe 2 décrit la **V1 absolue** : tous les contextes de §503. Elle est atteinte par trains :

- **V1.0** : noyau universel + premier segment (D3), publiable commercialement ;
- **V1.x** : chaque train ajoute des contextes de §503 sans changer l'architecture ni casser les contrats publiés ;
- **V1 absolue** : tous les contextes de §503 vérifiés, gates §394 et §395 verts.

Construction :

- le travail est découpé en passes `P00` à `P69`, définies dans `todo/roadmap.md` : dépendances, livrables, invariants, tests et acceptance ;
- avant de démarrer une passe, sa spécification détaillée est écrite dans `todo/pass-XX-*.md` selon le gabarit de la feuille de route, et ses questions ouvertes sont tranchées ;
- une passe ne démarre que lorsque ses dépendances sont acceptées ;
- une passe ne contient que ce dont elle a besoin ; tout le reste est reporté vers une passe identifiée ;
- toute décision d'architecture prise dans une passe est reportée dans ce cahier et dans un ADR.

Les primitives transversales (tenancy, isolation, audit, policy, localisation, temps, argent, événements) précèdent les domaines qui les consomment.

---

# 3. OBJECTIF PRODUIT

ManeCMS doit permettre :

```text
à un particulier
à un freelance
à un artisan
à une association
à une PME
à une ETI
à un groupe
à un industriel
à un commerçant
à un hôtel
à un restaurant
à une école
à une marketplace
à une agence
à un cabinet
à une société de services
à une organisation internationale
```

de gérer dans une seule plateforme :

```text
identités
organisation
site web
contenu
clients
prospects
ventes
produits
services
prix
stocks
achats
production
qualité
maintenance
projets
RH
paie
facturation
paiements
banque
réservations
événements
tickets
documents
contrats
signature
marketing
support
communauté
knowledge base
automatisations
reporting
analytics
IA
intégrations
```

---

# 4. ENVIRONNEMENT DE DÉVELOPPEMENT — LARAVEL HERD

## 4.1 Environnement local

Le développement local officiel de ManeCMS est :

```text
Laravel Herd
        │
        ├── PHP
        ├── Composer
        ├── Laravel
        ├── PostgreSQL
        ├── Redis
        ├── Meilisearch
        ├── MinIO / S3-compatible
        └── Reverb
```

Le projet DOIT être facilement exploitable depuis Herd sans stack Docker obligatoire pour le développement quotidien.

Docker/containers PEUVENT exister pour CI, staging ou reproductibilité infrastructure.


## 4.2 Activation progressive des services

PostgreSQL est obligatoire dès J0. Les autres services sont activés par la passe qui en a besoin, jamais avant.

| Service | Activé par | Avant activation |
|:--|:--|:--|
| Redis + Horizon | passe Outbox / Queue, ou besoin de cache mesuré | queue `database`, cache `database`, session `database` |
| Meilisearch | passe Search | aucune recherche full-text |
| S3 / MinIO | déploiement ou volumétrie media | disque `local` privé |
| Reverb | passe Realtime | aucun temps réel |

Un service activé arrive avec sa configuration `.env.example`, ses tests et son runbook.

---

# 5. INITIALISATION DU PROJET

## 5.1 Bootstrap

Point de départ :

```bash
laravel new manecms
cd manecms
```

Le projet utilise le Starter Kit officiel Livewire comme bootstrap d'authentification et de structure initiale.

Ensuite :

```bash
composer require livewire/livewire
composer require tallstackui/tallstackui
```

Les versions exactes sont verrouillées par `composer.lock`.

---

# 6. RÈGLE ABSOLUE UI

Le socle officiel ManeCMS est :

```text
Blade
+
Livewire
+
Tailwind CSS
+
TallStackUI
+
ManeUI
+
Alpine.js
```

**Flux UI est interdit dans le cœur ManeCMS.**

Aucune couche de présentation ne doit introduire Flux comme dépendance concurrente.

---

# 7. RÔLE DES TECHNOLOGIES FRONTEND

## 7.1 Blade

Blade est utilisé pour :

```text
pages
layouts
partials
emails
documents
SSR
fragments
```

## 7.2 Livewire

Livewire est la couche applicative interactive principale.

Utilisation :

```text
CRUD
forms
filters
tables
dialogs
drawers
dashboards
wizards
search
workflow
admin
portals
back-office
```

## 7.3 TallStackUI

TallStackUI est la librairie UI de référence.

ManeCMS PEUT encapsuler TallStackUI dans ManeUI mais ne doit pas créer des variantes visuelles incohérentes.

## 7.4 ManeUI

ManeUI est le design system produit.

```text
TallStackUI
     ↓
ManeUI
     ↓
Application
```

Les développeurs métier consomment principalement ManeUI.

## 7.5 Alpine.js

Alpine est utilisé uniquement pour les comportements légers côté client.

Exemples :

```text
toggle
dropdown
focus
shortcut
clipboard
tiny local state
transition
```

Alpine ne doit pas devenir un second framework d'application parallèle à Livewire.

---

# 8. STACK TECHNIQUE FINAL

```text
Backend
Laravel
PHP 8.4+

Application UI
Livewire 4
Blade
Tailwind CSS 4
TallStackUI 4
ManeUI
Alpine.js

Database
PostgreSQL

Cache
Redis

Queue
Redis + Horizon

Storage
S3-compatible

Search
Laravel Scout
Meilisearch

Realtime
Laravel Reverb

AI
Laravel AI SDK
Provider adapters

Auth
Laravel Authentication
Sanctum
Passport si OAuth2 requis

Testing
Pest 5
PHPStan
Larastan
Strict Rules
Pest Type Coverage
Pest Architecture
Pest Browser
Pest Stressless
Pest Agent / Evals
Pest Drift
Rector
Pint
PHP Insights

Documentation
ADR
MADR
C4
Mermaid
OpenAPI
```

---

# 9. PHILOSOPHIE D'ARCHITECTURE

ManeCMS utilise :

```text
Modular Monolith
+
DDD pragmatique
+
Bounded Contexts
+
Ports & Adapters
+
CQRS-lite
+
Domain Events
+
Outbox
+
Inbox
+
Process Managers
+
Saga
+
Read Models
```

Le monolithe modulaire est la structure cible de V1.

La distribution en microservices n'est pas une condition du produit.

---

# 10. RÈGLE DE PROPRIÉTÉ

Chaque concept métier possède :

```text
un owner
une responsabilité
des tables propriétaires
des commandes propriétaires
des invariants propriétaires
des événements propriétaires
```

Exemple :

```text
Invoice
→ Finance

Payment
→ Payments

Booking
→ Booking

Product master
→ Commerce/PIM

Stock
→ Inventory

Employee relationship
→ HR

Payroll Run
→ Payroll
```

Un domaine consommateur ne modifie pas directement les tables d'un autre.

---

# 11. BOUNDED CONTEXTS V1

```text
PLATFORM
├── Kernel
├── Identity
├── Tenancy
├── Organization
├── Party
├── Master Data
├── Policy
├── Channel
├── Localization
├── Jurisdiction
├── Configuration
└── Compliance

EXPERIENCE
├── CMS
├── Media
├── SEO
├── CRM
├── Marketing
├── Service
├── Community
├── Knowledge
└── Collaboration

OPERATIONS
├── Finance
├── Procurement
├── Inventory
├── WMS
├── Manufacturing
├── Quality
├── Assets
├── Maintenance
├── Fleet
├── Projects
├── PSA
├── Timesheets
├── HR
└── Payroll

TRANSACTIONAL
├── Commerce
├── PIM
├── Pricing
├── Payments
├── Booking
├── Events
├── Ticketing
├── Membership
├── Documents
├── Contracts
└── Signatures

TRANSVERSAL
├── Workflow
├── Approval
├── Automation
├── Notifications
├── Search
├── Analytics
├── API
├── Integration Hub
├── AI
├── Audit
├── Extensions
└── Reporting
```


## 11.1 Nommage canonique

La liste du §11 fait foi ; l'arborescence du §13 s'y aligne. Chaque contexte vit dans `App\Domain\<Context>`. Sous-modules :

- `Content` = CMS (§44 à §49) ;
- `Commerce` contient `Pim`, `Pricing`, `Subscriptions`, `Pos`, `Marketplace` ;
- `Finance` contient `Tax` ;
- `Documents` contient `Contracts`, `Signatures`, `Ocr` ;
- `Projects` contient `Psa`, `Timesheets` ;
- `Inventory` contient `Wms` ;
- `Manufacturing` contient `Mrp` ;
- `Platform` contient `Kernel`, `Configuration`.

Collisions de vocabulaire résolues :

| Terme | Sens unique | Contexte owner |
|:--|:--|:--|
| `Organization` | structure interne d'un tenant (§27) | Organization |
| `Company` | sous-type de Party : personne morale tierce (§22) | Party |
| `TenantMember` | appartenance d'un user à un tenant (§16.3) | Tenancy |
| `Membership` | adhésion à un plan (§111) | Membership |
| `EventManagement` | événements, sessions, billets (§109) | EventManagement |
| domain event | message publié via l'outbox (§122) | Platform |
| `File` | stockage, sécurité et métadonnées de fichier (§271) | Media |
| `MediaAsset` | médiathèque éditoriale : alt, droits, variantes (§50) | Media |
| DMS | bibliothèque documentaire : dossiers, versions, partage (§51) | Documents |
| `Artifact` | document généré : gabarit, rendu, preuve (§112) | Documents |
| `Task` | tâche générique (§60) ; Projects la rattache à ses phases | Collaboration |
| article de blog, article KB | types de contenu de Content ; Knowledge possède audience et visibilité KB | Content, Knowledge |
| `Case` | objet unique de suivi d'exception (§56, §273, §474) | Service |

---

# 12. CONTRAT D'UN DOMAINE

Chaque domaine DOIT posséder :

```text
context.md
```

avec :

```text
Mission
Scope
Non-goals
Owned concepts
Aggregates
Entities
Value Objects
Commands
Queries
Policies
Invariants
Transactions
Owned tables
Published events
Consumed events
Read models
Search projections
Analytics events
Permissions
Audit
Retention
Security
Observability
Tests
Acceptance
ADR
Extension points
```

---

# 13. STRUCTURE DE CODE

```text
app/
└── Domain/
    ├── Platform/
    ├── Identity/
    ├── Tenancy/
    ├── Organization/
    ├── Party/
    ├── MasterData/
    ├── Policy/
    ├── Channel/
    ├── Localization/
    ├── Jurisdiction/
    ├── Content/
    ├── CRM/
    ├── Marketing/
    ├── Service/
    ├── Finance/
    ├── Procurement/
    ├── Inventory/
    ├── Manufacturing/
    ├── Quality/
    ├── Assets/
    ├── Maintenance/
    ├── Fleet/
    ├── Projects/
    ├── HR/
    ├── Payroll/
    ├── Commerce/
    ├── Payments/
    ├── Booking/
    ├── Events/
    ├── Membership/
    ├── Documents/
    ├── Workflow/
    ├── Approval/
    ├── Automation/
    ├── Search/
    ├── Analytics/
    ├── Integrations/
    ├── AI/
    ├── Audit/
    └── Extensions/

resources/
├── views/
├── css/
└── js/

tests/
├── Unit/
├── Feature/
├── Integration/
├── Database/
├── Architecture/
├── Security/
├── Browser/
├── Performance/
├── Contract/
├── Concurrency/
├── AI/
├── Accessibility/
└── Recovery/

docs/
├── architecture/
├── adr/
├── domains/
├── api/
├── events/
├── security/
├── runbooks/
└── design-system/
```

---

# 14. UNIVERSAL GRAPH

```text
Tenant
  ↓
Workspace
  ↓
Organization
  ↓
Legal Entity
  ↓
Site / Branch
  ↓
Party
  ↓
Role / Account / Relationship
  ↓
Domain Aggregate
  ↓
Transaction
  ↓
Event
  ↓
Projection
  ├── Search
  ├── Analytics
  ├── 360
  └── AI
```

Ce graphe n'implique pas que toutes les relations soient des relations ORM directes.

---

# 15. IDENTIFIANTS

Les identifiants internes doivent être stables et non dépendre des numéros métier affichés.

Privilégier :

```text
UUID / ULID / identifiant comparable
```

pour les identités techniques.

Les numéros humains :

```text
INV-FR-2026-000123
PO-FR-2026-000004
BOOK-2026-000912
```

sont gérés par un moteur de numbering dédié.


## 15.1 Conventions de données

Ces conventions s'appliquent à toute nouvelle table. Une dérogation exige un ADR.

### Identifiants et colonnes

- Clé primaire ULID (`HasUlids`) pour toute nouvelle table métier. `users.id` reste entier (héritage Identity).
- Les URL exposent des ULID, jamais des identifiants entiers ni des numéros humains.
- `NOT NULL` par défaut. Une colonne `nullable` porte une signification métier (ex. `archived_at`).
- Dates en `timestampTz`, stockées en UTC.
- Un timestamp remplace un booléen d'état (`archived_at` plutôt que `is_archived`, jamais les deux).
- Enums : colonne `string` + enum PHP backed + cast.
- Pas de JSON pour une donnée interrogée ou soumise à invariant.
- Montants : entier en unité mineure + devise (§35).

### Cycle de vie et suppression

Trois mécanismes, non interchangeables :

1. **Archivage** (`archived_at`) : état métier réversible. L'objet sort des listes et des sélections. Défaut pour les objets structurants : tenant, workspace, organization, catalogue.
2. **Soft delete** (`deleted_at`) : réservé aux contenus avec corbeille utilisateur : pages, médias, articles. Purge définitive selon rétention.
3. **Suppression physique** : objets techniques sans valeur historique (tokens, liaisons), ou exécution d'une règle de rétention / RGPD (§326, §328), toujours auditée.

Interdit : supprimer, même logiquement, un objet financier ou légal validé. On l'annule par contre-passation.

Clés étrangères : `restrictOnDelete` par défaut. `cascadeOnDelete` uniquement pour les enfants techniques du même agrégat.

### Unicité

- Toute unicité métier est garantie par un index unique en base (§374). La validation applicative n'est qu'un confort UX.
- Une unicité scopée inclut `tenant_id` (et `workspace_id` si applicable).
- Les objets archivés ou soft-deleted conservent leur valeur unique, sauf index partiel documenté.
- Insensibilité à la casse : colonne normalisée en minuscules à l'écriture, indexée.
- Collision concurrente : l'action intercepte `UniqueConstraintViolationException` et applique sa stratégie (suffixe ou erreur de validation), jamais une erreur 500.

### Intégrité tenant

- Toute table tenant-scoped porte `tenant_id NOT NULL`, en première colonne de ses index composés.
- Une table rattachée à un workspace porte `tenant_id` et `workspace_id`, avec une clé étrangère composite `(tenant_id, workspace_id) → workspaces(tenant_id, id)` qui interdit tout rattachement croisé.

### Slugs

- Générés par le serveur, jamais saisis librement sauf champ SEO dédié.
- Format `^[a-z0-9]+(-[a-z0-9]+)*$`, 60 caractères maximum.
- Si la translittération produit une chaîne vide (arabe, emoji...), base de repli + suffixe aléatoire.
- Les slugs exposés en URL ou sous-domaine respectent une liste de valeurs réservées.
- Immuables par défaut ; un changement de slug public impose une redirection (§49).

### Validation

- Toute entrée est validée côté serveur (Form Request ou action), quelle que soit l'UI.
- Chaînes normalisées par `Str::squish` avant validation : Livewire n'applique pas `TrimStrings`.
- La règle `max` est alignée sur la taille de la colonne.
- Libellé humain : `required|string|min:2|max:120`, sauf règle de domaine.
- `tenant_id`, `workspace_id` et tout identifiant d'appartenance ne proviennent jamais de l'input : ils viennent du contexte autorisé.
- Messages traduisibles (`__()`).

---

# 16. TENANCY

## 16.1 Définitions

```text
Tenant
→ frontière d'isolation technique

Workspace
→ espace produit/configuration

Organization
→ structure métier

LegalEntity
→ entité juridique

Site
→ lieu opérationnel
```

Ces concepts ont des identifiants différents.


## 16.2 Cardinalités

```text
User ──< TenantMember >── Tenant ──< Workspace ──< Organization ──< LegalEntity ──< Site
```

- `Tenant` : compte client et frontière d'isolation (RLS, données, abonnement plateforme).
- `User` (Identity) : global, membre de plusieurs tenants via `TenantMember`.
- `Workspace` : appartient à un seul tenant. Un tenant possède toujours au moins un workspace actif.
- `Organization` : appartient à un workspace. Structure métier (§27), jamais un mécanisme d'accès.
- Les données métier portent `tenant_id` et, sauf donnée partagée au niveau tenant, `workspace_id`.

Exemples :

- freelance : 1 tenant, 1 workspace, 1 organization ;
- groupe : 1 tenant, 1 workspace par marque ou pays, n organizations et legal entities ;
- agence : 1 tenant par client géré ; l'utilisateur de l'agence est membre de chacun.

## 16.3 Appartenance et rôles de base

- L'appartenance se fait au niveau du tenant (`tenant_members`).
- Rôles de base : `owner`, `admin`, `member`. Un tenant a exactement un owner, garanti par un index unique partiel.
- Jusqu'à P05, tout membre accède à tous les workspaces de son tenant. La restriction par workspace sera une capability du Policy Engine (§28).
- Le terme `Membership` est réservé au domaine adhésions (§111) ; la tenancy utilise `TenantMember`.
- Un non-membre reçoit une réponse 404 : l'existence d'un tenant ou d'un workspace n'est jamais révélée.

## 16.4 Contexte courant

- Les pages tenant-scoped sont adressées par URL : `/w/{workspace}`. L'URL est la source du contexte ; plusieurs onglets peuvent travailler dans des workspaces différents.
- Le contexte est résolu une fois par requête, HTTP comme Livewire (middleware persistant), après vérification d'appartenance.
- Un contexte absent lève `TenantContextRequired` (§263) et la requête échoue fermée (§494).
- Le dernier workspace utilisé est persisté par appartenance pour être retrouvé après reconnexion.

---

# 17. RLS

Les tables sensibles utilisent :

```text
tenant_id
+
application scoping
+
PostgreSQL Row-Level Security
```

Les policies couvrent :

```text
SELECT
INSERT
UPDATE
DELETE
```

avec `USING` et `WITH CHECK` appropriés.

Le contexte tenant doit être explicitement installé pour :

```text
HTTP
Livewire
CLI
Scheduler
Queue
Horizon
Webhook
Import
Export
Search
AI
MCP
```

Tenant non défini :

```text
READ  → deny
WRITE → deny
```


## 17.1 Mise en œuvre PostgreSQL / Laravel

- L'application se connecte avec un rôle `NOSUPERUSER NOBYPASSRLS` qui ne possède pas les tables. Un superuser contourne toujours la RLS : le compte `root` actuel est interdit pour l'exécution applicative dès la passe RLS.
- Les migrations s'exécutent avec un rôle propriétaire distinct (connexion dédiée).
- Chaque table tenant-scoped : `ENABLE` et `FORCE ROW LEVEL SECURITY`, une policy par commande avec `USING` et `WITH CHECK`.
- Prédicat : `tenant_id = current_setting('app.tenant_id', true)`. Variable absente : `NULL`, donc aucune ligne (fail closed).
- `set_config('app.tenant_id', ...)` est appelé à l'installation de `TenantContext` (HTTP, Livewire, job, commande, scheduler) et remis à zéro en fin de requête ou de job.
- Les lectures nécessaires avant résolution du tenant (tenants d'un user) passent par une policy dédiée sur `app.user_id`.
- Un pooler en mode transaction impose `SET LOCAL` dans une transaction explicite ; ce mode est exclu tant qu'un ADR ne l'organise pas.
- Un test vérifie que chaque table possédant `tenant_id` a la RLS activée et forcée.

---

# 18. HOW-TO TENANCY

1. Identifier le tenant.
2. Ouvrir une transaction.
3. Installer le contexte.
4. Exécuter les commandes.
5. Exécuter les queries.
6. Détruire le contexte en fin de transaction.
7. Vérifier les logs et métriques.

---

# 19. VERIFY TENANCY

Tests obligatoires :

```text
Tenant A → accès A
Tenant A → aucun accès B

INSERT A → accepté
INSERT B depuis contexte A → refus

UPDATE A → accepté
UPDATE B depuis contexte A → refus

DELETE A → policy respectée
DELETE B → refus
```

Répéter pour :

```text
HTTP
Livewire
API
Queue
CLI
Search
RAG
Export
```

---

# 20. IDENTITY

Le domaine Identity gère :

```text
User
Credential
Session
MFA
Recovery
API Identity
Device
Login
Logout
Password
Passkey lorsque retenu
```


## 20.1 Suppression de compte

- Un owner d'un tenant non archivé ne peut pas supprimer son compte : il transfère d'abord la propriété ou archive le tenant.
- La suppression d'un compte retire ses appartenances, ne supprime jamais les données d'un tenant et conserve l'audit avec un acteur pseudonymisé.


## 20.2 Plateforme opérateur (D1)

- Les opérateurs de la plateforme ont un rôle plateforme, distinct des rôles de tenant.
- Le back-office opérateur (`/platform`) est séparé des workspaces, exige la MFA et est audité.
- Un opérateur n'accède aux données d'un tenant que par impersonation explicite (§477) ou break-glass (§269).

---

# 21. AUTHENTIFICATION

Support :

```text
email/password
2FA
recovery
session management
device awareness
API tokens
OAuth2 lorsque nécessaire
```

Les secrets ne sont jamais stockés en clair.

---

# 22. PARTY

`Party` représente une identité métier.

```text
Party
├── Person
├── Organization
└── Household
```

`Party` possède uniquement les données identitaires transversales.

---

# 23. PARTY ROLES

Une Party peut être :

```text
Customer
Supplier
Employee
Partner
Member
Donor
Student
Vendor
Contact
Seller
Guest
```

Les rôles sont des références, pas des dumps de données métiers.

---

# 24. MDM

Le Master Data Management gère :

```text
source
external ids
matching
duplicate detection
golden record
merge
split
survivorship
provenance
audit
suppression
```

---

# 25. HOW-TO MDM

```text
Import Party
    ↓
Normalize
    ↓
Match existing records
    ↓
No match → create
Possible match → review
Definite duplicate → merge
    ↓
Golden record
    ↓
Audit
```

---

# 26. VERIFY MDM

Tester :

```text
duplicate detection
false positive
false negative
merge
reverse/repair where supported
external references
historical transaction preservation
```

---

# 27. ORGANIZATION

Organisation :

```text
Organization
├── Business Units
├── Legal Entities
├── Branches
├── Departments
├── Teams
├── Cost Centers
└── Profit Centers
```

Les relations organisationnelles peuvent être datées.

---

# 28. POLICY ENGINE

Le Policy Engine est distinct de :

```text
RBAC
ABAC
Approval
Workflow
```

Décisions :

```text
ALLOW
ALLOW_WITH_CONSTRAINTS
DENY
REQUIRE_APPROVAL
```

Policies :

```text
CanPublish
CanRefund
CanDiscount
CanPostJournal
CanPay
CanClosePeriod
CanViewPayroll
CanExportData
CanUseAITool
CanDelete
```


## 28.1 RBAC de base

- Permission : chaîne stable `<context>.<resource>.<action>` (ex. `content.page.publish`), déclarée en code par son contexte.
- Rôle : ensemble nommé de permissions, défini par tenant ; `owner`, `admin` et `member` sont des rôles système non supprimables.
- Attribution au niveau du tenant, restreignable à des workspaces.
- Les Laravel Policies délèguent au Policy Engine ; aucun test de rôle par chaîne dans les vues ou les composants.
- Le Policy Engine combine RBAC, attributs (ABAC), capabilities (§30) et SoD (§29) et rend `ALLOW`, `ALLOW_WITH_CONSTRAINTS`, `DENY` ou `REQUIRE_APPROVAL`.
- Le code est la source de vérité des permissions : chaque contexte les déclare dans une énumération listée dans `config/authorization.php` ; `authorization:sync-permissions` (lancée aussi après chaque `migrate`) les copie en base, où le rôle applicatif ne peut que les lire.
- `is_owner` est dérivé du rôle système `owner` par trigger ; un index unique partiel garantit un owner par tenant. Personne ne devient owner hors transfert de propriété.
- Ordre d'évaluation fixe et raisons de refus stables (`DenialReason`) ; un refus sur une action est audité (`authorization.denied`) avant de lever `AUTHORIZATION_DENIED` (403). `REQUIRE_APPROVAL` lève `APPROVAL_REQUIRED` (409) jusqu'aux approbations (P15).
- Sous l'owner, personne n'attribue ni ne gère un rôle plus riche que le sien ; un admin ne gère ni l'owner ni un autre admin. Décision : `docs/adr/0003-authorization-policy-engine.md`.

---

# 29. SÉGRÉGATION DES TÂCHES

Le système doit permettre :

```text
Create
Approve
Execute
Reconcile
Close
```

d'être attribués à des acteurs différents.

Exemples :

```text
Invoice
Purchase
Payment
Refund
Payroll
Contract
```

---

# 30. CAPABILITY MODEL

```text
SAFE_CONFIG
GUARDED_CONFIG
PRIVILEGED_CONFIG
CODE_ONLY
IMMUTABLE
```

Exemples :

```text
label
view
dashboard
workflow de contenu
        → configurable

tax invariant
accounting balance
payment safety
stock invariant
        → code protected
```

---

# 31. CHANNEL

Channels :

```text
Web
Mobile
PWA
POS
Marketplace
Customer Portal
Supplier Portal
Partner Portal
Employee Portal
Warehouse
Field
Kiosk
API
AI
MCP
```

Chaque channel peut posséder :

```text
theme
locale
timezone
catalog
pricing
inventory visibility
permissions
navigation
feature flags
```

---

# 32. LOCALISATION

V1 minimum :

```text
fr-FR
en-US
es-ES
ar
```

Architecture :

```text
BCP 47
fallback chains
pluralization
number formatting
currency formatting
date formatting
timezone
RTL
translation memory
glossary
AI translation
```

---

# 33. RTL

Le système DOIT fonctionner correctement en :

```text
Arabic
Persian
Urdu
Hebrew
```

avec :

```text
CSS logical properties
direction
mirrored layout
icons contextualisés
```

---

# 34. TIME ENGINE

Primitives :

```text
Instant
LocalDate
LocalTime
ZonedDateTime
Duration
DateRange
TimeRange
BusinessDay
FiscalDate
Calendar
HolidayCalendar
BusinessHours
```

Aucune logique métier ne doit dépendre du timezone serveur par défaut.

---

# 35. MONEY

Jamais de float.

```text
Money
├── amount
├── currency
└── context
```

Préciser :

```text
transaction currency
functional currency
reporting currency
```

---

# 36. QUANTITY

```text
Quantity
+
UOM
+
conversion policy
```

Exemples :

```text
kg
g
piece
liter
meter
hour
box
pallet
```

---

# 37. DESIGN SYSTEM MANEUI

ManeUI comprend :

```text
FOUNDATIONS
TOKENS
COMPONENTS
PATTERNS
BUSINESS PATTERNS
LAYOUTS
ACCESSIBILITY
MOTION
RESPONSIVE
DARK MODE
RTL
DENSITY
```

---

# 38. DESIGN TOKENS

Tokens :

```text
color
surface
text
border
icon
spacing
radius
shadow
elevation
typography
line height
breakpoints
z-index
motion
density
```

Hiérarchie :

```text
Primitive
   ↓
Semantic
   ↓
Component
   ↓
Page
```

---

# 39. COMPOSANTS MANEUI

Minimum :

```text
Button
IconButton
Input
Textarea
Select
Combobox
MultiSelect
Checkbox
Radio
Switch
DatePicker
DateRangePicker
TimePicker
MoneyInput
QuantityInput
SearchInput
FileUpload
Avatar
Badge
Status
Alert
Toast
Tooltip
Popover
Dropdown
Modal
Drawer
Tabs
Accordion
Card
Table
DataTable
Pagination
FilterBar
FilterBuilder
CommandPalette
Breadcrumb
Tree
Timeline
Kanban
Calendar
Scheduler
ChartContainer
EmptyState
ErrorState
LoadingState
Skeleton
```

---

# 40. COMPOSANTS MÉTIER MANEUI

```text
MoneyCell
TaxBreakdown
InvoiceLinesEditor
JournalEntryEditor
ApprovalInbox
AuditTimeline
EntityHeader
Entity360
RelationshipGraph
BookingCalendar
AvailabilityGrid
StockMatrix
ReconciliationGrid
PaymentState
OrderTimeline
PurchaseMatchingPanel
TranslationMatrix
RevisionViewer
DocumentPreview
ContractVersionViewer
ImportMapper
ImportPreview
ConflictResolver
SyncStatus
OfflineQueue
ReportBuilder
QueryBuilder
AutomationBuilder
WorkflowDesigner
```

---

# 41. ÉTATS UI OBLIGATOIRES

Chaque composant significatif doit spécifier :

```text
default
hover
focus
focus-visible
active
selected
disabled
loading
error
success
warning
empty
readonly
skeleton
offline
permission denied
mobile
dark
RTL
reduced motion
```

---

# 42. DATA-DENSE UX

Les interfaces ERP/CRM doivent supporter :

```text
pagination
sorting
multi-sort
filtering
saved filters
saved views
grouping
aggregation
column resize
column reorder
column pinning
bulk actions
inline editing
keyboard navigation
export
density
responsive behavior
```

---

# 43. ACCESSIBILITY

Cible :

```text
WCAG 2.2 AA
```

Sur :

```text
Public
Admin
Customer Portal
Employee Portal
POS
Booking
Builder
RTL
Mobile
```

Tests :

```text
axe
keyboard
focus
screen reader
contrast
zoom
reflow
reduced motion
manual audit
```

---

# 44. CMS

Le domaine Content possède :

```text
Content Type
Field
Entry
Page
Article
Block
Template
Revision
Publication
Schedule
Preview
Rollback
Taxonomy
Tag
Navigation
Redirect
```


## 44.1 Sites publics (D5)

- Un workspace possède zéro ou plusieurs sites ; un site est un Channel de type Web (§31).
- Adresses : sous-domaine de la plateforme et domaines personnalisés, vérifiés par enregistrement DNS TXT, avec TLS automatique.
- Requête publique : hôte, puis site, puis tenant ; aucune session utilisateur requise ; seules les révisions publiées sont lisibles.
- Thème : layouts Blade versionnés et tokens ManeUI ; aucun code fourni par le tenant (§47).
- Cache de page invalidé à la publication (§361).
- Locales par site, `hreflang` et sitemaps (§49, §337).

---

# 45. CONTENT LIFECYCLE

```text
Draft
  ↓
Review
  ↓
Approved
  ↓
Scheduled
  ↓
Published
  ↓
Unpublished
  ↓
Archived
```

Le public lit toujours la révision publiée.

---

# 46. PAGE BUILDER

Le builder doit supporter :

```text
Canvas
Tree
Selection
Drag/drop
Nesting
Resize
Alignment
Responsive behavior
Inspector
Style controls
Bindings
Visibility conditions
Undo
Redo
History
Autosave
Preview
Accessibility
Publish
```

---

# 47. PAGE BUILDER SECURITY

Le builder ne doit jamais permettre :

```text
arbitrary PHP
arbitrary SQL
arbitrary server command
arbitrary filesystem write
```

Les composants sont whitelistés.

---

# 48. BLOG

```text
Article
Author
Category
Tag
Media
SEO
Revision
Workflow
Publication
Comments
RSS
Newsletter integration
```

---

# 49. SEO

```text
title
description
canonical
robots
OpenGraph
Twitter metadata
schema.org
hreflang
sitemap
redirects
slug
```

SEO Health :

```text
missing title
duplicate title
missing description
broken canonical
orphan content
broken links
missing alt
redirect chains
```

---

# 50. MEDIA

Le Media Platform gère :

```text
Image
Video
Audio
PDF
Office
Archive
```

Métadonnées :

```text
mime
size
checksum
owner
tenant
classification
rights
copyright
alt text
version
retention
scan status
```

Les fichiers privés ne sont jamais rendus publics par défaut.

---

# 51. DOCUMENT MANAGEMENT

Le DMS possède :

```text
folders
files
versions
metadata
tags
permissions
sharing
retention
archiving
legal hold
OCR
preview
```

---

# 52. CRM

## Leads

```text
source
campaign
score
owner
qualification
consent
conversion
```

## Accounts

```text
customer account
hierarchy
contacts
addresses
relationships
roles
```

## Opportunities

```text
pipeline
stage
amount
probability
expected close
owner
products
services
competitors
activities
```

---

# 53. CRM ACTIVITIES

```text
Email
Call
Meeting
Task
Note
Event
Reminder
```

Toutes les activités alimentent les timelines autorisées.

---

# 54. SALES

```text
Quote
Sales Order
Contract
Renewal
Subscription
Commission
Customer Success
```

Pipeline :

```text
Lead
 ↓
Opportunity
 ↓
Quote
 ↓
Approval
 ↓
Order
 ↓
Fulfillment
 ↓
Invoice
 ↓
Payment
```

---

# 55. MARKETING

```text
Campaign
Segment
Audience
Journey
Email
SMS
Push
Automation
Consent
Suppression
Attribution
```

Toute communication marketing doit respecter les consentements et les règles de suppression.

---

# 56. SERVICE / CASE MANAGEMENT

Un Case représente une exception ou situation nécessitant suivi.

```text
Case
├── customer issue
├── dispute
├── compliance issue
├── quality issue
├── finance exception
├── integration problem
├── operational exception
└── support ticket
```

Le Case Management possède :

```text
queue
assignment
priority
SLA
escalation
status
evidence
tasks
communications
resolution
```

---

# 57. KNOWLEDGE

```text
Article
Category
Collection
Version
Revision
Visibility
Audience
Approval
Search
Embedding
```

Knowledge peut alimenter :

```text
Public KB
Internal KB
Customer Support
Employee Training
LMS
AI RAG
```

---

# 58. LMS

```text
Course
Module
Lesson
Quiz
Assignment
Enrollment
Progress
Attendance
Certificate
Instructor
Student
```

---

# 59. COMMUNITY

```text
Profile
Group
Forum
Topic
Comment
Review
Reaction
Moderation
Report
Membership
Notification
```

Modération :

```text
manual
rule-based
AI-assisted
appeal
audit
```

---

# 60. COLLABORATION

```text
Task
Comment
Mention
Assignment
Presence
Activity
Shared Document
Calendar
Notification
```

---

# 61. FINANCE — PRINCIPES

Finance est un domaine VITAL.

Il possède :

```text
Chart of Accounts
Ledger
Journal
Journal Entry
Journal Line
Fiscal Period
Tax
Payment Terms
AP
AR
Bank
Reconciliation
Asset
Expense
Budget
Forecast
Cash Flow
Consolidation
Intercompany
```

---

# 62. ACCOUNTING INVARIANTS

Une écriture postée :

```text
est équilibrée
est auditable
est immutable
est idempotente
appartient à une période
appartient à un journal
possède une source
possède une référence
```

Débit ≠ crédit interdit.

Somme :

```text
DEBITS = CREDITS
```

---

# 63. JOURNAL PIPELINE

```text
Business Event
    ↓
Accounting Mapping
    ↓
Draft Journal Entry
    ↓
Domain Validation
    ↓
Policy
    ↓
Approval si nécessaire
    ↓
Post
    ↓
Immutable Ledger
    ↓
Audit
    ↓
Outbox
```

---

# 64. MULTI-CURRENCY

Le modèle distingue :

```text
transaction amount
functional amount
reporting amount
exchange rate
rate source
rounding
valuation date
```

---

# 65. FISCAL PERIOD

```text
Open
Soft Closed
Closed
Locked
```

Une période fermée ne peut pas être modifiée via un simple CRUD.

Toute exception doit :

```text
être autorisée
être auditée
être traçable
```

---

# 66. AP

```text
Supplier
Bill
Credit Note
Payment Term
Approval
Payment Proposal
Payment
Reconciliation
```

---

# 67. AR

```text
Customer
Invoice
Credit Note
Payment
Allocation
Dunning
Collection
Write-off
Bad Debt
```

---

# 68. BANKING

```text
Bank Account
Statement
Transaction
Import
Matching
Reconciliation
Adjustment
```

Matching :

```text
automatic
rule based
AI-assisted
manual
```

---

# 69. TAX ENGINE

Le moteur fiscal utilise :

```text
Jurisdiction
Tax Rule
Tax Code
Tax Rate
Effective Date
Exemption
Customer status
Product classification
Place of supply
```

Les règles juridiques sont versionnées.

---

# 70. E-INVOICING

Architecture pour :

```text
UBL
CII
Factur-X
PEPPOL-compatible flows
country-specific formats
```

Pipeline :

```text
Invoice Posted
 ↓
Fiscal Validation
 ↓
Serialization
 ↓
Signature / Provider
 ↓
Transmission
 ↓
Acknowledgement
 ↓
Archive Evidence
```

---

# 71. PROCUREMENT

```text
Purchase Request
RFQ
Supplier Quote
Supplier Selection
Purchase Order
Receipt
Invoice
Payment
```

---

# 72. THREE-WAY MATCH

```text
Purchase Order
+
Receipt
+
Supplier Invoice
       ↓
Match
```

Les écarts ouvrent éventuellement un Case.

---

# 73. INVENTORY

Inventory doit reposer sur un ledger explicite.

```text
Product
Location
Lot
Serial
Stock Movement
Reservation
Transfer
Adjustment
Count
```

---

# 74. STOCK LEDGER

Un mouvement contient :

```text
movement_id
product_id
location
quantity
uom
lot
serial
source
reason
timestamp
actor
```

Le stock disponible doit être reconstructible.

---

# 75. WMS

```text
Receive
Putaway
Move
Pick
Pack
Ship
Count
Transfer
Cross Dock
```

---

# 76. LOTS / SERIALS

Support :

```text
lot
serial
expiry
manufacture date
supplier lot
quality status
traceability
```

---

# 77. REPLENISHMENT

Support :

```text
min/max
reorder point
safety stock
lead time
demand forecast
supplier constraints
```

---

# 78. LANDED COST

Les coûts sont répartis selon une méthode configurable :

```text
value
weight
volume
quantity
custom allocation
```

La méthode choisie est auditée.

---

# 79. CONSIGNMENT

Le modèle distingue :

```text
physical possession
economic ownership
```

Le stock consigné ne doit pas être confondu avec stock détenu économiquement.

---

# 80. MANUFACTURING / MRP

```text
BOM
Routing
Work Center
Operation
Production Order
Material Requirement
Capacity Requirement
Schedule
```

---

# 81. MRP

Pipeline :

```text
Demand
 ↓
Gross Requirements
 ↓
Netting
 ↓
Stock
 ↓
Purchase / Production
 ↓
Capacity
 ↓
Scheduling
```

---

# 82. PRODUCTION

```text
Draft
Planned
Released
Started
In Progress
Completed
Closed
Cancelled
```

---

# 83. QUALITY

```text
Inspection Plan
Inspection
Check
Non Conformance
Corrective Action
Preventive Action
Supplier Quality
Production Quality
```

---

# 84. ASSETS

```text
Asset
Category
Location
Custodian
Acquisition
Depreciation
Transfer
Disposal
Warranty
Maintenance
```

---

# 85. MAINTENANCE

```text
Preventive
Corrective
Predictive
Work Order
Technician
Parts
Labor
Time
Evidence
```

---

# 86. FLEET

```text
Vehicle
Registration
Insurance
Mileage
Maintenance
Fuel
Driver
Assignment
```

---

# 87. PROJECTS / PSA

```text
Project
Phase
Task
Milestone
Resource
Timesheet
Expense
Budget
Billing
Margin
```

---

# 88. TIMESHEETS

Une saisie de temps possède :

```text
employee
project
task
date
duration
billable
rate
approval
```

Le calcul de facturation doit conserver le snapshot de taux pertinent.

---

# 89. HR / HCM

```text
Employee
Employment Relationship
Contract
Position
Department
Manager
Attendance
Leave
Expense
Performance
Learning
Assets
Documents
```

---

# 90. HR PRIVACY

Les données sensibles RH sont compartimentées.

```text
General HR
Payroll
Performance
Highly Sensitive
```

Chaque catégorie possède une policy d'accès spécifique.

---

# 91. PAYROLL

Payroll doit couvrir :

```text
Payroll Calendar
Payroll Period
Payroll Run
Employee
Contract
Salary Components
Earnings
Deductions
Benefits
Bonus
Overtime
Absence
Leave
Proration
Retroactive Changes
Withholding
Social Contributions
Garnishments
Off-cycle Payroll
Termination
Payment Files
Payslip
Declarations
Accounting Integration
```

---

# 92. PAYROLL PIPELINE

```text
Period Open
 ↓
Collect Inputs
 ↓
Validate Contracts
 ↓
Compute
 ↓
Apply Rules
 ↓
Review
 ↓
Approval
 ↓
Post Payroll
 ↓
Generate Payslips
 ↓
Payment
 ↓
GL Posting
 ↓
Declarations
```

---

# 93. PIM

```text
Product
Product Type
Variant
Attribute
Attribute Set
Category
Brand
Media
Translation
Specification
Relationship
Bundle
```

---

# 94. CATALOGS

Support :

```text
multiple catalogs
multiple channels
multiple locales
B2B catalog
B2C catalog
seller catalog
regional catalog
```

---

# 95. PRICING

Pricing engine :

```text
Base Price
Customer Segment
Price List
Quantity Break
Currency
Channel
Dates
Promotion
Contract Price
Subscription Price
```

Snapshot de prix obligatoire sur les transactions historiques.

---

# 96. PROMOTIONS

```text
Coupon
Discount
Promotion
Bundle
Buy X Get Y
Volume Discount
Customer-specific Discount
Campaign Discount
```

Les promotions ont :

```text
eligibility
priority
stacking rules
limits
usage count
time window
channel
```

---

# 97. CART

```text
Cart
Cart Line
Price Snapshot
Tax Snapshot
Discount Snapshot
Inventory Reservation
```

Le panier n'est pas une garantie de disponibilité finale.

---

# 98. CHECKOUT

```text
Cart
 ↓
Validate Price
 ↓
Validate Tax
 ↓
Validate Inventory
 ↓
Validate Customer
 ↓
Create Order
 ↓
Payment
 ↓
Fulfillment
 ↓
Invoice
```

Le checkout doit être idempotent.

---

# 99. ORDERS

```text
Draft
Pending
Confirmed
Paid
Partially Fulfilled
Fulfilled
Cancelled
Returned
Refunded
Closed
```

---

# 100. SUBSCRIPTIONS

```text
Plan
Price
Subscription
Cycle
Usage
Invoice
Retry
Dunning
Pause
Resume
Cancel
Upgrade
Downgrade
Proration
```

---

# 101. POS

POS possède :

```text
Device
Register
Cashier Session
Cart
Ticket
Payment
Refund
Cash Movement
Closing
```

---

# 102. POS OFFLINE

Le mode offline utilise :

```text
device id
local operation id
base version
sync cursor
server operation id
conflict state
```

Le mode offline doit explicitement définir :

```text
Allowed Offline
Forbidden Offline
Requires Reconciliation
```

Les opérations financières à haut risque ne doivent pas être reproduites naïvement en offline.

---

# 103. MARKETPLACE

Marketplace :

```text
Seller
Seller Account
Seller Catalog
Order Split
Commission
Platform Fee
Payout
Settlement
Dispute
Moderation
```

Les fonds vendeurs et fonds plateforme sont conceptuellement séparés.

---

# 104. PAYMENTS

Payment possède son propre cycle :

```text
Created
Authorized
Captured
Partially Captured
Failed
Voided
Refunded
Partially Refunded
Disputed
```

---

# 105. PAYMENT PROVIDER ADAPTER

Interface :

```text
authorize
capture
void
refund
retrieve
verifyWebhook
saveMethod
```

Le domaine ne dépend pas directement de Stripe/Adyen/etc.

---

# 106. PAYMENT IDEMPOTENCE

Toute opération sensible doit supporter :

```text
Idempotency-Key
```

Une répétition retourne :

```text
same result
ou
explicitly determined state
```

et jamais un second paiement.

---

# 107. BOOKING

```text
Service
Resource
Availability
Capacity
Slot
Reservation
Price
Customer
Payment
Confirmation
Cancellation
```

---

# 108. BOOKING AVAILABILITY

Une disponibilité affichée n'est pas une réservation confirmée.

La confirmation nécessite une écriture protégée par la stratégie de concurrence adaptée.

Pour les intervalles arbitraires, la stratégie DOIT utiliser les contraintes PostgreSQL appropriées lorsqu'elles sont applicables, notamment des contraintes d'exclusion sur ranges ; les advisory locks peuvent compléter mais ne remplacent pas systématiquement l'intégrité de la base.

---

# 109. EVENTS

```text
Event
Venue
Session
Speaker
Ticket Type
Ticket
Attendee
Check-in
Certificate
```

---

# 110. TICKETING

```text
Inventory
Seat / Capacity
Reservation
Payment
Ticket
QR / Barcode
Check-in
Transfer
Refund
```

---

# 111. MEMBERSHIP

```text
Membership Plan
Member
Enrollment
Renewal
Fee
Access Rule
Benefit
Expiration
```

---

# 112. DOCUMENT PLATFORM

Document Platform :

```text
Artifact
File
Version
Template
Render
Preview
Signature
Evidence
Retention
Archive
```

Le domaine Documents ne possède pas :

```text
Invoice semantics
Booking semantics
Payroll semantics
Order semantics
```

Il fournit leur infrastructure documentaire.

---

# 113. CONTRACTS

```text
Contract
Contract Party
Version
Clause
Status
Approval
Signature
Effective Date
Expiration
Renewal
Obligation
```

---

# 114. E-SIGNATURE

```text
Signature Request
Signer
Sequence
Evidence
Hash
Timestamp
Provider
Signed Artifact
```

Les preuves de signature doivent être archivées conformément à la policy retenue.

---

# 115. OCR

```text
Upload
 ↓
Scan
 ↓
OCR
 ↓
Classification
 ↓
Extraction
 ↓
Validation
 ↓
Human Review
 ↓
Domain object
```

Le système conserve la distinction :

```text
raw OCR
extracted value
validated value
source evidence
```

---

# 116. WORKFLOW

Le système sépare :

```text
Domain State Machine
Approval Engine
Process Manager
Automation Engine
```

Il n'existe pas un pseudo « Universal Workflow » fourre-tout contenant toute la logique métier.

---

# 117. DOMAIN STATE MACHINE

Un domaine possède ses propres états.

Exemple :

```text
Invoice
Draft
Approved
Posted
Cancelled
```

Les transitions sont protégées par les invariants du domaine.

---

# 118. APPROVAL ENGINE

```text
Request
Requester
Approver Set
Policy
Quorum
Delegation
Deadline
Escalation
Decision
Evidence
```

---

# 119. APPROVAL AI

Une action demandée par une IA passe par exactement le même Approval Engine qu'une action humaine.

```text
AI Proposal
 ↓
Policy
 ↓
Pending Approval
 ↓
Human Decision
 ↓
Domain Command
```

---

# 120. AUTOMATION ENGINE

Support :

```text
Trigger
Condition
Branch
Wait
Loop bounded
Human Task
Command
Notification
Webhook
Retry
Timeout
Compensation
```

Jamais de boucle infinie.

---

# 121. PROCESS MANAGER / SAGA

Utilisé lorsque plusieurs transactions indépendantes sont nécessaires :

```text
Order
 ↓
Payment
 ↓
Inventory
 ↓
Fulfillment
 ↓
Invoice
```

Chaque étape possède :

```text
retry
timeout
failure
compensation
observability
idempotency
```

---

# 122. OUTBOX

Toute mutation transactionnelle importante qui déclenche un effet externe doit pouvoir écrire :

```text
DB state
+
Outbox event
```

dans la même transaction.

---

# 123. INBOX

Chaque consumer dispose d'une logique de déduplication.

Clé :

```text
event_id
```

Même événement reçu deux fois :

```text
processed once
```

---

# 124. RETRY / DEAD LETTER

Chaque job possède :

```text
attempt
backoff
timeout
max tries
dead letter
replay policy
correlation id
```

Le replay ne doit pas rejouer aveuglément une opération destructive.

---

# 125. NOTIFICATIONS

Canaux :

```text
In-App
Email
SMS
Push
Realtime
Webhook
```

Notification possède :

```text
template
locale
channel
recipient
deduplication
delivery state
read state
audit
```

---

# 126. REALTIME

Reverb peut servir :

```text
presence
notifications
job progress
dashboard refresh
collaboration
availability updates
```

Le temps réel ne devient jamais la source de vérité transactionnelle.

---

# 127. SEARCH

Architecture :

```text
Domain
 ↓
Projection
 ↓
Search Index
 ↓
Search Query
```

Le moteur ne possède pas les records métiers source.

---

# 128. GLOBAL SEARCH

Recherche :

```text
Customers
Contacts
Companies
Products
Orders
Invoices
Payments
Bookings
Tickets
Projects
Employees
Documents
Content
Knowledge
```

---

# 129. SEARCH SECURITY

Chaque document indexé doit avoir :

```text
tenant scope
visibility
organization scope
sensitivity
locale
AI eligibility
```

La recherche ne peut pas contourner l'autorisation.

---

# 130. SEARCH TESTS

Chaque index doit tester :

```text
isolation
authorization
reindex
deletion propagation
eventual consistency
locale
filter
facet
ranking baseline
version compatibility
```

---

# 131. ANALYTICS

Analytics est un vrai contexte métier transverse.

```text
Operational Events
 ↓
Ingestion
 ↓
Validation
 ↓
Facts
 ↓
Dimensions
 ↓
Semantic Layer
 ↓
Metrics
 ↓
Reports
 ↓
Dashboards
```

---

# 132. ANALYTICS SEMANTIC LAYER

Une métrique possède :

```text
id
name
definition
source
unit
currency semantics
aggregation
filter
owner
version
freshness
```

---

# 133. BI

Métriques :

```text
Revenue
Gross Margin
Cash
AR
AP
Pipeline
Inventory
Production
Bookings
Employee Cost
Project Profitability
Retention
```

---

# 134. REPORT BUILDER

```text
Data Source
 ↓
Dimensions
 ↓
Measures
 ↓
Filters
 ↓
Grouping
 ↓
Visualization
 ↓
Dashboard
 ↓
Schedule / Export
```

Les données sensibles restent protégées.

---

# 135. DASHBOARDS

Dashboards :

```text
personal
role-based
organization
legal entity
site
team
```

Widgets :

```text
chart
table
KPI
timeline
funnel
calendar
map
alert
```

---

# 136. API

La V1 expose une API versionnée.

```text
/api/v1
```

Les contrats sont documentés par OpenAPI.

Le style API DOIT être cohérent.

---

# 137. API ERROR CONTRACT

```json
{
  "code": "BOOKING_CONFLICT",
  "message": "The requested slot is no longer available.",
  "detail": "...",
  "correlation_id": "...",
  "retryable": false,
  "field_errors": []
}
```

---

# 138. API IDEMPOTENCE

Toutes les commandes sensibles doivent documenter :

```text
Idempotency-Key
```

et le comportement d'un retry.

---

# 139. WEBHOOKS

Webhook entrant :

```text
Provider
 ↓
Verify Signature
 ↓
Persist Raw Event
 ↓
Deduplicate
 ↓
Inbox
 ↓
Domain Command
```

Jamais :

```text
HTTP request
→ mutate DB
→ hope it works
```

---

# 140. INTEGRATION HUB

Un Integration Hub central doit gérer :

```text
Connection
Credential
Provider
External ID
Mapping
Sync
Webhook
Retry
Conflict
Transformation
Rate Limit
Audit
```

---

# 141. EXTERNAL IDS

Chaque intégration peut posséder :

```text
external_system
external_id
local_type
local_id
sync_state
last_synced_at
```

---

# 142. IMPORT ENGINE

Formats :

```text
CSV
XLSX
JSON
ZIP
API
```

Pipeline :

```text
Upload
 ↓
Mapping
 ↓
Validation
 ↓
Dry Run
 ↓
Import
 ↓
Reconciliation
 ↓
Report
```

---

# 143. EXPORT ENGINE

Formats :

```text
CSV
XLSX
PDF
JSON
```

Les gros exports sont asynchrones.

Les exports de données sensibles nécessitent les policies appropriées.

---

# 144. AI PLATFORM

AI est une plateforme contrôlée.

```text
Model Registry
Provider
Prompt Version
Tool
Agent
Run
Memory
Embedding
RAG
Budget
Policy
Approval
Audit
Evaluation
```

---

# 145. AI USE CASES

```text
Content
Translation
SEO
Search
CRM
Sales
Support
Finance Analysis
HR Assistance
Commerce
Booking
Documents
Analytics
```

---

# 146. AI COPILOT

Exemples :

```text
"Montre-moi les impayés > 30 jours."

"Prépare une relance pour ces clients."

"Analyse les ventes du trimestre."

"Prépare un rapport fournisseur."

"Trouve les documents liés à ce contrat."
```

L'IA répond selon les permissions de l'utilisateur.

---

# 147. AI TOOLING

Tools explicites :

```text
search_customer
get_invoice
find_booking
create_draft
prepare_quote
generate_report
search_documents
prepare_translation
```

Chaque tool possède :

```text
name
input schema
output schema
permission
risk tier
tenant scope
audit policy
rate limit
cost budget
```

---

# 148. AI SENSITIVE ACTIONS

Actions sensibles :

```text
publish
refund
payment
post accounting
change bank data
run payroll
delete
approve
```

ne doivent pas être exécutées automatiquement hors policy explicite.

---

# 149. AI THREAT MODEL

Protection contre :

```text
prompt injection
indirect prompt injection
tool poisoning
RAG exfiltration
cross-tenant retrieval
data leakage
memory poisoning
unauthorized tool calls
recursive agents
cost runaway
network abuse
secret leakage
```

---

# 150. AI RAG

Sources possibles :

```text
Documents
Knowledge
Policies
Contracts
Content
Products
Support
```

Chaque chunk conserve :

```text
tenant
source
visibility
classification
AI eligibility
provenance
```

---

# 151. AI EVALUATION

Chaque agent possède des évaluations :

```text
accuracy
tool selection
policy compliance
refusal correctness
retrieval quality
hallucination resistance
security
cost
latency
```

Les cas critiques sont dans une suite de régression.

---

# 152. AI COST CONTROL

Chaque run doit respecter :

```text
token budget
time budget
tool budget
network budget
max recursion
max steps
```

---

# 153. AUDIT

Chaque opération sensible doit pouvoir répondre :

```text
Who
What
When
Where
Why
Before
After
Source
Correlation ID
Causation ID
```

---

# 154. AUDIT APPEND ONLY

L'audit critique est append-only.

Une tentative de modification non autorisée doit échouer et être observable.

---

# 155. COMPLIANCE

Moteur de conformité :

```text
Consent
Retention
Deletion
Rectification
Export
Legal Hold
Classification
Audit
Evidence
```

Les obligations exactes sont liées au pack de juridiction.

---

# 156. DATA CLASSIFICATION

Chaque donnée sensible possède :

```text
classification
owner
steward
retention
indexability
AI eligibility
exportability
deletion rule
```

Classes possibles :

```text
PUBLIC
INTERNAL
CONFIDENTIAL
RESTRICTED
HIGHLY_SENSITIVE
```

---

# 157. SNAPSHOTS HISTORIQUES

Une commande historique utilise un snapshot lorsque nécessaire.

Exemples :

```text
Invoice
Order
Booking
Contract
Payroll
Journal
```

Doivent préserver les valeurs historiques indispensables :

```text
price
tax
address
currency
rate
terms
label
channel
locale
```

---

# 158. CONCURRENCY

Mécanisme choisi selon l'invariant :

```text
optimistic locking
SELECT FOR UPDATE
unique constraint
check constraint
exclusion constraint
advisory lock
compare-and-swap
atomic update
idempotency
```

Aucune stratégie unique n'est imposée à tout le système.

---

# 159. CONCURRENCY — BOOKING

Pour les périodes temporelles :

```text
resource_id
+
tstzrange
```

et exclusion constraint lorsque le modèle s'y prête.

Advisory lock PEUT compléter pour sérialiser certaines opérations.

---

# 160. CONCURRENCY — STOCK

```text
Cart Reservation
→ optimistic lock

Checkout Finalization
→ short FOR UPDATE transaction

Unique constraints
→ duplicate prevention
```

---

# 161. CONCURRENCY — PAYMENTS

```text
provider_event_id unique
idempotency key unique
payment state machine
```

---

# 162. CONCURRENCY — PAYROLL

Verrouillage logique par :

```text
legal_entity
payroll_period
```

Le calcul d'une paie ne doit pas être exécuté deux fois de façon destructive.

---

# 163. CONFIGURATION

Configuration hiérarchique :

```text
System
 ↓
Tenant
 ↓
Workspace
 ↓
Organization
 ↓
Legal Entity
 ↓
Site
 ↓
Channel
 ↓
User
```

Une valeur plus spécifique surcharge la valeur générale selon les règles documentées.

---

# 164. SETTINGS

Les settings doivent distinguer :

```text
Configuration
Secret
Feature Flag
Business Rule
Policy
```

Ils ne sont pas interchangeables.

---

# 165. BUSINESS TEMPLATES

Pour atteindre l'universalité, V1 possède un Setup Engine.

```text
Business Template
 ↓
Modules
 ↓
Roles
 ↓
Permissions
 ↓
Tax defaults
 ↓
Accounting defaults
 ↓
Workflows
 ↓
Documents
 ↓
Dashboards
 ↓
Channels
```

---

# 166. TEMPLATES V1

Le catalogue de base doit inclure :

```text
Personal
Freelance
Professional Services
Agency
Retail
E-commerce
Restaurant
Hotel
Hospitality
Events
Manufacturing
Distribution
Logistics
Construction
Real Estate
Automotive
Education
Nonprofit
Association
Marketplace
Subscription SaaS
Healthcare-capable base
Government-capable base
```

Les packs de juridiction et obligations spécifiques restent versionnés.

---

# 167. TEMPLATE INSTALLATION

```text
Choose Template
 ↓
Review
 ↓
Modules
 ↓
Roles
 ↓
Configuration
 ↓
Data Seeding
 ↓
Validation
 ↓
Activate
```

---

# 168. EXTENSIONS

Les extensions déclarent :

```text
name
version
compatibility
dependencies
permissions
capabilities
routes
commands
events
migrations
settings
UI registrations
```

---

# 169. EXTENSION SECURITY

Une extension ne doit pas :

```text
bypass tenant isolation
bypass policy
write foreign domain tables
disable audit
execute arbitrary secret access
```

sans permissions/capability explicites.

---

# 170. EXTENSION LIFECYCLE

```text
Install
Enable
Configure
Upgrade
Disable
Uninstall
Data Cleanup
Migration
Rollback strategy
```

L'uninstallation doit définir le traitement des données créées par l'extension.

---

# 171. SDK

SDK pour :

```text
Field
Block
Content Type
Payment Provider
Booking Provider
Tax Provider
Search Driver
AI Tool
Agent
Document Generator
Channel
Report
Dashboard Widget
```

---

# 172. PORTALS

Portails :

```text
Customer
Employee
Supplier
Partner
Seller
Admin
POS
Warehouse
Field
```

Les portails exposent des capacités existantes, ils ne dupliquent pas les domaines.

---

# 173. CUSTOMER PORTAL

```text
Profile
Orders
Quotes
Invoices
Payments
Bookings
Tickets
Contracts
Documents
Subscriptions
Reviews
```

---

# 174. EMPLOYEE PORTAL

```text
Profile
Leave
Attendance
Expenses
Timesheets
Projects
Documents
Payslips
Training
Tasks
```

---

# 175. SUPPLIER PORTAL

```text
RFQ
Purchase Orders
Receipts
Invoices
Documents
Contracts
Payments
```

---

# 176. SELLER PORTAL

```text
Products
Orders
Inventory
Payouts
Reviews
Analytics
```

---

# 177. WAREHOUSE PORTAL

```text
Receive
Putaway
Pick
Pack
Ship
Transfer
Count
```

---

# 178. FIELD SERVICE PORTAL

```text
Jobs
Route
Customer
Checklist
Photo
Signature
Parts
Time
```

---

# 179. OFFLINE

Le système offline DOIT être explicite.

Chaque opération possède :

```text
device_id
operation_id
base_version
created_at
sync_cursor
server_id
conflict_state
```

---

# 180. OFFLINE CONFLICTS

Stratégies possibles :

```text
merge
server wins
client wins
human review
domain-specific compensation
```

`Last Write Wins` ne doit jamais être appliqué par défaut aux domaines :

```text
Finance
Payments
Payroll
Inventory
Booking
Signature
```

---

# 181. QR / BARCODE

Support :

```text
EAN
UPC
Code 128
QR
DataMatrix
GS1-compatible patterns
```

Le code physique n'est pas nécessairement l'identifiant métier interne.

---

# 182. BUSINESS DAY

Les domaines opérationnels doivent supporter :

```text
Business Hours
Holiday Calendar
Working Days
Fiscal Calendar
Shift
Timezone
```

---

# 183. RECONCILIATION TRANSVERSE

Le système doit rapprocher :

```text
Orders ↔ Payments
Orders ↔ Inventory
Invoices ↔ Payments
PO ↔ Receipt ↔ Bill
Payroll ↔ GL
Booking ↔ Payment
Projects ↔ Timesheets ↔ Billing
Marketplace Orders ↔ Payouts
```

Chaque écart peut ouvrir un Case.

---

# 184. GLOBAL TIMELINE

Chaque 360° est un read model.

```text
Customer 360
Employee 360
Supplier 360
Partner 360
Product 360
Project 360
Content 360
Document 360
```

---

# 185. 360 UX

Pattern :

```text
ENTITY HEADER
├── Identity
├── Status
├── Owner
├── Main actions
└── Security-aware data

TABS
├── Overview
├── Activity
├── Documents
├── Transactions
├── Communications
├── Relationships
├── Audit
└── Related data
```

---

# 186. CUSTOM FIELDS

Un custom field possède :

```text
type
validation
visibility
indexability
classification
AI eligibility
exportability
retention
```

Types :

```text
text
long text
number
money
quantity
select
multi-select
boolean
date
datetime
relation
json
file
```

---

# 187. CUSTOM DATA VS CORE DATA

```text
Core Entity
→ invariant métier fort

Custom Content Type
→ structure flexible
```

Un administrateur ne peut pas transformer :

```text
Invoice
Payment
Stock Movement
Payroll Run
Booking
```

en simple contenu sans contraintes.

---

# 188. NO-CODE

Le no-code peut configurer :

```text
views
fields
forms
dashboards
menus
labels
content structures
safe workflows
safe automations
```

Il ne peut pas désactiver :

```text
accounting balance
payment safety
booking conflict
stock constraints
RLS
SoD
audit
payroll rules
```

---

# 189. PERFORMANCE BUDGET

Les performances sont spécifiées par :

```text
population
dataset
concurrency
RPS
cache state
DB size
search size
browser
network
```

Chaque scénario possède :

```text
p50
p95
p99
error rate
throughput
```

---

# 190. PERFORMANCE TARGETS

Les seuils réels doivent être définis par scénario et environnement.

Exemple :

```text
Simple authenticated page
→ p95 target

Search
→ p95 target

Checkout
→ p95 target

Booking
→ p95 target

API read
→ p95 target

API write
→ p95 target
```

Un SLO sans population ni contexte n'est pas considéré comme spécification valide.

---

# 191. OBSERVABILITY

Chaque requête importante porte :

```text
request_id
correlation_id
causation_id
tenant_id
user_id lorsqu'autorisé
```

Logs structurés.

---

# 192. METRICS

Minimum :

```text
request latency
error rate
queue depth
job failures
retry count
DB latency
DB connections
Redis health
search latency
search failures
payment failures
booking conflicts
RLS violations
AI cost
AI latency
outbox backlog
inbox failures
projection lag
```

---

# 193. HORIZON

Queues :

```text
default
high
critical
search
analytics
ai
integrations
notifications
exports
imports
```

Les jobs critiques ont des files dédiées.

---

# 194. BACKUPS

Sauvegarder :

```text
Database
Documents
Media
Configuration
Secrets metadata where appropriate
```

Backup n'est valide que si un restore périodique est testé.

---

# 195. DISASTER RECOVERY

Chaque service critique définit :

```text
RPO
RTO
restore procedure
validation
owner
manual fallback
```

---

# 196. RUNBOOKS

Runbooks :

```text
DB outage
Redis outage
Queue backlog
Search outage
Payment webhook backlog
Booking conflict incident
Payroll stuck
Backup restore
Deployment rollback
Tenant isolation incident
AI provider outage
Integration outage
```

---

# 197. RELEASE ENGINEERING

Release :

```text
Code
Migration
Config
Dependencies
API
Events
Search indexes
Jobs
Templates
Feature flags
```

Tout changement important est documenté.

---

# 198. MIGRATIONS

Les migrations doivent respecter les fenêtres de compatibilité de déploiement.

Privilégier :

```text
expand
migrate
backfill
contract
```

plutôt que :

```text
destructive change in one deployment
```

---

# 199. FEATURE FLAGS

Les feature flags servent à :

```text
rollout
rollback
experimentation
tenant rollout
channel rollout
```

Ils ne servent pas à cacher des features essentielles de V1 indéfiniment.

---

# 200. DEPENDENCY POLICY

Les dépendances sont classées :

```text
Core Runtime
Infrastructure
Developer Tooling
Optional Adapter
Extension
```

Chaque dépendance possède :

```text
owner
purpose
license
security process
upgrade policy
```

---

# 201. PACKAGE QUALITY BASELINE

Socle dev :

```json
{
  "require-dev": {
    "laravel/pint": "^1",
    "laravel/pao": "^1",
    "orchestra/testbench": "^11",
    "pestphp/pest": "^5",
    "pestphp/pest-plugin-laravel": "^5",
    "pestphp/pest-plugin-livewire": "^5",
    "pestphp/pest-plugin-type-coverage": "^5",
    "pestphp/pest-plugin-browser": "^5",
    "pestphp/pest-plugin-stressless": "^5",
    "pestphp/pest-plugin-drift": "^5",
    "pestphp/pest-plugin-agent": "^5",
    "pestphp/pest-plugin-phpstan": "^5",
    "phpstan/phpstan": "^2",
    "larastan/larastan": "^3",
    "phpstan/phpstan-strict-rules": "^2",
    "phpstan/phpstan-deprecation-rules": "^2",
    "rector/rector": "^2",
    "nunomaduro/phpinsights": "^2"
  }
}
```

Les versions mineures exactes sont verrouillées dans `composer.lock` après validation de compatibilité.

---

# 202. QUALITY GATES

```text
Pint
→ pass

Rector dry run
→ pass

PHPStan max
→ zero errors

Type Coverage
→ 100%

Architecture
→ zero violations

Security
→ zero critical

Browser critical paths
→ green

Mutation
→ target met on critical domains

Performance
→ thresholds met

Recovery
→ validated

```

---

# 203. TESTING — PHILOSOPHIE

```text
Coverage
≠
Correctness
```

La qualité est prouvée par :

```text
types
invariants
constraints
authorization
transactions
concurrency
contracts
browser
security
performance
recovery
```

---

# 204. TEST LEVELS

```text
Unit
Feature
Integration
Database
Contract
Architecture
Security
Concurrency
Browser
Accessibility
Performance
Recovery
AI
Migration
```

---

# 205. DOMAIN TESTS

Chaque aggregate teste :

```text
valid command
invalid command
invariant violation
transition
state
event
audit
idempotency
concurrency
```

---

# 206. DATABASE TESTS

Tester réellement :

```text
FK
UNIQUE
CHECK
EXCLUSION
RLS
indexes where relevant
foreign keys
cascade behavior
```

---

# 207. RLS TESTS

```text
A → A
A ↛ B
```

Tester :

```text
SELECT
INSERT
UPDATE
DELETE
JOIN
SEARCH
EXPORT
QUEUE
AI
```

---

# 208. ARCHITECTURE TESTS

Interdit :

```text
Domain → UI
Domain → Stripe
Domain → Meilisearch
Domain → Redis
Domain → Eloquent infrastructure when abstraction required
```

Interdit également :

```text
Finance → write Commerce tables
CRM → write Finance tables
Booking → write Payment tables
```

Les interactions passent par les contrats appropriés.

---

# 209. TYPE COVERAGE

Objectif :

```text
100%
```

du code PHP first-party inclus dans la politique Type Coverage.

---

# 210. PHPSTAN

```bash
vendor/bin/phpstan analyse --level=max
```

Objectif :

```text
0 errors
```


Le dépôt est au niveau 7 au 2026-10-01 ; la passe P00 le relève à `max`.

---

# 211. PINT

```bash
vendor/bin/pint --test
```

Objectif :

```text
0 diff
```

---

# 212. RECTOR

```bash
vendor/bin/rector process --dry-run
```

La CI ne doit pas accepter les modifications automatiques silencieuses.

---

# 213. MUTATION TESTING

Mutation renforcée sur :

```text
Finance
Payments
Pricing
Taxes
Inventory
Booking
Authorization
Publishing
Localization
Payroll
Orders
```

Objectif : tuer les mutants pertinents du périmètre critique.

---

# 214. PROPERTY-BASED TESTING

À utiliser lorsque les invariants sont mieux exprimés comme propriétés.

Exemples :

```text
Debit/Credit always balanced
Currency conversions remain coherent
Tax totals match line totals
Quantity aggregation remains deterministic
Idempotent command does not duplicate
```

---

# 215. CONCURRENCY TESTING

Le test doit réellement simuler :

```text
Worker A
Worker B
```

sur la même ressource.

Exemples :

```text
double booking
oversell
double payment
coupon race
duplicate webhook
payroll double run
period close race
```

---

# 216. API CONTRACT TESTING

Tester :

```text
schema
status
error contract
pagination
filter
sort
auth
scope
idempotency
version
deprecation
```

---

# 217. EVENT CONTRACT TESTING

Chaque event possède :

```text
schema
version
required fields
sensitivity
consumer compatibility
```

Un breaking change impose une nouvelle version.

---

# 218. BROWSER TESTING

Navigateur :

```text
Chromium
Firefox
WebKit
```

Sur :

```text
desktop
tablet
mobile
```

Parcours critiques :

```text
login
tenant switch
CMS publish
CRM lead conversion
quote
order
checkout
payment
booking
invoice
purchase
stock
payroll
portal
POS
```

---

# 219. ACCESSIBILITY BROWSER TESTING

Tester :

```text
keyboard only
focus
screen reader landmarks
dialogs
tables
forms
errors
contrast
zoom
RTL
```

---

# 220. VISUAL REGRESSION

Chaque composant critique possède un snapshot visuel.

```text
component
 ↓
fixture
 ↓
browser
 ↓
screenshot
 ↓
diff
```

Matrice :

```text
Light
Dark
RTL
LTR

Desktop
Tablet
Mobile
```

---

# 221. SECURITY TESTING

Minimum :

```text
IDOR
Tenant escape
RBAC bypass
ABAC bypass
Privilege escalation
CSRF
XSS
Mass Assignment
Path traversal
File upload abuse
Private media leakage
Preview leakage
Rate limit
Token leakage
Webhook signature bypass
```

---

# 222. FINANCIAL SECURITY TESTING

```text
Price tampering
Tax tampering
Currency tampering
Negative quantity
Duplicate payment
Webhook replay
Refund abuse
Coupon abuse
Period lock bypass
Journal manipulation
```

---

# 223. BOOKING SECURITY TESTING

```text
Double booking
Capacity bypass
Unauthorized cancellation
Cross-tenant booking
Price manipulation
Timezone exploit
```

---

# 224. AI SECURITY TESTING

```text
Prompt injection
Data exfiltration
Cross-tenant RAG
Unauthorized tool
Sensitive tool bypass
Memory poisoning
Prompt leakage
Secret extraction
Recursive execution
Cost abuse
```

---

# 225. PERFORMANCE TESTING

Outil de charge compatible avec le pipeline choisi.

Scénarios :

```text
public web
API
search
checkout
booking
inventory
CRM
dashboard
imports
exports
```

---

# 226. SOAK TEST

Le système doit supporter des exécutions prolongées sans :

```text
memory leak
queue leak
connection leak
unbounded cache
event duplication
```

---

# 227. MIGRATION TESTS

Tester :

```text
empty database
small database
realistic database
large database
upgrade from previous schema
rollback where defined
```

---

# 228. BACKUP RESTORE TEST

```text
Create backup
 ↓
Destroy test target
 ↓
Restore
 ↓
Validate
 ↓
Smoke tests
 ↓
Business verification
```

---

# 229. HOW-TO STANDARD D'UN DOMAINE

Chaque domaine DOIT être implémenté selon :

```text
1. Define Context
2. Define Ownership
3. Define Aggregates
4. Define Invariants
5. Define Tables
6. Define Constraints
7. Define Commands
8. Define Queries
9. Define Policies
10. Define Events
11. Implement Outbox
12. Implement Inbox
13. Implement Read Models
14. Implement API
15. Implement ManeUI
16. Implement Audit
17. Implement Observability
18. Write Tests
19. Run Security
20. Run Browser
21. Document ADR
22. Validate Acceptance
```

---

# 230. VERIFY STANDARD D'UN DOMAINE

Un domaine est `Verified` uniquement lorsque :

```text
[ ] ownership valid
[ ] invariants tested
[ ] migrations valid
[ ] constraints valid
[ ] RLS valid
[ ] policies valid
[ ] commands tested
[ ] queries tested
[ ] events tested
[ ] outbox tested
[ ] inbox tested
[ ] projections tested
[ ] API tested
[ ] UI tested
[ ] accessibility tested
[ ] audit tested
[ ] security tested
[ ] concurrency tested
[ ] observability verified
[ ] documentation complete
[ ] acceptance complete
```

---

# 231. RECIPE — CMS

## Build

```text
Create Page
Create Revision
Validate Content
Review
Publish
Generate Search Projection
Generate SEO
Invalidate Cache
Emit PagePublished.v1
```

## Verify

```text
Draft not public
Published revision visible
Rollback works
Audit exists
Search projection updated
Cross-tenant access denied
```

## Acceptance

```text
Editor creates page
Reviewer approves
Publisher publishes
Public visitor sees exact published revision
```

---

# 232. RECIPE — CRM

## Build

```text
Lead
 ↓
Qualification
 ↓
Convert
 ↓
Party / Contact
 ↓
Opportunity
 ↓
Quote
```

## Verify

```text
duplicate conversion prevented
permissions respected
audit present
timeline updated
```

---

# 233. RECIPE — FINANCE

## Build

```text
Business Event
 ↓
Accounting Mapper
 ↓
Journal Draft
 ↓
Validation
 ↓
Post
 ↓
Ledger
 ↓
Audit
```

## Verify

```text
balanced entry
closed period rejected
duplicate command rejected
tenant escape impossible
posting immutable
```

---

# 234. RECIPE — PROCUREMENT

```text
Request
 ↓
Approval
 ↓
RFQ
 ↓
Supplier Quote
 ↓
PO
 ↓
Receipt
 ↓
Bill
 ↓
Three Way Match
 ↓
Payment
```

Verify each transition, approval and matching discrepancy.

---

# 235. RECIPE — INVENTORY

```text
Demand
 ↓
Reservation
 ↓
Movement
 ↓
Ledger
```

Verify concurrency, stock availability, lots, serials, traceability and reconciliation.

---

# 236. RECIPE — MANUFACTURING

```text
Demand
 ↓
MRP
 ↓
Planned Orders
 ↓
Production
 ↓
Quality
 ↓
Stock
 ↓
Cost
```

Verify BOM version, routing version, material consumption and production completion.

---

# 237. RECIPE — PAYROLL

```text
Employee
 ↓
Contract Snapshot
 ↓
Period
 ↓
Inputs
 ↓
Calculation
 ↓
Review
 ↓
Approval
 ↓
Posting
 ↓
Payslip
```

Verify no duplicate run, jurisdiction rules, period lock and GL reconciliation.

---

# 238. RECIPE — COMMERCE

```text
Catalog
 ↓
Price
 ↓
Cart
 ↓
Tax
 ↓
Order
 ↓
Payment
 ↓
Stock
 ↓
Fulfillment
 ↓
Invoice
```

---

# 239. RECIPE — BOOKING

```text
Service
 ↓
Resource
 ↓
Availability
 ↓
Conflict-safe Reservation
 ↓
Payment
 ↓
Confirmation
```

The availability view never guarantees the final reservation.

---

# 240. RECIPE — DOCUMENTS

```text
Business Aggregate
 ↓
Document Definition
 ↓
Template
 ↓
Render
 ↓
Version
 ↓
Artifact
 ↓
Archive
```

---

# 241. RECIPE — AI

```text
User Intent
 ↓
Policy
 ↓
Retrieval
 ↓
Tool Selection
 ↓
Draft / Proposal
 ↓
Approval if required
 ↓
Command
 ↓
Audit
```

---

# 242. RECIPE — ANALYTICS

```text
Event
 ↓
Ingestion
 ↓
Validation
 ↓
Fact
 ↓
Semantic Metric
 ↓
Dashboard
```

---

# 243. RECIPE — INTEGRATION

```text
External Event
 ↓
Signature Verification
 ↓
Raw Event Store
 ↓
Dedup
 ↓
Inbox
 ↓
Mapping
 ↓
Domain Command
```

---

# 244. RECIPE — OFFLINE

```text
Local Operation
 ↓
Persist
 ↓
Queue
 ↓
Reconnect
 ↓
Send
 ↓
Conflict Decision
 ↓
Apply
 ↓
Acknowledge
```

---

# 245. VERTICAL PACK — RETAIL

```text
PIM
Catalog
Pricing
POS
Inventory
Returns
Payments
Promotions
Customer
```

End-to-end :

```text
Product
→ Catalog
→ Sale
→ Payment
→ Stock
→ Invoice
→ Finance
```

---

# 246. VERTICAL PACK — RESTAURANT

```text
Menu
Table
Reservation
Order
Kitchen Ticket
Course
Payment
Inventory
Procurement
Staff
```

---

# 247. VERTICAL PACK — HOTEL

```text
Property
Room
Room Type
Availability
Reservation
Guest
Rate Plan
Housekeeping
Check-in
Check-out
Payment
Invoice
```

---

# 248. VERTICAL PACK — REAL ESTATE

```text
Property
Unit
Listing
Owner
Prospect
Viewing
Offer
Lease
Rent
Maintenance
Documents
Payments
```

---

# 249. VERTICAL PACK — HEALTHCARE-CAPABLE CORE

Le noyau doit pouvoir représenter :

```text
Patient
Provider
Facility
Appointment
Encounter
Document
Billing
Consent
```

Les données cliniques sensibles possèdent des classifications et policies fortement renforcées.

Le produit ne prétend pas fournir automatiquement une conformité réglementaire spécifique sans pack et validation juridictionnelle appropriés.

---

# 250. VERTICAL PACK — LOGISTICS

```text
Shipment
Route
Vehicle
Driver
Package
Warehouse
Tracking
Proof of Delivery
Billing
```

---

# 251. VERTICAL PACK — CONSTRUCTION

```text
Project
Site
Contract
Subcontractor
BOQ
Purchase
Work Order
Schedule
Timesheet
Expense
Progress
Invoice
```

---

# 252. VERTICAL PACK — AUTOMOTIVE

```text
Vehicle
Customer
Workshop Order
Parts
Labor
Maintenance
Warranty
Appointment
Invoice
```

---

# 253. VERTICAL PACK — EDUCATION

```text
Institution
Program
Course
Module
Lesson
Student
Enrollment
Schedule
Attendance
Assessment
Certificate
Billing
```

---

# 254. VERTICAL PACK — NONPROFIT

```text
Member
Donor
Campaign
Donation
Recurring Donation
Receipt
Fund
Volunteer
Event
Grant
Accounting
```

---

# 255. VERTICAL PACK — PROFESSIONAL SERVICES

```text
Lead
Proposal
Contract
Project
Resource
Timesheet
Expense
Billing
Retainer
Profitability
```

---

# 256. VERTICAL PACK — MARKETPLACE

```text
Seller
Catalog
Order
Split
Commission
Payment
Payout
Dispute
Review
Moderation
```

---

# 257. BUSINESS UNIVERSALITY

L'universalité ne repose pas sur un énorme objet générique.

Elle repose sur :

```text
Universal Primitives
+
Typed Domain Models
+
Business Templates
+
Jurisdiction Packs
+
Policies
+
Channels
+
Extensions
```

---

# 258. MASTER DATA

Master data principales :

```text
Party
Product
Service
Resource
Asset
Currency
Tax
UOM
Location
Organization
Legal Entity
```

Chaque master possède :

```text
status
version
effective dates
provenance
audit
external ids
```

---

# 259. DATA LINEAGE

Les données analytiques doivent pouvoir identifier :

```text
source domain
source event
source record
transformation
metric
refresh time
```

---

# 260. REPORTING HISTORIQUE

Les rapports doivent utiliser les bonnes sémantiques temporelles :

```text
transaction date
posting date
service date
booking date
payment date
fiscal date
```

---

# 261. CACHE

Les clés de cache incluent selon le besoin :

```text
tenant
workspace
organization
site
channel
locale
user scope
revision
version
```

Aucune donnée sensible ne doit être partagée accidentellement entre tenants.

---

# 262. SESSION CONTEXT

Le contexte de requête doit pouvoir transporter :

```text
tenant
workspace
organization
legal entity
site
channel
locale
timezone
user
correlation id
```

---

# 263. ERROR MODEL

Les erreurs métier doivent être typées.

Exemples :

```text
BOOKING_CONFLICT
PAYMENT_ALREADY_CAPTURED
FISCAL_PERIOD_CLOSED
STOCK_INSUFFICIENT
APPROVAL_REQUIRED
POLICY_DENIED
TENANT_CONTEXT_REQUIRED
IDEMPOTENCY_CONFLICT
```

---

# 264. OBSERVABILITY OF FAILURE

Une erreur critique doit permettre de savoir :

```text
where
when
who
tenant
command
aggregate
correlation
cause
retryability
```

---

# 265. API RATE LIMITS

Limitation adaptée par :

```text
user
tenant
API token
route
provider
channel
risk tier
```

---

# 266. WEBHOOK SECURITY

Toujours :

```text
signature validation
timestamp validation
replay protection
raw payload retention
deduplication
audit
```

---

# 267. SECRET MANAGEMENT

Les secrets doivent être gérés hors du code source.

Support :

```text
environment secrets
external secret manager
rotation
revocation
audit
```

---

# 268. CRYPTOGRAPHIC GOVERNANCE

Pour les clés critiques :

```text
key ownership
rotation
version
purpose
algorithm
storage
revocation
incident procedure
```

---

# 269. BREAK-GLASS

Un mécanisme d'urgence PEUT exister pour certains incidents.

Il doit :

```text
be explicit
be time limited
be audited
require elevated authorization
produce alerts
```

---

# 270. SECURITY HEADERS

Le web public et l'admin doivent appliquer les politiques de sécurité appropriées :

```text
CSP
HSTS
frame protection
content type protection
referrer policy
permissions policy
```

---

# 271. FILE SECURITY

Tout upload doit être contrôlé :

```text
mime
extension
size
checksum
malware scanning policy
classification
tenant
owner
```


## 271.1 Règles par défaut des uploads

- Stockage privé par défaut ; la publication est explicite.
- Nom stocké : ULID + extension déduite du MIME détecté. Le nom d'origine n'est qu'une métadonnée.
- MIME détecté depuis le contenu, jamais depuis le nom ou l'en-tête client.
- Images acceptées : JPEG, PNG, WebP, AVIF. SVG refusé sauf sanitization dédiée. 10 Mo maximum par défaut, dimensions maximales définies.
- Images ré-encodées, métadonnées EXIF supprimées (géolocalisation).
- Checksum SHA-256 stocké ; déduplication dans le tenant.
- Chaque fichier porte `tenant_id`, propriétaire et classification (§156).
- Uploads Livewire : validation des fichiers temporaires et purge des temporaires abandonnés.

---

# 272. PRIVATE MEDIA

Accès via :

```text
authorized controller
signed temporary URL
permission check
```

Jamais par exposition directe non contrôlée.

---

# 273. COMPLIANCE CASES

Toute anomalie importante peut générer :

```text
Case
Evidence
Owner
Deadline
Decision
Resolution
Audit
```

---

# 274. GLOBAL EXCEPTION MANAGEMENT

Exceptions transversales :

```text
Finance mismatch
Payment discrepancy
Inventory discrepancy
Integration failure
Data quality problem
Booking conflict
Compliance issue
AI incident
```

sont représentables par le Case Management.

---

# 275. DATA QUALITY

Les masters doivent pouvoir être contrôlés par :

```text
completeness
uniqueness
validity
consistency
timeliness
```

---

# 276. DEDUPLICATION

Le système possède des règles de déduplication pour :

```text
Party
Product
Supplier
Customer
Document
External Events
Imports
Payments
```

---

# 277. RECONCILIATION ENGINE

Le moteur permet :

```text
source A
↔
source B
```

avec :

```text
matched
partial
unmatched
exception
```

---

# 278. SEARCH / ANALYTICS / AI SEPARATION

```text
Transactional DB
        │
        ├── Read Models
        ├── Search
        ├── Analytics
        └── AI RAG
```

Search, Analytics et AI ne deviennent jamais la source primaire d'un invariant métier.

---

# 279. API / UI SEPARATION

```text
Domain
 ↓
Application
 ├── Livewire
 ├── API
 ├── CLI
 └── Jobs
```

La logique métier ne doit pas dépendre de Livewire.

---

# 280. LIVEWIRE ARCHITECTURE

Les composants Livewire doivent être fins :

```text
UI State
Input
Calling Application Service
Displaying Result
```

Ils ne doivent pas contenir toute la logique de domaine.

---

# 281. LIVEWIRE FORM CONTRACT

Chaque formulaire possède :

```text
validation
authorization
dirty state
submit state
loading state
error state
success state
reset behavior
```

---

# 282. LIVEWIRE TABLE CONTRACT

Chaque table riche supporte si applicable :

```text
sorting
filter
search
pagination
bulk actions
selection
column visibility
saved view
export
```

---

# 283. TALLSTACKUI RULE

Les composants administratifs standards utilisent TallStackUI via ManeUI.

ManeUI encapsule :

```text
styling
semantics
accessibility
states
responsive behavior
```

---

# 284. NO FLUX RULE

Le repository DOIT échouer en CI si des dépendances ou imports interdits apparaissent.

Contrôles possibles :

```text
composer.lock
composer.json
imports
namespace checks
source scan
architecture test
```

Aucune référence :

```text
Flux
livewire/flux
```

dans le socle ManeCMS.

---

# 285. DOCUMENTATION

La documentation doit contenir :

```text
README
Architecture
C4
ADR
Domain Contexts
API
Events
Security
Runbooks
Design System
How-To
Troubleshooting
Migration Guide
Extension SDK
```

---

# 286. ADR

ADR indispensables :

```text
Modular Monolith
Tenancy
RLS
Party
MDM
Policy
Channel
Money
Time
Accounting
Tax
Booking
Inventory
Payments
Outbox
Inbox
Saga
Snapshots
Analytics
Search
API
AI Security
Extensions
Offline
DR
```

---

# 287. RÈGLE ADR

Une décision acceptée possède :

```text
Context
Problem
Options
Decision
Consequences
Status
Date
```

Pas de duplicate « final » contradictoire.

---

# 288. CI

Pipeline :

```text
Install
 ↓
Composer Validate
 ↓
Security Audit
 ↓
Pint
 ↓
Rector Dry Run
 ↓
PHPStan
 ↓
Architecture
 ↓
Unit
 ↓
Feature
 ↓
Integration
 ↓
Type Coverage
 ↓
Contract
 ↓
Security
 ↓
Mutation targets
 ↓
Browser
 ↓
Accessibility
 ↓
Performance targets
 ↓
Build
 ↓
Migration compatibility
 ↓
Release
```

---

# 289. LOCAL DEV WORKFLOW

Avec Herd :

```text
Edit
 ↓
php artisan
 ↓
Livewire
 ↓
Pest fast feedback
 ↓
Browser when needed
 ↓
PHPStan
 ↓
Commit
```

---

# 290. DAILY DEVELOPER COMMANDS

```bash
# le site est servi par Herd (https://manecms.test) : jamais php artisan serve
php artisan test
vendor/bin/pest
vendor/bin/pest --parallel
vendor/bin/pest --type-coverage
vendor/bin/phpstan analyse --level=max
vendor/bin/pint --test
vendor/bin/rector process --dry-run
php artisan migrate:fresh --seed
php artisan queue:work
```

Les commandes exactes peuvent être encapsulées par Composer scripts.

---

# 291. COMPOSER SCRIPTS

```json
{
  "scripts": {
    "quality": [
      "@quality:format",
      "@quality:rector",
      "@quality:phpstan",
      "@quality:insights"
    ],
    "quality:format": "@php vendor/bin/pint --test",
    "quality:rector": "@php vendor/bin/rector process --dry-run",
    "quality:phpstan": "@php vendor/bin/phpstan analyse --level=max",
    "quality:insights": "@php artisan insights",
    "test:types": "@php vendor/bin/pest --type-coverage --min=100",
    "test:architecture": "@php vendor/bin/pest tests/Architecture",
    "test:unit": "@php vendor/bin/pest tests/Unit --parallel",
    "test:feature": "@php vendor/bin/pest tests/Feature --parallel",
    "test:integration": "@php vendor/bin/pest tests/Integration --parallel",
    "test:security": "@php vendor/bin/pest tests/Security --parallel",
    "test:browser": "@php vendor/bin/pest tests/Browser --parallel",
    "test:concurrency": "@php vendor/bin/pest tests/Concurrency",
    "test:contract": "@php vendor/bin/pest tests/Contract",
    "test:performance": "@php vendor/bin/pest tests/Performance",
    "test": "@composer quality && @composer test:unit && @composer test:feature"
  }
}
```

La suite complète CI peut ajouter les campagnes coûteuses :

```text
mutation
browser matrix
stress
visual regression
DR
```

---

# 292. PEST GROUPS

Les tests doivent être classés par groupes :

```text
unit
feature
integration
architecture
database
security
browser
accessibility
contract
concurrency
performance
ai
recovery
migration
critical
```

---

# 293. RISK TIERS

```text
VITAL
STANDARD
EXTENSIBLE
```

VITAL :

```text
Finance
Payments
Payroll
Inventory critical path
Booking critical path
Authorization
Tenant isolation
Publishing
```

---

# 294. VITAL TEST POLICY

VITAL :

```text
100% type coverage
strict PHPStan
architecture
critical browser
concurrency
security
mutation target
audit
recovery where relevant
```

---

# 295. STANDARD POLICY

STANDARD :

```text
100% type coverage
PHPStan
feature/integration
critical browser if applicable
security
selected mutation
```

---

# 296. EXTENSIBLE POLICY

EXTENSIBLE :

```text
100% type coverage
PHPStan
core integration
basic browser where applicable
security
```

---

# 297. TEST DATA

Utiliser :

```text
Factories
States
Builders
Fixtures
Seeds
Scenario generators
```

Les fixtures doivent être déterministes.

---

# 298. TEST ISOLATION

Chaque test doit nettoyer :

```text
database
cache
queues
events
search fixtures
files
```

selon sa nature.

---

# 299. REAL POSTGRES TESTS

Les invariants dépendant de PostgreSQL doivent être testés sur PostgreSQL.

Ne pas remplacer systématiquement :

```text
RLS
EXCLUSION
locking
JSONB
constraints
```

par SQLite.


## 299.1 Base de test

Toute la suite tourne sur PostgreSQL (`manecms_testing`), jamais sur SQLite, à partir de P00. Le rôle de test a `CREATEDB` pour les tests parallèles. Les tests RLS utilisent le rôle applicatif non propriétaire.

---

# 300. SEARCH TEST INFRA

Les tests Search doivent pouvoir lancer :

```text
real Meilisearch
```

ou un driver explicitement désigné pour les tests unitaires lorsque la fonctionnalité ne nécessite pas le moteur réel.

---

# 301. FILE TEST INFRA

Le stockage test peut utiliser :

```text
local fake
MinIO
S3-compatible test environment
```

selon le niveau de test.

---

# 302. PAYMENT TEST INFRA

Les providers sont simulés dans les tests unitaires, mais les intégrations critiques possèdent des tests sandbox.

---

# 303. BOOKING TEST INFRA

Tests :

```text
unit availability
DB exclusion constraint
real concurrent transactions
browser booking flow
```

---

# 304. MASTERY RULE

Un développeur ne considère pas un domaine terminé lorsqu'il a créé :

```text
Model
Migration
Controller
CRUD
```

Un domaine est terminé lorsqu'il possède :

```text
Model
Invariant
Ownership
Transaction
Policy
Events
Read Model
Audit
Integration
Observability
Tests
Docs
Acceptance
```

---

# 305. ACCEPTANCE — PERSONAL

Le mode personnel doit permettre :

```text
Profile
Contacts
Calendar
Tasks
Notes
Documents
Bookings
Expenses
Subscriptions
Personal Projects
AI Assistant
```

---

# 306. ACCEPTANCE — FREELANCE

```text
Lead
 ↓
Opportunity
 ↓
Quote
 ↓
Contract
 ↓
Project
 ↓
Timesheet
 ↓
Invoice
 ↓
Payment
 ↓
Accounting
```

---

# 307. ACCEPTANCE — RETAIL

```text
Catalog
 ↓
Price
 ↓
POS/Web
 ↓
Order
 ↓
Payment
 ↓
Stock
 ↓
Fulfillment
 ↓
Invoice
 ↓
Accounting
```

---

# 308. ACCEPTANCE — MANUFACTURING

```text
Demand
 ↓
MRP
 ↓
Purchase / Production
 ↓
BOM
 ↓
Routing
 ↓
Quality
 ↓
Stock
 ↓
Shipment
 ↓
Finance
```

---

# 309. ACCEPTANCE — HOTEL

```text
Room
 ↓
Availability
 ↓
Reservation
 ↓
Payment
 ↓
Check-in
 ↓
Stay
 ↓
Check-out
 ↓
Invoice
 ↓
Accounting
```

---

# 310. ACCEPTANCE — RESTAURANT

```text
Table
 ↓
Reservation
 ↓
Order
 ↓
Kitchen
 ↓
Delivery / Service
 ↓
Payment
 ↓
Stock
 ↓
Accounting
```

---

# 311. ACCEPTANCE — EDUCATION

```text
Course
 ↓
Enrollment
 ↓
Schedule
 ↓
Attendance
 ↓
Assessment
 ↓
Certificate
 ↓
Billing
```

---

# 312. ACCEPTANCE — NONPROFIT

```text
Donor
 ↓
Campaign
 ↓
Donation
 ↓
Receipt
 ↓
Fund
 ↓
Accounting
 ↓
Analytics
```

---

# 313. ACCEPTANCE — MARKETPLACE

```text
Seller
 ↓
Catalog
 ↓
Order
 ↓
Split
 ↓
Payment
 ↓
Commission
 ↓
Payout
 ↓
Reconciliation
```

---

# 314. ACCEPTANCE — GROUP MULTI-ENTITY

```text
Tenant
 ↓
Group
 ├── France
 ├── Spain
 ├── Morocco
 └── UAE
```

Support :

```text
multiple legal entities
multiple currencies
intercompany
consolidation
delegated admin
security boundaries
group reporting
```

---

# 315. ACCEPTANCE — CUSTOMER 360

Le système doit agréger :

```text
Party
CRM
Orders
Invoices
Payments
Bookings
Tickets
Documents
Contracts
Subscriptions
```

via read models.

---

# 316. ACCEPTANCE — SUPPLIER 360

```text
Supplier
Procurement
Orders
Receipts
Invoices
Payments
Contracts
Quality
Performance
```

---

# 317. ACCEPTANCE — EMPLOYEE 360

```text
Identity
Employment
Contracts
Leave
Attendance
Timesheets
Expenses
Payroll
Documents
Learning
Tasks
```

---

# 318. ACCEPTANCE — PRODUCT 360

```text
Product
Variants
Catalogs
Pricing
Stock
Orders
Purchasing
Production
Quality
Documents
Analytics
```

---

# 319. ACCEPTANCE — DOCUMENT 360

```text
Artifact
Version
Owner
Relations
Audit
Retention
Signature
Evidence
```

---

# 320. FAILURE MODE ENGINEERING

Chaque domaine doit documenter au moins :

```text
duplicate command
concurrent update
network timeout
provider timeout
DB rollback
queue retry
partial success
projection lag
search stale
permission denied
tenant missing
external duplicate
invalid state
```

---

# 321. PARTIAL FAILURE

Exemple :

```text
Payment captured
BUT
invoice generation failed
```

Le système ne doit pas perdre l'information.

Il crée :

```text
Payment success
+
Outbox
+
Exception Case if needed
+
Retry invoice generation
```

---

# 322. PARTIAL FAILURE BOOKING

```text
Booking committed
BUT
email failed
```

La réservation reste valide.

La notification est une opération secondaire réessayable.

---

# 323. PARTIAL FAILURE INVENTORY

```text
Stock movement committed
BUT
search projection failed
```

Le stock réel ne dépend pas de Search.

---

# 324. IDEMPOTENCY RULE

Tout consommateur critique doit répondre :

```text
What happens on duplicate event?
```

La réponse doit être documentée.

---

# 325. CORRELATION RULE

Chaque processus distribué possède :

```text
correlation_id
causation_id
```

---

# 326. DATA RETENTION

Chaque domaine définit :

```text
retention
archive
anonymize
delete
legal hold
```

---

# 327. LEGAL HOLD

Une donnée sous legal hold :

```text
ne doit pas être supprimée automatiquement
```

même si sa rétention normale est dépassée.

---

# 328. DATA SUBJECT WORKFLOWS

Support :

```text
access
export
rectification
restriction
deletion
```

avec contrôle des obligations de conservation.

---

# 329. CONSENT

Consent possède :

```text
purpose
source
timestamp
version
locale
scope
withdrawal
evidence
```

---

# 330. EMAIL COMPLIANCE

Marketing :

```text
unsubscribe
suppression
consent
preferences
```

Le système ne doit pas envoyer un message marketing à un destinataire supprimé de la liste selon les règles applicables.

---

# 331. TEMPLATES EMAIL

Chaque template :

```text
id
version
locale
subject
body
variables
channel
preview
test
status
```

---

# 332. NUMBERING

Sequences par :

```text
Legal Entity
Document Type
Fiscal Year
Site
Country
```

Les numéros ne doivent pas être réutilisés sans règle explicitement définie.

---

# 333. PDF

PDF Engine :

```text
template
locale
brand
version
variables
metadata
QR
attachments
```

---

# 334. DOCUMENT RENDERING TEST

Pour les documents critiques :

```text
render
compare expected output
verify values
verify numbering
verify tax
verify locale
verify QR
```

---

# 335. TRANSLATION MEMORY

```text
source
translation
locale pair
context
hash
status
usage
```

---

# 336. AI TRANSLATION

```text
TM lookup
 ↓
Existing translation?
 ↓
Proposal
 ↓
AI if missing
 ↓
Human review
 ↓
Approval
 ↓
Publish
```

---

# 337. SEO LOCALIZATION

Pour les contenus multilingues :

```text
slug
canonical
hreflang
title
description
structured data
```

---

# 338. DESIGN SYSTEM QA

Chaque nouveau composant doit avoir :

```text
API
States
Responsive
Dark
RTL
Accessibility
Examples
Tests
Visual baseline
Documentation
```

---

# 339. DESIGN SYSTEM DEFINITION OF DONE

```text
[ ] component built
[ ] TallStackUI/ManeUI compliant
[ ] no Flux
[ ] keyboard support
[ ] focus visible
[ ] screen reader semantics
[ ] dark mode
[ ] RTL
[ ] mobile
[ ] loading
[ ] error
[ ] disabled
[ ] visual test
[ ] browser test
[ ] docs
```

---

# 340. PAGE BUILDER DEFINITION OF DONE

```text
[ ] schema
[ ] editor
[ ] tree
[ ] drag/drop
[ ] responsive
[ ] persistence
[ ] undo
[ ] redo
[ ] autosave
[ ] preview
[ ] revision
[ ] permissions
[ ] accessibility
[ ] publish
[ ] rollback
[ ] browser tests
```

---

# 341. PORTAL DEFINITION OF DONE

```text
[ ] auth
[ ] tenant scope
[ ] policy scope
[ ] responsive
[ ] mobile
[ ] accessibility
[ ] data privacy
[ ] audit
[ ] session timeout
[ ] browser tests
```

---

# 342. API DEFINITION OF DONE

```text
[ ] OpenAPI
[ ] auth
[ ] authorization
[ ] pagination
[ ] errors
[ ] idempotency
[ ] rate limit
[ ] audit
[ ] version
[ ] tests
```

---

# 343. EVENT DEFINITION OF DONE

```text
[ ] schema
[ ] version
[ ] sensitivity
[ ] producer
[ ] consumers
[ ] outbox
[ ] inbox where relevant
[ ] retries
[ ] dedup
[ ] replay
[ ] observability
[ ] contract tests
```

---

# 344. EXTENSION DEFINITION OF DONE

```text
[ ] manifest
[ ] compatibility
[ ] permissions
[ ] migrations
[ ] install
[ ] upgrade
[ ] disable
[ ] uninstall
[ ] cleanup
[ ] tests
[ ] security
```

---

# 345. RELEASE DEFINITION OF DONE

```text
[ ] composer install
[ ] migrations
[ ] PHPStan
[ ] Pint
[ ] Rector
[ ] Architecture
[ ] Unit
[ ] Feature
[ ] Integration
[ ] Contracts
[ ] Security
[ ] Browser
[ ] Accessibility
[ ] Critical mutation
[ ] Performance
[ ] Backup
[ ] Restore
[ ] Docs
[ ] ADR
[ ] Changelog
[ ] Rollback notes
```

---

# 346. MASTER WALKING SKELETON

Le système doit prouver l'intégration de tous les domaines.

```text
Identity
 ↓
Tenant
 ↓
Party
 ↓
Organization
 ↓
Channel
 ↓
CMS
 ↓
CRM
 ↓
Product
 ↓
Quote
 ↓
Order
 ↓
Payment
 ↓
Inventory
 ↓
Booking
 ↓
Document
 ↓
Finance
 ↓
Automation
 ↓
Analytics
 ↓
AI
```

Le walking skeleton ne remplace pas la profondeur des domaines.

Il vérifie que le noyau universel est réellement transversal.

---

# 347. DAY-1 VERTICAL THREADS

Le produit doit avoir dès le début des fils verticaux fonctionnels :

```text
CMS
→ publish

CRM
→ lead conversion

Finance
→ balanced journal

Commerce
→ checkout

Payments
→ capture idempotente

Booking
→ conflict-safe reservation

Documents
→ invoice PDF

Inventory
→ reservation

HR
→ employee

Payroll
→ payroll run

Projects
→ timesheet

AI
→ proposal + approval
```

---

# 348. CROSS-DOMAIN RULE

Chaque liaison inter-domaine doit répondre :

```text
Who owns the data?
Who owns the command?
Who owns the invariant?
Which event?
Which projection?
Which policy?
Which audit?
Which retry behavior?
```

Si la réponse n'est pas documentée, le design n'est pas validé.

---

# 349. EXEMPLE COMMERCE → FINANCE

Commerce produit :

```text
OrderPlaced
```

Finance décide ce que cet événement implique pour sa propre comptabilité.

Commerce ne fait pas :

```sql
INSERT INTO journal_entries
```

---

# 350. EXEMPLE BOOKING → PAYMENTS

Booking demande une opération Payment.

Payments possède l'état du paiement.

Booking possède l'état de la réservation.

---

# 351. EXEMPLE HR → PAYROLL

HR possède :

```text
Employment
Contract
Employee relationship
```

Payroll possède :

```text
Payroll Run
Payslip
Calculation
```

---

# 352. EXEMPLE PAYROLL → FINANCE

Payroll publie les informations nécessaires.

Finance possède le posting comptable résultant.

---

# 353. EXEMPLE PRODUCT → ORDER

Commerce possède le Product master et ses variants.

Order conserve le snapshot transactionnel nécessaire.

---

# 354. EXEMPLE CONTENT → SEARCH

Content publie :

```text
PagePublished
PageUnpublished
RevisionPublished
```

Search projette.

---

# 355. EXEMPLE DOCUMENT → SEARCH

Documents peut demander une indexation, mais Search reste owner de l'index.

---

# 356. EXEMPLE AI → DOMAIN

AI :

```text
propose
prepare
simulate
```

Le domaine :

```text
validate
authorize
execute
```

---

# 357. COMMANDS

Une commande décrit une intention :

```text
CreateInvoice
ApproveInvoice
PostInvoice
CapturePayment
ReserveBooking
ConfirmOrder
ReleaseStock
ApprovePayroll
PublishPage
```

Elle ne doit pas être un DTO géant sans règles.

---

# 358. QUERIES

Les queries sont optimisées pour la lecture.

```text
GetCustomer360
SearchInvoices
GetBookingAvailability
GetInventoryPosition
GetPayrollSummary
```

---

# 359. READ MODEL CONTRACT

Chaque projection possède :

```text
name
source events
schema
projection version
rebuild
lag
authorization
retention
```

---

# 360. PROJECTION FAILURE

Si une projection échoue :

```text
source of truth remains valid
projection is retried
lag is measurable
```

---

# 361. CACHE INVALIDATION

Une publication ou mutation importante doit définir les caches concernés.

Exemple :

```text
ContentPublished
→ public page cache
→ CDN cache
→ search projection
```

---

# 362. QUEUE PRIORITY

Les travaux bloquant le métier ont priorité sur les traitements secondaires.

Exemple :

```text
payment confirmation
>
analytics enrichment
```

---

# 363. OBSERVABILITY DASHBOARD

Admin/ops doit voir :

```text
queue health
outbox backlog
inbox failures
projection lag
search health
DB
Redis
storage
payments
booking conflicts
AI cost
integration health
```

---

# 364. HEALTH CHECKS

```text
database
redis
search
storage
mail
queue
reverb
payments
AI
backup
```

---

# 365. INCIDENT MANAGEMENT

Incident :

```text
Detection
 ↓
Classification
 ↓
Containment
 ↓
Recovery
 ↓
Validation
 ↓
Root Cause
 ↓
Corrective Action
```

---

# 366. POSTMORTEM

Incident critique :

```text
timeline
impact
root cause
detection gap
response
fix
regression test
```

---

# 367. OBSERVABILITY SECURITY

Les logs ne doivent pas contenir :

```text
password
full card data
secrets
tokens
unnecessary PII
```

---

# 368. LOG CORRELATION

Chaque événement critique est corrélable :

```text
Request
 ↓
Command
 ↓
DB
 ↓
Event
 ↓
Queue
 ↓
Provider
```

---

# 369. MULTI-REGION READY

L'architecture doit pouvoir évoluer vers :

```text
single region
multi region
read replica
region-aware routing
```

sans changer les contrats métier.

---

# 370. DATABASE SCALING

Prévoir :

```text
connection pooling
read replicas where useful
partitioning when justified
archiving
index lifecycle
vacuum/analyze discipline
```

---

# 371. TABLE OWNERSHIP

Chaque table appartient à un domaine.

Le repository doit pouvoir vérifier :

```text
table
→ owning context
```

---

# 372. MIGRATION OWNERSHIP

Une migration ne doit modifier une table étrangère qu'avec une procédure architecturale documentée.

---

# 373. QUERY OWNERSHIP

Les queries doivent respecter :

```text
Domain read
Application query
Read model
```

selon leur usage.

---

# 374. DATABASE CONSTRAINTS AS CODE

Les invariants qui peuvent être garantis en base doivent l'être lorsque cela apporte une protection déterministe.

Exemples :

```text
unique
check
foreign key
exclusion
not null
```

---

# 375. DATABASE INVARIANT PRIORITY

```text
Business invariant
>
Application convenience
```

Si l'invariant est critique, il ne doit pas dépendre uniquement du contrôleur.

---

# 376. API SECURITY

```text
authentication
authorization
abilities
rate limits
token expiry
revoke
rotation
audit
```

---

# 377. OAUTH

Passport n'est utilisé que lorsqu'un vrai besoin OAuth2 est identifié.

Sanctum reste la voie standard des API/SPA classiques.

---

# 378. WEB ADMIN SECURITY

Admin possède :

```text
MFA
risk-based policy
session management
audit
privileged action controls
```

---

# 379. EXPORT SECURITY

Avant export :

```text
permission
policy
scope
classification
audit
volume threshold
```

Les gros exports sont asynchrones.

---

# 380. AI DATA GOVERNANCE

Avant d'envoyer une donnée à un provider AI externe :

```text
allowed?
tenant isolated?
classification allowed?
provider allowed?
retention?
contractual policy?
```

---

# 381. AI PROVIDER ADAPTER

```text
Mane AI Contract
 ↓
Provider Adapter
 ↓
OpenAI / Anthropic / Gemini / local / other
```

Le domaine ne dépend pas directement du fournisseur.

---

# 382. AI FALLBACK

Un agent doit définir :

```text
fallback model
retry
provider unavailable
timeout
partial completion
```

---

# 383. AI HUMAN OVERRIDE

L'utilisateur peut :

```text
reject
edit
approve
regenerate
escalate
```

selon permission.

---

# 384. AI AUDIT

Chaque run critique :

```text
user
agent
model
prompt version
tools
inputs
outputs
decision
cost
duration
approval
```

---

# 385. REDACTION

Avant logs / analytics / AI :

```text
redaction
masking
tokenization
classification filters
```

si nécessaire.

---

# 386. GLOBAL SEARCH PRIVACY

Une donnée visible dans un écran A ne devient pas automatiquement visible dans Search.

Search réévalue :

```text
scope
visibility
permission
classification
```

---

# 387. GLOBAL ANALYTICS PRIVACY

Même logique pour Analytics.

Une dimension sensible :

```text
payroll
bank
health
HR
```

est contrôlée indépendamment.

---

# 388. MOBILE UX

Mobile :

```text
touch targets
gesture
responsive tables
bottom actions
offline state
reduced density
```

---

# 389. TABLET UX

Support :

```text
split panes
touch + keyboard
dense forms
landscape
```

---

# 390. DESKTOP UX

Desktop doit tirer parti de :

```text
multi-panel
keyboard
command palette
dense tables
shortcuts
```

---

# 391. KEYBOARD SHORTCUTS

Le système peut définir :

```text
search
command palette
save
cancel
next
previous
open
close
```

Les raccourcis doivent être documentés et non destructifs par défaut.

---

# 392. ERROR UX

Chaque erreur importante indique :

```text
what happened
why
what can be done
retryable?
reference
```

---

# 393. ASYNC UX

Les opérations asynchrones affichent :

```text
queued
running
completed
failed
retrying
```

---

# 394. V1 DEFINITION ABSOLUE

ManeCMS V1 n'est pas déclarée terminée selon le nombre d'écrans.

Elle est déclarée terminée lorsque :

```text
TOUS LES CONTEXTES V1
+
LEURS CONTRATS
+
LEURS AGRÉGATS
+
LEURS INVARIANTS
+
LEURS TABLES
+
LEURS TRANSACTIONS
+
LEURS EVENTS
+
LEURS READ MODELS
+
LEURS POLICIES
+
LEURS UI
+
LEURS API
+
LEURS AUDITS
+
LEURS TESTS
+
LEURS RUNBOOKS
+
LEURS ACCEPTANCE TESTS
```

sont présents et vérifiés.

---

# 395. ABSOLUTE RELEASE GATE

Aucune release V1 n'est validée si l'une des catégories critiques suivantes échoue :

```text
Tenant Isolation
Authorization
Finance Integrity
Payment Integrity
Booking Integrity
Inventory Integrity
Payroll Integrity
Data Retention
Audit
Critical Security
Migration Compatibility
Backup/Restore
```

---

# 396. PR REVIEW CONTRACT

Chaque PR répond :

```text
Quel domaine ?
Quel aggregate ?
Quel invariant ?
Quelles tables ?
Quelle migration ?
Quelles policies ?
Quels events ?
Quels projections ?
Quel audit ?
Quelle observabilité ?
Quels tests ?
Quel risque ?
Quel ADR ?
```

---

# 397. DESIGN REVIEW CONTRACT

Toute feature UI importante vérifie :

```text
ManeUI
TallStackUI
states
responsive
dark
RTL
accessibility
keyboard
loading
empty
error
permission
visual regression
```

---

# 398. SECURITY REVIEW CONTRACT

Nouvelle feature :

```text
new data?
new permission?
new endpoint?
new file?
new secret?
new integration?
new AI tool?
new audit need?
new PII?
```

---

# 399. DATA REVIEW CONTRACT

Nouvelle donnée :

```text
owner
type
nullable?
FK?
tenant scoped?
RLS?
classification?
retention?
index?
searchable?
analytics?
AI?
snapshot?
```

---

# 400. EVENT REVIEW CONTRACT

Nouvel event :

```text
name
version
producer
consumer
payload
sensitivity
idempotency
ordering
retry
replay
```

---

# 401. ACCEPTANCE REVIEW CONTRACT

Une fonctionnalité n'est pas acceptée par :

```text
"ça marche chez moi"
```

Elle est acceptée par :

```text
Given
When
Then
Evidence
```

---

# 402. EXEMPLE ACCEPTANCE

```text
GIVEN
a user belongs to tenant A

WHEN
the user searches invoices

THEN
only tenant A invoices are visible

AND
tenant B invoices are absent from
UI
API
Search
Export
AI/RAG
```

---

# 403. EXEMPLE CONCURRENCY

```text
GIVEN
one booking slot

AND
two simultaneous requests

WHEN
both try to reserve

THEN
exactly one succeeds

AND
the other receives BOOKING_CONFLICT
```

---

# 404. EXEMPLE IDEMPOTENCE

```text
GIVEN
payment command K

WHEN
the same command is sent twice

THEN
one payment effect exists

AND
both responses resolve to the same operation
```

---

# 405. EXEMPLE FINANCE

```text
GIVEN
closed fiscal period

WHEN
user attempts posting

THEN
posting is rejected

AND
no journal line is created

AND
audit contains refusal
```

---

# 406. EXEMPLE AI

```text
GIVEN
an AI agent with refund preparation capability

WHEN
it proposes a refund

THEN
a PendingApproval is created when policy requires it

AND
no refund is executed before approval
```

---

# 407. EXEMPLE UI

```text
GIVEN
a disabled submit button

WHEN
keyboard navigation is used

THEN
focus remains visible

AND
disabled semantics are announced

AND
the action cannot be invoked
```

---

# 408. TEST DATA REDACTION

Production data ne doit pas être copiée telle quelle dans les environnements non autorisés.

---

# 409. STAGING

Staging doit ressembler à la production sur :

```text
auth
queue
search
storage
observability
payments sandbox
webhooks
browser
```

sans données personnelles non autorisées.

---

# 410. PRODUCTION DEPLOYMENT

```text
Build
 ↓
Migrate compatible
 ↓
Health
 ↓
Warmup
 ↓
Queue workers
 ↓
Web
 ↓
Search
 ↓
Observe
```

---

# 411. ROLLBACK

Chaque release définit :

```text
code rollback
migration mitigation
feature flag rollback
queue compatibility
event compatibility
search compatibility
```

---

# 412. QUEUE DEPLOYMENT

Les workers doivent être compatibles avec les événements et jobs en circulation pendant le déploiement.

---

# 413. EVENT DEPLOYMENT

Un consumer ancien ne doit pas casser brutalement la production lorsqu'un producer est déployé.

---

# 414. SEARCH REINDEX

Chaque index possède :

```text
version
build procedure
cutover strategy
rollback strategy
```

---

# 415. ANALYTICS REBUILD

Les projections Analytics doivent pouvoir être reconstruites depuis les sources reconnues, selon la politique de rétention.

---

# 416. AUDIT RETENTION

Les logs d'audit critiques ont une stratégie de conservation explicite.

---

# 417. OBSERVABILITY ALERTS

Alertes sur :

```text
5xx
DB saturation
queue backlog
payment failures
booking conflicts
outbox backlog
RLS errors
AI runaway
search outage
backup failure
```

---

# 418. HEALTH / SMOKE

Après déploiement :

```text
login
read
write
queue
search
document
payment sandbox
booking
```

si applicables.

---

# 419. BUSINESS SMOKE

Le smoke métier vérifie un flux de bout en bout.

---

# 420. UNIVERSAL SMOKE

```text
Create Party
 ↓
Create Product
 ↓
Create Customer
 ↓
Create Order
 ↓
Create Payment
 ↓
Create Invoice
 ↓
Create Booking
 ↓
Generate Document
 ↓
Emit Event
 ↓
Projection
 ↓
Analytics
```

---

# 421. DOMAIN REBUILDABILITY

Chaque projection importante doit disposer d'une procédure de rebuild.

---

# 422. DATA REPAIR

Les outils admin de correction doivent :

```text
require capability
produce audit
be idempotent where possible
```

---

# 423. ADMIN ACTIONS

Les actions admin dangereuses doivent être visibles comme :

```text
privileged
destructive
irreversible
```

---

# 424. BULK OPERATIONS

Bulk actions :

```text
must have permissions
must be bounded
must be auditable
must be observable
```

---

# 425. ASYNC BULK

Au-delà d'un volume déterminé :

```text
create job
show progress
allow cancel when safe
report failures
```

---

# 426. IMPORT RECONCILIATION

Après import :

```text
rows read
rows valid
rows created
rows updated
rows skipped
rows failed
duplicates
warnings
```

---

# 427. MASTER DATA IMPORT

Les imports MDM doivent intégrer :

```text
match
merge candidates
external ids
provenance
```

---

# 428. DOCUMENT IMPORT

OCR/import doit conserver :

```text
source file
hash
OCR output
extracted fields
confidence
validation state
```

---

# 429. SECURITY OF AI IMPORT

AI extraction ne doit jamais automatiquement devenir :

```text
posted accounting
payment
payroll
bank change
```

sans contrôles appropriés.

---

# 430. SEARCH RANKING

Le ranking doit être configurable mais testable.

Une modification du ranking ne doit pas produire une régression invisible sur les scénarios critiques.

---

# 431. SEARCH RECALL TEST

Dataset connu :

```text
query
expected candidates
expected top results
```

---

# 432. ANALYTICS CORRECTNESS

Les KPI critiques ont des datasets de référence.

Exemple :

```text
Revenue
```

doit avoir un scénario fixture où le résultat attendu est connu.

---

# 433. FINANCE RECONCILIATION TEST

Le dataset de test doit permettre de vérifier :

```text
subledger
↔
GL
↔
payments
```

---

# 434. INVENTORY RECONCILIATION TEST

```text
Opening
+
Receipts
-
Issues
± Adjustments
=
Closing
```

Le système doit pouvoir expliquer un écart.

---

# 435. PAYROLL RECONCILIATION

```text
Payroll
↔
Payslips
↔
Payments
↔
GL
```

---

# 436. BOOKING RECONCILIATION

```text
Bookings
↔
Payments
↔
capacity
↔
attendance/check-in
```

---

# 437. MARKETPLACE RECONCILIATION

```text
Orders
↔
Fees
↔
Seller balances
↔
Payouts
```

---

# 438. CURRENCY RECONCILIATION

Les arrondis sont documentés et testés sur :

```text
line
document
payment
posting
report
```

---

# 439. TAX RECONCILIATION

Les taxes par ligne doivent pouvoir être rapprochées avec :

```text
tax code
jurisdiction
rate
base
amount
document total
```

---

# 440. CONFIGURATION AUDIT

Toute configuration ayant un effet métier important possède :

```text
version
actor
timestamp
old value
new value
reason
```

---

# 441. WORKFLOW VERSIONING

Une instance utilise la version du process avec laquelle elle a commencé, sauf mécanisme explicite de migration.

---

# 442. AUTOMATION VERSIONING

Une exécution en cours doit être associée à la version exacte de la définition exécutée.

---

# 443. TEMPLATE VERSIONING

Les Business Templates sont versionnés.

Une nouvelle version ne doit pas modifier silencieusement une installation existante.

---

# 444. JURISDICTION VERSIONING

Les règles fiscales/sociales possèdent :

```text
jurisdiction
effective date
version
source
review date
```

---

# 445. JURISDICTION TESTING

Chaque pack doit fournir :

```text
known examples
expected calculations
effective dates
regression tests
```

---

# 446. NO UNIVERSAL LEGAL GUARANTEE

ManeCMS doit être architecture-ready pour plusieurs pays, mais la conformité réglementaire exacte est toujours validée par pack de juridiction et gouvernance appropriée.

---

# 447. BUSINESS HOURS

Les règles de disponibilité utilisent :

```text
timezone
calendar
holiday
business hours
exceptions
```

---

# 448. RESOURCE CAPACITY

Booking doit permettre :

```text
capacity
resource
resource group
staff
room
seat
equipment
```

---

# 449. INVENTORY RESERVATION

Réservation stock :

```text
requested
reserved
allocated
picked
packed
shipped
released
```

---

# 450. SHIPPING

```text
Carrier
Shipment
Package
Tracking
Label
Dispatch
Proof of Delivery
```

---

# 451. RETURNS

Commerce :

```text
Return Request
Approval
Receipt
Inspection
Refund
Restock
Replacement
```

---

# 452. REFUNDS

Refund doit être :

```text
authorized
idempotent
linked to payment
audited
reconciled
```

---

# 453. DISPUTES

Payments et Marketplace peuvent créer :

```text
Dispute
Evidence
Deadline
Response
Outcome
```

---

# 454. CUSTOMER SUCCESS

```text
Health
Renewal
Risk
Tasks
Playbooks
Expansion
Churn signal
```

Les signaux IA restent des signaux, pas des décisions autonomes.

---

# 455. NOTIFICATION PREFERENCES

Utilisateur/client :

```text
email
sms
push
in-app
marketing
transactional
operational
```

---

# 456. COMMUNICATION CENTER

Le produit doit permettre de retrouver :

```text
message
channel
recipient
sender
template
status
audit
thread
```

---

# 457. CONVERSATIONS

```text
Conversation
Thread
Message
Participant
Attachment
Status
```

---

# 458. COMMENTS

Commentaires transversaux possibles sur les objets autorisés :

```text
Task
Document
Project
Order
Ticket
Content
```

Le commentaire conserve :

```text
author
timestamp
edit history
moderation
visibility
```

---

# 459. ACTIVITY STREAM

Un flux d'activité est un read model.

Il ne devient pas la source de vérité des domaines.

---

# 460. SEARCH / COMMENTS / PRIVACY

Un commentaire privé ne doit jamais apparaître dans une recherche publique ou un RAG non autorisé.

---

# 461. GLOBAL COMMAND CENTER

L'interface principale peut fournir :

```text
search
command palette
recent
tasks
alerts
favorites
shortcuts
```

---

# 462. NAVIGATION

Navigation dépend de :

```text
role
permissions
capabilities
tenant
organization
channel
feature flags
```

---

# 463. PERMISSION-AWARE UX

Si l'utilisateur ne peut pas effectuer une action :

```text
l'action est absente
ou
désactivée avec raison claire
```

selon le besoin de compréhension métier.

---

# 464. EMPTY STATES

Chaque écran important définit :

```text
empty
first-use
no-access
no-results
error
```

---

# 465. LOADING STATES

Les interfaces doivent distinguer :

```text
initial loading
action loading
background refresh
queued operation
```

---

# 466. ERROR STATES

Les erreurs ne doivent pas être uniquement :

```text
Something went wrong
```

---

# 467. DESIGN DENSITY

ManeUI supporte :

```text
comfortable
compact
dense
```

pour les profils métier.

---

# 468. THEME

```text
Light
Dark
System
```

Le thème ne doit pas casser :

```text
charts
status colors
contrast
RTL
```

---

# 469. RESPONSIVE

Chaque composant critique possède :

```text
mobile behavior
tablet behavior
desktop behavior
```

---

# 470. PRINT

Certaines interfaces métier doivent avoir une présentation imprimable :

```text
Invoice
Quote
Purchase Order
Packing Slip
Certificate
Report
```

---

# 471. EMAIL RENDER TEST

Les emails critiques doivent être testés au minimum sur :

```text
HTML validity
variables
locale
CTA
attachments
```

---

# 472. PDF SNAPSHOT

Les documents critiques possèdent des fixtures d'acceptance.

---

# 473. AUDIT UI

Une vue audit doit permettre :

```text
who
when
action
object
before
after
reason
```

selon les droits.

---

# 474. CASE UI

Case doit présenter :

```text
context
priority
status
owner
deadline
evidence
timeline
actions
resolution
```

---

# 475. ADMIN HEALTH UI

Dashboard Ops :

```text
System
DB
Redis
Queue
Search
Storage
Payments
Bookings
AI
Integrations
Backups
```

---

# 476. SUPPORT TOOLING

Admin support doit pouvoir :

```text
find party
inspect timeline
inspect jobs
inspect integrations
inspect audits
inspect cases
```

sans contourner les règles de confidentialité.

---

# 477. SUPPORT IMPERSONATION

Une fonction d'impersonation PEUT exister mais doit être :

```text
explicit
time-limited
audited
visible
```

---

# 478. EXPORT SUPPORT

Support ne doit pas pouvoir exporter n'importe quelles données sans policy.

---

# 479. INCIDENT MODE

Le système peut activer un mode incident permettant :

```text
read-only
disable risky mutation
show banner
increase logging
pause automation
```

selon procédure.

---

# 480. AUTOMATION SAFETY

Une automation critique possède :

```text
max executions
rate limit
time window
failure policy
```

---

# 481. RECURSION PROTECTION

Si :

```text
Event A
→ automation
→ Event A
```

un guard doit empêcher une boucle infinie.

---

# 482. EVENT STORM PROTECTION

Le système surveille :

```text
event volume
retry rate
consumer lag
automation recursion
```

---

# 483. SEARCH STORM PROTECTION

Reindex massif :

```text
throttle
batch
backpressure
observability
```

---

# 484. IMPORT STORM PROTECTION

Import massif :

```text
chunk
queue
rate limit
resume
checkpoint
```

---

# 485. LARGE DATASET SUPPORT

Le produit doit éviter les opérations :

```text
load all
```

sur des collections potentiellement massives.

---

# 486. API PAGINATION

Pagination standardisée :

```text
page/page size
ou
cursor
```

selon endpoint.

---

# 487. BULK API

Pour de gros volumes :

```text
async job
progress
result file
```

---

# 488. IMPORT IDEMPOTENCY

Chaque import possède :

```text
import_id
row key
source
batch
```

Le même batch rejoué ne doit pas créer des doublons.

---

# 489. DATA MIGRATION

Un import initial peut utiliser :

```text
staging tables
mapping
validation
dry run
reconciliation
```

---

# 490. DATA SEEDING

Seeds :

```text
roles
permissions
policies
currencies
locales
timezones
UOM
tax baseline
templates
```

---

# 491. DEMO DATA

Les données demo doivent être activées explicitement et jamais semées par erreur en production.

---

# 492. PRIVACY BY DEFAULT

Par défaut :

```text
private
minimal access
minimal retention
minimal indexing
minimal AI exposure
```

---

# 493. SECURITY BY DEFAULT

Par défaut :

```text
deny
audit
encryption where required
MFA for privileged
rate limit
RLS
```

---

# 494. FAIL CLOSED

Les systèmes critiques échouent fermé lorsqu'ils ne peuvent pas déterminer :

```text
tenant
identity
policy
permission
state
signature
```

---

# 495. DOMAIN TEST NAMING

Préférer :

```text
it('rejects posting into closed fiscal periods')
```

à :

```text
it('works')
```

Le test explique la règle métier.

---

# 496. TEST EXPLICITNESS

Un test critique doit montrer :

```text
Given
When
Then
```

ou une structure équivalente très lisible.

---

# 497. TEST ISOLATION BETWEEN DOMAINS

Une règle de domaine ne doit pas être accidentellement couverte uniquement par un test d'une autre couche.

---

# 498. CRITICAL INVARIANT DATABASE BACKSTOP

Lorsque possible :

```text
application invariant
+
database invariant
```

Exemple :

```text
unique payment idempotency key
```

---

# 499. CRITICAL INVARIANT TEST BACKSTOP

Chaque invariant critique possède :

```text
happy path
negative path
concurrency path
database constraint path
authorization path
replay path
```

---

# 500. DEFINITION FINALE DE MANECMS

ManeCMS est considéré comme correctement architecturé uniquement si :

```text
UNIVERSAL PRIMITIVES
+
TYPED DOMAINS
+
SINGLE OWNERSHIP
+
TRANSACTIONAL INTEGRITY
+
RLS
+
POLICY
+
OUTBOX
+
INBOX
+
PROCESS MANAGERS
+
READ MODELS
+
SEARCH
+
ANALYTICS
+
AI
+
ManeUI
+
TallStackUI
+
Livewire
+
TEST ENGINEERING
+
SECURITY
+
OBSERVABILITY
+
RECOVERY
+
BUSINESS TEMPLATES
+
EXTENSIBILITY
```

sont cohérents.

---

# 501. FORMULE FONDATRICE

```text
ONE IDENTITY
+
ONE TENANT MODEL
+
ONE PARTY MODEL
+
ONE POLICY MODEL
+
MANY DOMAIN ENGINES
+
ONE EVENT INFRASTRUCTURE
+
ONE DOCUMENT PLATFORM
+
ONE SEARCH PLATFORM
+
ONE ANALYTICS PLATFORM
+
ONE AI CONTROL PLANE
+
ONE DESIGN SYSTEM
+
MANY CHANNELS
=
MANECMS
```

---

# 502. RÈGLE ULTIME

ManeCMS ne doit pas résoudre la complexité en rendant tout générique.

Il doit résoudre la complexité en combinant :

```text
Universal where it is truly universal
+
Typed where semantics differ
+
Configurable where safe
+
Code-protected where critical
```

---

# 503. V1 ABSOLUE

La V1 contient :

```text
Platform
Identity
Tenancy
Organization
Party
MDM
Policy
Channel
Localization
Jurisdiction
CMS
Media
SEO
CRM
Marketing
Service
Community
Knowledge
LMS
Collaboration
Finance
Tax
Procurement
Inventory
WMS
Manufacturing
MRP
Quality
Assets
Maintenance
Fleet
Projects
PSA
Timesheets
HR
Payroll
Commerce
PIM
Pricing
Subscriptions
POS
Marketplace
Payments
Booking
Events
Ticketing
Membership
Documents
Contracts
Signatures
OCR
Workflow
Approval
Automation
Notifications
Search
Analytics
API
Integration Hub
AI
Audit
Compliance
Extensions
Business Templates
Portals
Offline
Reporting
Observability
DR
```

---

# 504. ETAT FINAL DU CAHIER DES CHARGES

Ce document ne doit pas être interprété comme :

```text
liste d'idées
```

mais comme :

```text
PRODUCT CONTRACT
+
ARCHITECTURE CONTRACT
+
DATA CONTRACT
+
SECURITY CONTRACT
+
UX CONTRACT
+
TEST CONTRACT
+
OPERATIONS CONTRACT
+
ACCEPTANCE CONTRACT
```

---

# 505. CRITÈRE ABSOLU D'ACHÈVEMENT

```text
Une fonctionnalité n'est pas finie
parce qu'elle est codée.

Elle est finie lorsque :

elle est modélisée
+
elle a un owner
+
elle a ses invariants
+
elle a ses transactions
+
elle a ses permissions
+
elle a ses événements
+
elle a ses read models
+
elle a son UI
+
elle a son API
+
elle a son audit
+
elle a son observabilité
+
elle a ses tests
+
elle a son acceptance
+
elle a sa documentation
+
elle a sa stratégie de panne
+
elle a sa stratégie de migration
+
elle respecte ManeUI/TallStackUI
+
elle passe les gates de release.
```

# 506. FIN

```text
MANECMS

Laravel Herd
+
Laravel Starter Kit Livewire
+
Livewire
+
Blade
+
Tailwind
+
TallStackUI
+
ManeUI
+
Alpine
+
PostgreSQL
+
Redis
+
Meilisearch
+
S3
+
Reverb
+
Pest
+
PHPStan
+
Larastan
+
Rector
+
Pint
+
AI
+
Analytics
+
Automation
+
Domain Engines
+
Universal Business Graph
```
