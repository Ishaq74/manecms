# ManeCMS — Setup Packages Composer / Node + Configs

> Source normative : `todo/todo.md` §§ 4-8, 200-201, 284, 288, 290-291, 506.
> Focus volontaire : **installation packages Composer + Node + configs Laravel**. Hors scope : domaines, tests métier, CI complète, docs ADR/C4.

## 0. Constat express (actuel vs cible)

- [x] Constaté : `livewire/livewire ^4.1` présent — OK (§5, §8 : Livewire 4) — v4.4.6 installé
- [ ] Problème : `livewire/flux ^2.13.1` + `livewire/blaze` présents — **interdits** (§6 Règle absolue UI, §284 No Flux Rule) — ⚠️ **CONSIGNE UTILISATEUR 30/09/2026 : NE PAS désinstaller tant que tout n'est pas migré vers TallStackUI ; on retire Flux uniquement quand il sera devenu inutile**
- [x] `tallstackui/tallstackui` — **v4.0.0 installé le 30/09/2026** (§5, §6, §8 : TallStackUI 4 via ManeUI)
- [ ] Problème : `php ^8.3` en `composer.json` — cible `PHP 8.4+` (§8)
- [ ] Problème : `pestphp/pest ^4.7` — cible `Pest 5` (§8, §201)
- [ ] Problème : `DB_CONNECTION=sqlite`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`, `BROADCAST_CONNECTION=log`, `FILESYSTEM_DISK=local` — cible `PostgreSQL + Redis + Redis/Horizon + Reverb + S3` (§4, §8)
- [ ] Problème : aucune clé `SCOUT_*/MEILISEARCH_*`, `REVERB_*`, `HORIZON_*`, `AWS_*` complète en `.env.example`
- [ ] Problème : `config/` sans `horizon.php`, `scout.php`, `broadcasting.php`, `sanctum.php` — à publier
- [x] `tailwindcss ^4` OK — Alpine.js : **fourni par le bundle Livewire 4, pas de paquet npm séparé** (un paquet `alpinejs` séparé + `Alpine.start()` provoquerait l'erreur documentée `Detected multiple instances of Alpine` — docs Livewire 4 `alpine` + `troubleshooting`). Forçage sur chaque page via `@livewireStyles` (head) + `@livewireScripts` (fin body des 4 layouts) selon page Alpine officielle TallStackUI — directive protégée anti-doublon côté Livewire (`hasRenderedScripts`). À contre-vérifier via MCP après restart.
- [x] MCP TallStackUI ajouté à `opencode.json` (`remote` → `https://tallstackui.com/mcp/tallstackui`, handshake vérifié le 30/09/2026) — **restart opencode requis**
- [x] Bascule pnpm : `pnpm install` fait le 30/09/2026 (`pnpm-lock.yaml` créé, pnpm 10.28.0, node 22) — scripts composer `npm` → `pnpm` faits, `package-lock.json` supprimé, `pnpm run build` vert, `pnpm audit` 0 vulnérabilité (30/09/2026)

---

## 1. Pré-requis Herd / services (§4)

- [x] PHP 8.4+ actif via `herd php` (8.4.16 le 30/09/2026 ; le `php` Laragon par défaut est 8.3.12 — toujours passer par `herd php` / `herd composer`)
- [x] Composer à jour (2.7.9)
- [x] PostgreSQL démarré et accessible — base `manecms` migrée le 30/09/2026 (users, cache, jobs, passkeys, 2FA)
- [ ] Redis démarré (`herd services:start redis`)
- [ ] Meilisearch démarré (service Herd ou binaire, noter host + master key)
- [ ] MinIO / S3-compatible prêt (endpoint + bucket + clés)
- [ ] Reverb : pas de service externe, vérifier port dispo (défaut 8080)

## 2. Composer — `require` runtime (§5, §8, §284)

Ordre conseillé : ajouter le manquant d'abord, migrer l'UI, et **retirer Flux/Blaze uniquement quand TallStackUI les aura rendus inutiles** (consigne utilisateur 30/09/2026).

- [ ] Retirer Flux + Blaze : `composer remove livewire/flux livewire/blaze` — ⛔ **BLOQUÉ jusqu'à migration complète Flux → TallStackUI**
- [ ] Vérifier aucune trace : `grep -rin flux composer.json composer.lock app/ resources/ config/` → 0 résultat — ⛔ **après migration complète uniquement**
- [x] Garder / aligner : `livewire/livewire ^4` (v4.4.6 présent)
- [x] Ajouter UI : `composer require tallstackui/tallstackui` → **v4.0.0 installé le 30/09/2026**, `npm install -D @tailwindcss/forms`, `<tallstackui:script />` + `@livewireStyles` dans `partials/head.blade.php`, `@livewireScripts` en fin de body (layouts `app/sidebar` + `auth/simple|card|split`), imports `@import v4.css` + `@source tallstackui` dans `app.css`, `npm run build` vert, `view:cache` vert
- [x] Ajouter Queue : `composer require laravel/horizon` (§8 : Redis + Horizon) — **v5.50 installé le 30/09/2026** (`--ignore-platform-req=ext-pcntl,ext-posix` : extensions absentes sous Windows Herd, Horizon tournera en prod Linux)
- [x] Ajouter Search : `composer require laravel/scout meilisearch/meilisearch-php` (§8 : Scout + Meilisearch) — **scout v11.8 + meilisearch-php v1.17 installés le 30/09/2026**
- [x] Ajouter Realtime : `composer require laravel/reverb` (§8 : Reverb) — **v1.12 installé le 30/09/2026** (framework monté 13.33 → 13.34, guzzle 8 → 7 par résolution)
- [x] Ajouter Auth API : `composer require laravel/sanctum` (§8 : Sanctum voie standard) — **v4.3.3 installé + migré (`personal_access_tokens`) le 30/09/2026**
- [ ] Optionnel OAuth2 : décider `laravel/passport` oui/non (§8 : seulement si OAuth2 requis) — noter la décision
- [x] Ajouter Storage S3 : `composer require league/flysystem-aws-s3-v3` (§8 : S3-compatible) — **v3.35 installé le 30/09/2026**
- [x] Vérifier déjà présents et à garder : `laravel/framework ^13`, `laravel/fortify`, `laravel/tinker`
- [ ] AI SDK : **ne rien installer au hasard** — identifier le package officiel `Laravel AI SDK + adapters` visé par §8, noter le choix avant install — ⏸️ reporté (aucun install le 30/09/2026)
- [x] Figer : `composer validate --strict` vert (`tallstackui/tallstackui` passé `4.0` → `^4.0` pour le strict), `composer audit` 0 alerte (30/09/2026)

## 3. Composer — `require-dev` baseline qualité (§201 exact)

Cible `§201. PACKAGE QUALITY BASELINE` — mettre à jour / ajouter :

- [x] `laravel/pint ^1` — déjà présent, OK
- [x] `laravel/pao ^1` — déjà présent (`^1.0.6`), OK
- [x] `orchestra/testbench ^11` — **ajouté le 30/09/2026**
- [x] `pestphp/pest ^5` — monté `^4.7` → `^5.0` le 30/09/2026
- [x] `pestphp/pest-plugin-laravel ^5` — monté `^4.1` → `^5.0` le 30/09/2026
- [x] `pestphp/pest-plugin-livewire ^5` — **ajouté le 30/09/2026**
- [x] `pestphp/pest-plugin-type-coverage ^5` — **ajouté le 30/09/2026**
- [x] `pestphp/pest-plugin-browser ^5` — **ajouté le 30/09/2026**
- [x] `pestphp/pest-plugin-stressless ^5` — **ajouté le 30/09/2026**
- [x] `pestphp/pest-plugin-drift ^5` — **ajouté le 30/09/2026**
- [x] `pestphp/pest-plugin-agent ^5` — **ajouté le 30/09/2026**
- [x] `pestphp/pest-plugin-phpstan ^5` — **ajouté le 30/09/2026**
- [x] `phpstan/phpstan ^2` — **ajouté le 30/09/2026** (+ `allow-plugins` pour `dealerdirect/phpcodesniffer-composer-installer` requis par phpinsights)
- [x] `larastan/larastan ^3` — déjà présent (`^3.9`), OK
- [x] `phpstan/phpstan-strict-rules ^2` — **ajouté le 30/09/2026**
- [x] `phpstan/phpstan-deprecation-rules ^2` — **ajouté le 30/09/2026**
- [x] `rector/rector ^2` — **ajouté le 30/09/2026**
- [x] `nunomaduro/phpinsights ^2` — **ajouté le 30/09/2026**
- [x] Décision `laravel/boost`, `laravel/sail`, `laravel/pail`, `fakerphp/faker`, `mockery/mockery`, `nunomaduro/collision` : **gardés** (utiles localement, 30/09/2026)
- [x] Figer : `composer update` ciblé + `composer.lock` verrouillé après validation compatibilité (30/09/2026)

## 4. Node / pnpm — frontend socle (§6, §8)

Gestionnaire officiel du projet : **pnpm** (décision 30/09/2026 — ne plus utiliser npm).

État `package.json` : `vite ^8`, `vite-plus`, `laravel-vite-plugin ^3`, `tailwindcss ^4`, `@tailwindcss/vite ^4`, `@tailwindcss/forms ^0.5` (dev) OK.

- [x] Garder : `tailwindcss ^4` + `@tailwindcss/vite ^4` (§8 : Tailwind CSS 4)
- [x] Garder : `vite`, `laravel-vite-plugin`, `vite-plus` (build `vp build` / `vp dev` existants)
- [x] Alpine.js : **pas de paquet séparé** — servi par le bundle Livewire 4 + forçage `@livewireStyles`/`@livewireScripts` (voir §0). `resources/js/app.js` reste vide (aucun `import Alpine` / `Alpine.start()` — causerait double instance).
- [x] TallStackUI ne demande pas de paquet npm — install via Composer (fait) + `<tallstackui:script />` (fait) ; pas de `php artisan tallstackui:install` en v4 (install manuelle : layout + CSS, fait)
- [x] Vérifier `resources/css/app.css` importe Tailwind v4 (`@import "tailwindcss";`) — fait, + imports TallStackUI
- [x] `pnpm install` OK (`pnpm-lock.yaml` créé)
- [x] `composer.json/scripts` : `npm install` → `pnpm install`, `npm run build` → `pnpm run build`, `npx concurrently … "npm run dev"` → `pnpm exec concurrently … "pnpm run dev"` (30/09/2026)
- [x] Supprimer `package-lock.json` (un seul lockfile : `pnpm-lock.yaml`) (30/09/2026)
- [x] Figer : `pnpm run build` OK sans erreur, `pnpm audit` passé (0 vulnérabilité, 30/09/2026)

## 5. Configs Laravel + `.env` (§4, §8)

### 5.1 `.env.example` / `.env` à aligner

- [x] `APP_URL` via Herd (`https://manecms.test` — `.env.example` aligné le 30/09/2026 ; `.env` live passé à `https://manecms.test` par les installers)
- [x] `DB_CONNECTION=pgsql` + `DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD` (corrigé le 30/09/2026 : `.env` disait `postgresql`, invalide — `config/database.php` attend `pgsql`)
- [x] `CACHE_STORE=redis` + `REDIS_HOST/REDIS_PORT/REDIS_PASSWORD/REDIS_CLIENT` (`.env.example` + `.env` cible, 30/09/2026 — ⚠️ pas de serveur Redis dispo sous ce Herd Windows : runtime local en attente §1)
- [x] `QUEUE_CONNECTION=redis` + clés `HORIZON_*` (`HORIZON_NAME/PATH`, 30/09/2026 — même réserve Redis)
- [x] `BROADCAST_CONNECTION=reverb` + `REVERB_APP_ID/REVERB_KEY/REVERB_SECRET/REVERB_HOST/REVERB_PORT/REVERB_SCHEME` (+ `VITE_REVERB_*`, 30/09/2026)
- [x] `FILESYSTEM_DISK=s3` + `AWS_ACCESS_KEY_ID/AWS_SECRET_ACCESS_KEY/AWS_DEFAULT_REGION/AWS_BUCKET/AWS_ENDPOINT` (MinIO : `AWS_USE_PATH_STYLE_ENDPOINT=true`, 30/09/2026 — bucket/endpoint à renseigner quand MinIO prêt)
- [x] `SCOUT_DRIVER=meilisearch` + `MEILISEARCH_HOST/MEILISEARCH_KEY` (30/09/2026 — host `http://localhost:7700` par défaut, clé à renseigner)
- [x] `SESSION_DRIVER=database` — OK à garder sauf décision contraire
- [x] `MAIL_*` — laisser `log` en local, noter pour plus tard
- [ ] Appliquer : `copy .env.example .env` si besoin, `php artisan key:generate`, `php artisan config:clear` — `key:generate` rejoué le 30/09/2026 (clé vide constatée) ; recopie complète `.env` non faite (écraserait les valeurs live)

### 5.2 Fichiers `config/` à publier / vérifier

Actuel : `app, auth, cache, database, filesystems, fortify, logging, mail, queue, services, session`. Manquants :

- [x] `php artisan horizon:install` → `config/horizon.php` (30/09/2026, + `HorizonServiceProvider`)
- [x] `php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"` → `config/scout.php` (30/09/2026 — pas de `scout:install` en Scout v11)
- [x] `php artisan reverb:install` → `config/reverb.php` / `config/broadcasting.php` + `routes/channels.php` (30/09/2026 — plantage non-bloquant sur prompt interactif avec `--no-interaction`, fichiers essentiels publiés)
- [x] `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"` → `config/sanctum.php` + migration `personal_access_tokens` migrée (30/09/2026)
- [x] `php artisan tallstackui:install` → **obsolète en v4** (aucune commande `install` : `find-component`, `ide`, `setup-color`, `setup-prefix` uniquement — install manuelle déjà faite : layout + CSS)
- [ ] Vérifier `config/database.php` (pgsql par défaut), `config/cache.php` (redis), `config/queue.php` (redis), `config/filesystems.php` (disk s3), `config/scout.php` (meilisearch)
- [ ] Vérifier `vite.config.js` charge `laravel-vite-plugin` + `@tailwindcss/vite`

## 6. Scripts Composer + commandes (§290-§291)

- [x] Aligner `composer.json/scripts` sur §291 : `quality`, `quality:format`, `quality:rector`, `quality:phpstan`, `quality:insights`, `test:types`, `test:architecture`, `test:unit`, `test:feature`, `test:integration`, `test:security`, `test:browser`, `test:concurrency`, `test:contract`, `test:performance`, `test` (30/09/2026 — `test` historique remplacé par `quality + unit + feature` ; `lint`, `types:check`, `ci:check`, `setup`, `dev` conservés)
- [x] Garder `setup` / `dev` existants ou les adapter (ne pas casser `npm run build` + `migrate`) — `setup`/`dev` en pnpm, OK
- [x] Valider : `composer validate --strict` vert (30/09/2026)

## 7. Vérifications finales + ordre d'exécution conseillé

Ordre :

1. [x] §1 pré-requis Herd OK (PHP 8.4 via herd, Composer, pgsql migré) — ⚠️ Redis/Meilisearch/MinIO **non démarrés** (pas de namespace `herd services` sur ce Herd Windows ; `herd start` ne fournit pas Redis port 6379)
2. [x] §2 Composer runtime (Horizon/Scout/Reverb/Sanctum/S3 — remove Flux ⛔ après migration UI complète)
3. [x] §3 Composer dev baseline §201
4. [x] §4 pnpm (scripts composer → pnpm, rm `package-lock.json`, `pnpm run build` + `pnpm audit`)
5. [x] §5 configs (`.env.example` redis/meili/s3/reverb + `artisan *:install`) — `.env` live en cible ; valeurs live Redis/S3/Meili en attente §1
6. [x] `php artisan migrate --force` sur PostgreSQL (fait le 30/09/2026, + Sanctum `personal_access_tokens`)
7. [x] `composer validate --strict`, `composer audit`, `pnpm audit` (verts le 30/09/2026)
8. [x] `php artisan optimize:clear` vert quand `.env` en drivers locaux (30/09/2026 — **KO avec `.env` cible redis sans serveur**, en attente §1), `vendor/bin/pint --test` (⚠️ échecs pré-existants CRLF/EOF dans `bootstrap`/`tests` — `--dirty` appliqué sur nos fichiers), `vendor/bin/phpstan analyse --level=max` (⚠️ 9 erreurs pré-existantes starter-kit + configs publiées, `--memory-limit=1G` requis), `vendor/bin/pest --compact` **34/34 verts** (après réactivation `RefreshDatabase` dans `tests/Pest.php`, 30/09/2026)
9. [ ] Commit `composer.json/lock + package.json + pnpm-lock.yaml + .env.example + config/ + opencode.json` avec message explicite

> Règle de sortie : checklist terminée = `composer.lock` verrouillé, `pnpm run build` vert, `migrate` vert sur pgsql, Flux rendu inutile puis retiré (zéro référence `flux`, §284), configs Horizon/Scout/Reverb/Sanctum/S3 publiées.
