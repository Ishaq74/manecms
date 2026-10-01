# ADR 0001 — Monolithe modulaire

- **Statut** : accepté
- **Date** : 2026-10-01
- **Passe** : P00
- **Cahier** : §9, §10, §11, §11.1, §13, §208, §279, §371

## Contexte

ManeCMS couvre plus de soixante contextes métier (§503) qui partagent les mêmes primitives :
identité, tenant, Party, policy, argent, temps, audit. Le développement est mené par une petite
équipe, sous Laravel, avec une seule base PostgreSQL.

## Problème

Comment découper le code pour que chaque domaine garde ses règles et ses données sans
multiplier les déploiements ni dupliquer les primitives ?

## Options

1. **Microservices** — isolation forte, mais transactions distribuées, coût d'exploitation
   élevé et duplication des primitives.
2. **Monolithe sans frontières** — simple au départ, mais couplage entre domaines et
   invariants dispersés.
3. **Monolithe modulaire** — un seul déploiement, frontières explicites par contexte,
   transactions locales, effets inter-domaines par événements.

## Décision

Monolithe modulaire (option 3).

- Chaque contexte vit dans `App\Domain\<Context>` (§11.1), avec ses modèles, actions,
  policies, événements et tests.
- Chaque table appartient à un seul contexte, déclaré dans `config/domains.php` (§371).
- Un contexte n'écrit jamais dans les tables d'un autre ; il appelle une commande publique
  ou réagit à un événement publié via l'outbox (§122, §348).
- La logique métier ne dépend ni de Livewire ni de l'UI (§279) ; les tests d'architecture
  le vérifient.

## Conséquences

- Les frontières sont contrôlées par des tests d'architecture plutôt que par le réseau.
- L'extraction ultérieure d'un contexte en service reste possible, car ses entrées et
  sorties passent déjà par des commandes et des événements.
- Chaque nouvelle table doit être enregistrée dans `config/domains.php`.
