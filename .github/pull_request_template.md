## Passe

<!-- PXX — titre, lien vers todo/pass-XX-*.md -->

## Contrat de revue (cahier §396)

- **Domaine** :
- **Agrégat** :
- **Invariants** :
- **Tables et migrations** :
- **Policies** :
- **Événements** :
- **Projections** :
- **Audit** :
- **Observabilité** :
- **Risque** : VITAL | STANDARD | EXTENSIBLE
- **ADR** :

## Revue données (§399)

Pour chaque nouvelle colonne : owner, type, nullable, FK, tenant scope, RLS, classification,
rétention, index, recherche, analytics, IA, snapshot.

## Revue sécurité (§398)

- [ ] Nouvelle donnée personnelle, permission, route, fichier, secret, intégration ou outil IA identifiés
- [ ] Isolation tenant testée
- [ ] Refus d'autorisation testés

## Revue design (§397)

- [ ] Composants ManeUI, aucune référence à Flux
- [ ] États vide, chargement, erreur, refus
- [ ] Clavier, sombre, responsive, textes `fr` et `en`

## Gates

- [ ] `herd php artisan migrate:fresh`
- [ ] `herd php artisan test --compact`
- [ ] `herd php bin/check.php --full`
- [ ] `pnpm run build`
- [ ] Cases de la passe cochées dans `todo/roadmap.md` et `README.md`
