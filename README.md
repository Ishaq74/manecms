# ManeCMS

Plateforme métier universelle construite sur Laravel : CMS, CRM, finance, commerce, réservations,
projets, RH, automatisation et IA, réunis sur un même socle multi-tenant.

## Documents de référence

| Document | Rôle |
|:---------|:------|
| [todo/todo.md](todo/todo.md) | Cahier des charges normatif. Décisions produit provisoires : §0.1 |
| [todo/roadmap.md](todo/roadmap.md) | Feuille de route : passes, Definition of Done, traçabilité |
| [todo/pass-01-tenancy-core.md](todo/pass-01-tenancy-core.md) | Spécification détaillée de la passe en cours |

## Stack

- PHP 8.4, Laravel 13, Livewire 4, Fortify
- Blade, Tailwind CSS 4, TallStackUI 4, ManeUI, Alpine.js
- PostgreSQL
- Pest 5, PHPStan + Larastan, Rector, Pint, PHP Insights

Redis, Meilisearch, S3 et Reverb sont activés par la passe qui en a besoin (cahier §4.2).

## Installation locale

Prérequis : [Laravel Herd](https://herd.laravel.com) avec PostgreSQL, Node.js et pnpm.

```bash
composer setup
```

Le site est servi par Herd sur `https://manecms.test`. Pour les assets en développement :

```bash
pnpm run dev
```

## Qualité

```bash
herd php bin/check.php          # Pint, Rector, PHPStan, Pest
herd php bin/check.php --full   # + PHP Insights et type coverage
herd php bin/check.php --fix    # corrige le style et applique Rector
herd php artisan test --compact
pnpm run build
```

## Roadmap

Chaque passe a ses livrables, invariants, tests et critères d'acceptation détaillés dans
[todo/roadmap.md](todo/roadmap.md). Une passe est cochée lorsqu'elle est acceptée.

### Train V1.0 — noyau universel + freelance / services France

- [ ] **P00** Mise en conformité du socle
- [x] **P01** Tenancy core — dépend de P00
- [ ] **P02** Isolation PostgreSQL (RLS) — P01
- [ ] **P03** Audit, corrélation, erreurs typées — P02
- [ ] **P04** ManeUI : fondations — P01
- [ ] **P05** Membres, invitations, RBAC, Policy Engine — P03, P04
- [ ] **P06** Back-office opérateur — P05
- [ ] **P07** Localisation et temps — P04
- [ ] **P08** Money et Quantity — P04
- [ ] **P09** Configuration, settings, feature flags — P05
- [ ] **P10** Infrastructure d'événements (outbox, inbox, retry) — P03
- [ ] **P11** Notifications et emails — P07, P10
- [ ] **P12** Party et MDM core — P05, P07, P10
- [ ] **P13** Organisation, entités légales, sites — P08, P09, P12
- [ ] **P14** Fichiers et médias — P03, P04, P09
- [ ] **P15** Workflow : états, approbations, process managers — P05, P10, P11
- [ ] **P16** Numérotation et rendu documentaire — P13, P14
- [ ] **P17** CMS core — P14, P15
- [ ] **P18** Canaux, sites publics, SEO — P17
- [ ] **P19** Page builder — P18
- [ ] **P20** Blog et Knowledge — P18
- [ ] **P21** CRM — P12, P15, P18
- [ ] **P22** Catalogue et tarification de base — P08, P13
- [ ] **P23** Moteur fiscal et pack France — P22
- [ ] **P24** Devis et contrats légers — P16, P21, P23
- [ ] **P25** Finance core (grand livre) — P13, P15, P23
- [ ] **P26** Facturation client et facture électronique FR — P24, P25
- [ ] **P27** Paiements — P26
- [ ] **P28** Banque et rapprochement — P27
- [ ] **P29** Projets, temps, facturation au temps — P24, P26
- [ ] **P30** Dépenses et fournisseurs (AP léger) — P26, P28
- [ ] **P31** Portail client — P27, P29
- [ ] **P32** Recherche — P21, P26
- [ ] **P33** Analytics, rapports, tableaux de bord — P28, P29
- [ ] **P34** API v1, webhooks, import/export — P30
- [ ] **P35** Plateforme IA — P32, P34
- [ ] **P36** Conformité RGPD core — P30
- [ ] **P37** Business templates V1.0 — P31, P33
- [ ] **P38** Abonnement SaaS de la plateforme — P27, P06
- [ ] **P39** Préparation production — P35 à P38
- [ ] **G1 — Gate V1.0** — P00 à P39 acceptées, acceptance freelance de bout en bout

### Trains V1.x — vers la V1 absolue

- [ ] **P40** Marketing, consentement, communication center — G1
- [ ] **P41** Service et case management — G1
- [ ] **P42** Collaboration et temps réel — G1
- [ ] **P43** Community — P42
- [ ] **P44** LMS — P42
- [ ] **P45** DMS, contrats, e-signature, OCR — G1
- [ ] **P46** PIM, catalogues, promotions — G1
- [ ] **P47** Inventaire (stock ledger) — P46
- [ ] **P48** Commerce : panier, checkout, commandes, retours — P47
- [ ] **P49** Abonnements clients — P48
- [ ] **P50** WMS et portail entrepôt — P48
- [ ] **P51** Achats et portail fournisseur — P47, P41
- [ ] **P52** Booking et ressources — G1
- [ ] **P53** Événements et billetterie — P52
- [ ] **P54** Adhésions et dons — P49
- [ ] **P55** POS et mode offline — P48
- [ ] **P56** Marketplace et portail vendeur — P48
- [ ] **P57** Multi-devises, consolidation, intercompany — G1
- [ ] **P58** Immobilisations, maintenance, flotte — P51
- [ ] **P59** Production, MRP, qualité — P51
- [ ] **P60** RH / HCM et portail employé — G1
- [ ] **P61** Paie — P60
- [ ] **P62** Moteur d'automatisation — G1
- [ ] **P63** Champs personnalisés et no-code — P62
- [ ] **P64** Extensions et SDK — P63
- [ ] **P65** Portails partenaire et intervention terrain — P55
- [ ] **P66** UX avancée, vues 360, espagnol et arabe — G1
- [ ] **P67** Packs verticaux — P52 à P59
- [ ] **P68** Juridictions supplémentaires — P57, P61
- [ ] **P69** Excellence opérationnelle — P68
- [ ] **G2 — Gate V1 absolue** — P00 à P69 acceptées, gates §394 et §395 du cahier
