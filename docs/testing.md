# Tests & Quality Gate — ManeCMS

Documentation de référence pour la stack de test et de qualité du projet :
**Pest 5.2** (au-dessus de PHPUnit 13) sur **Laravel 13**, Livewire 4, Fortify, Horizon, Scout, Reverb.

> **Réponse courte à la question fréquente :**
> `herd php artisan test --compact` ne lance **que les tests** (suites `Unit` + `Feature`).
> Il ne lance **ni Pint, ni Rector, ni Larastan/PHPStan, ni PHP Insights.**
>
> Pour tout lancer d'un coup : **`herd php bin/check.php`** (voir §3).

---

## 1. prérequis

### 1.1 Version PHP — **PHP 8.4.1 minimum, obligatoire**

`composer.json` déclare `"php": "^8.3"`, mais c'est **faux en pratique** : PHPUnit 13 et Symfony 8
(exigés par Laravel 13) demandent `>= 8.4.1`. Le fichier `vendor/composer/platform_check.php`
refuse de démarrer avec PHP 8.3 :

```md
Composer detected issues in your platform: Your Composer dependencies require a PHP version ">= 8.4.1".
```

Sur cette machine, le `php` du `PATH` pointe vers **Laragon 8.3.12**
(`C:\laragon2\bin\php\php-8.3.12-...`), ce qui casse **tous** les scripts Composer et `artisan test`.

### 1.2 Le vrai correctif : mettre le PHP 8.4 de Herd en tête du PATH

Les deux solutions disponibles :

| | Avantage | Inconvénient |
|---|---|---|
| **A. PATH (recommandé)** | `php artisan test`, `composer test`, `composer check` fonctionnent, **et les codes de sortie sont corrects** | une commande à lancer par session |
| **B. `herd php …`** | rien à configurer | **mange le code de sortie** (retourne toujours 0) |

```powershell
# A. À exécuter dans PowerShell (session courante)
$env:Path = "$env:USERPROFILE\.config\herd\bin\php84;" + $env:Path

# Vérification
php -v                      # doit afficher PHP 8.4.16
```

Rendre le changement permanent : `setx PATH "$env:USERPROFILE\.config\herd\bin\php84;%PATH%"` (puis
redémarrer le terminal), ou via *Variables d'environnement* dans les propriétés système Windows.

> ⚠️ **Piège important** : `herd php …` (le proxy Herd sous Windows) **retourne toujours 0**,
> même quand la commande échoue. Vérifié :
> ```
> & "$env:USERPROFILE\.config\herd\bin\php84\php.exe" bin/check.php --only=bogus   → exit 1 ✅
> herd php bin/check.php --only=bogus                                                → exit 0 ❌
> ```
> Pour un pipeline ou une CI, utilisez **A**.

Si le PATH est déjà correct, toutes les commandes de cette doc s'écrivent sans préfixe :
`php artisan test --compact`, `composer check`, etc.

### 1.3 Extensions PHP

Présentes dans le PHP 8.4 de Herd : `pdo_sqlite`, `sqlite3`, `pdo_pgsql`, `mbstring`, `intl`, `dom`,
`curl`, `redis`, `gd`, `zip`, `bcmath`, `sodium`.

**Absentes** : `xdebug` et `pcov` → la **couverture de code est indisponible** (§8).
`memory_limit` est à **128M** (défaut Herd) → le **type coverage** échoue sans contournement (§9.2).

### 1.4 Base de données de test

Aucune installation : `phpunit.xml` force **SQLite en mémoire** (`:memory:`).
Les migrations sont rejouées à chaque exécution, sur une base détruite à la fin.
Aucun risque de toucher la base Postgres de développement.

> ⚠️ **Le piège n°1 du projet** : le dev utilise `pgsql`, les tests utilisent `sqlite`.
> Toute migration doit rester **compatible SQLite**, sinon la suite casse alors que le dev local
> fonctionne.

---

## 2. Ce qui est installe

| Outil | Version | Role |
|---|---|---|
| `pestphp/pest` | 5.2 | Framework de test (syntaxe fonctionnelle) |
| `phpunit/phpunit` | 13.3 | Moteur sous-jacent |
| `pestphp/pest-plugin-laravel` | 5.x | Helpers Laravel (`get()`, `actingAs()`, assertions Livewire/Fortify) |
| `pestphp/pest-plugin-livewire` | 5.x | `livewire()` : tests de composants Livewire |
| `pestphp/pest-plugin-arch` | 5.x | Tests d'architecture (`arch()->expect()`) |
| `pestphp/pest-plugin-type-coverage` | 5.x | `--type-coverage` : % d'arguments types |
| `pestphp/pest-plugin-drift` | 5.x | `--drift` : migration automatique PHPUnit → Pest |
| `pestphp/pest-plugin-browser` | 5.x | Tests navigateur (Playwright) |
| `pestphp/pest-plugin-stressless` | 5.x | Detection des tests lents |
| `pestphp/pest-plugin-phpstan` | 5.x | Extension PHPStan pour Pest |
| `pestphp/pest-plugin-agent` | 5.x | `--ai` : génère un test depuis un snippet |
| `larastan/larastan` | 3.x | PHPStan pour Eloquent/Laravel |
| `phpstan/phpstan` | 2.x | Analyse statique (`phpstan.neon`, level 7) |
| `laravel/pint` | 1.x | Formatage PSR-12 + preset `laravel` |
| `rector/rector` | 2.x | Refactoring automatique (config : `rector.php`) |
| `nunomaduro/phpinsights` | 2.x | Score de qualité de code |
| `brianium/paratest` | 7.x | exécution parallèle (`--parallel`) |
| `laravel/pao` | 1.x | **Agent-optimized output** : force `--no-output --no-progress` |
| `laravel/boost` | 2.x | Guidelines + MCP |

### 2.1 ⚠️ `laravel/pao` : pourquoi la sortie est du JSON

Le package `laravel/pao` (enregistré via `extra.pest.plugins` dans son `composer.json`)
**rajoute automatiquement `--no-output` et `--no-progress` a chaque run Pest et Pint**.
résultat : une seule ligne JSON.

```
$ php artisan test --compact
{"tool":"pest","result":"passed","tests":34,"passed":34,"assertions":83,"duration_ms":5663}

$ vendor/bin/pint --test
{"tool":"pint","result":"fail","files":[{"path":"tests\\Unit\\ExampleTest.php","fixers":["single_blank_line_at_eof"]}]}
```

C'est voulu (optimisé pour les agents IA) mais illisible en local. Combine avec `--testdox`,
PHPUnit avertit :

```
1) Options --no-output and --testdox cannot be used together
```

**Pour retrouver la sortie classique** (barre de progression, diffs détails) :

```bash
composer remove --dev laravel/pao        # désactive le plugin
composer require --dev laravel/pao       # pour le remettre
```

En attendant, `bin/check.php` affiche un **tableau de synthèse** (stage / PASS-FAIL / durée)
qui rend la sortie lisible même avec `pao` actif.

---

## 3. La commande unique : `bin/check.php`

C'est le réponse a « comment je lance tout ? ». Le script enchaîne les étapes dans l'ordre
**le moins cher d'abord**, s'arrête au **premier échec**, et affiche un résumé.

```powershell
herd php bin/check.php            # le gate standard
herd php bin/check.php --full     # + PHP Insights + type coverage (lent)
herd php bin/check.php --fix      # écrit les corrections (Pint + Rector)
herd php bin/check.php --dirty    # uniquement les fichiers modifiés par Git
herd php bin/check.php --help
```

### 3.1 Les stages, dans l'ordre

| # | Stage | Commande exécutée | Ce que ca vérifie | durée actuelle |
|---|---|---|---|---|
| 1 | `pint` | `vendor/bin/pint --parallel --test` | Formatage PSR-12 / preset Laravel | ~5 s |
| 2 | `rector` | `vendor/bin/rector process --dry-run` | Refactoring automatique possible | ~3 s |
| 3 | `phpstan` | `vendor/bin/phpstan analyse --no-progress` | Types, Larastan/Eloquent (level 7) | ~13 s |
| 4 | `pest` | `vendor/bin/pest` | Suites `Unit` + `Feature` | ~7 s |
| 5 | `insights` *(uniquement `--full`)* | `php artisan insights` | Score de qualité (preset `laravel`) | ~8 s |
| 6 | `type-coverage` *(uniquement `--full`)* | `vendor/bin/pest --type-coverage` | % d'arguments types | ~13 s |

**Pourquoi cet ordre** : le formatage est instantané et filtre le plus de fichiers ;
l'analyse statique est plus lente mais déterministe ; les tests sont les plus lents et les plus
bruyants, donc en dernier on n'a pas attendu 20 s pour découvrir un point-virgule manquant.

### 3.2 Options

| Option | Effet |
|---|---|
| `--fix` | Pint **et** Rector **écrivent** leurs corrections au lieu de seulement les rapporter |
| `--dirty` | `--dirty` ajoute a Pint, PHPStan et Pest : ne traite que les fichiers modifiés selon Git |
| `--full` | Ajoute les stages lents `insights` et `type-coverage` |
| `--only=<stages>` | N'exécute que ces stages (liste séparée par des virgules) |
| `--skip=<stages>` | Saute ces stages |
| `--help` | Affiche l'aide |

```powershell
herd php bin/check.php --only=pint,pest        # formatage + tests
herd php bin/check.php --skip=phpstan          # tout sauf l'analyse statique
herd php bin/check.php --only=pest --dirty     # tests des fichiers modifiés
```

### 3.3 Sortie

```
====  pint — Laravel Pint — code style
{"tool":"pint","result":"fail","files":[...]}
====  phpstan — PHPStan + Larastan — static analysis
{"tool":"phpstan","result":"failed","errors":2,...}
============================================================
STAGE            RESULT     DURATION
------------------------------------------------------------
pint             FAIL       4.84s
phpstan          FAIL       12.36s
------------------------------------------------------------
Not run: rector, pest, insights, type-coverage
1 stage(s) failed.
```

- **`Not run:`** liste les stages sautés parce qu'un précédent a échoué (arrêt au premier échec).
- Le **code de sortie** vaut `0` si tout passe, `1` sinon (⚠️ voir §1.2 : `herd php` le masque).
- Un stage dont le fichier de configuration manque affiche un `WARNING` explicite
  (actuellement `config/insights.php`), pour éviter un faux vert.

### 3.4 état actuel du projet (30/09/2026)

| Stage | résultat | détail |
|---|---|---|
| `pint` | ✅ **PASS** | 57 fichiers |
| `rector` | ✅ **PASS** | `rector.php` présent, 0 changement restant |
| `phpstan` | ✅ **PASS** | 0 erreur (level 7 + Larastan) |
| `pest` | ✅ **PASS** | 34 tests, 83 assertions |
| `insights` | ✅ **PASS** | grâce à `config/insights.php` (§5.6) |
| `type-coverage` | ✅ **PASS** | 96,9 % |

**Les 6 stages passent.** La suite complète prend ~30 s.

Ce qui avait été corrigé pour y arriver :

- 11 fichiers de tests avec une ligne vide en trop à la fin (`--fix`)
- 14 fichiers sans type de retour `: void` sur les closures (`--fix` sur Rector)
- `config/horizon.php:72` et `config/sanctum.php:21` : `env()` renvoie `bool|string|null`, il fallait un `(string)` avant de le passer à `Str::slug()` / `explode()`
- `config/insights.php` publié, avec `SyntaxCheck` retiré (incompatible Windows)
- `rector.php` créé (sans lui le stage passait au vert sans rien analyser)

### 3.5 Scripts Composer associés

```json
"check":      "@php bin/check.php"
"check:fix":  "@php bin/check.php --fix"
"check:dirty":"@php bin/check.php --dirty"
"check:full": "@php bin/check.php --full"
```

```powershell
composer check        # ⚠️ utilise le php du PATH → 8.3 ici, échoué (§1.1)
herd composer check   # ✅ mais masque le code de sortie (§1.2)
herd php bin/check.php   # ✅ le plus fiable
```

### 3.6 Comparaison avec `composer test` / `composer quality`

| | `composer test` | `bin/check.php` |
|---|---|---|
| Ordre contrôlé | non (séquence de scripts) | oui |
| arrêt au premier échec | non (continue) | **oui** |
| résumé final | non | **oui** |
| Ignore la version PHP | non (crash opaque) | **oui, message clair** |
| Détecte une config manquante | non | **oui (WARNING)** |
| Corrige le `memory_limit` du type coverage | non | **oui (`-d memory_limit=2G`)** |
| `--fix` applique Pint **et** Rector | non (scripts séparés) | **oui** |
| Commande unique | non | **oui** |

---

## 4. Les commandes de test exactes

> Toutes suppose que le PATH est corrige (§1.2). Sinon préfixez par `herd php `.

### 4.1 Les 6 commandes a connaitre par cœur

```bash
# 1) TOUTE la suite de tests (Unit + Feature)
php artisan test --compact

# 2) Un seul fichier
php artisan test tests/Feature/Auth/AuthenticationTest.php

# 3) Un seul test, par nom
php artisan test --filter="test_user_can_login"

# 4) Un groupe de fichiers
php artisan test tests/Feature/Settings --compact

# 5) En parallèle
php artisan test --parallel --compact

# 6) Uniquement les tests modifiés (git dirty)
php vendor/bin/pest --dirty
```

> ⚠️ Sur cette machine `--parallel` **ne sert a rien** : 34 tests = 20 s parallèle contre 23 s
> séquentiel. Le coût de démarrage de ParaTest dépasse le gain. A garder au-dela de ~500 tests.

### 4.2 Options réelles de `php artisan test`

`php artisan test --help` n'accepte que ces options (les flags Pest/PHPUnit sont passes
directement a `vendor/bin/pest` si `artisan` ne les connait pas) :

| Option | Effet |
|---|---|
| `--compact` | Sortie compacte (recommande, compatible `pao`) |
| `--coverage` | Collecte la couverture (necessite xdebug/pcov — §8) |
| `--min[=N]` | Seuil minimum de couverture, échoué en dessous |
| `-p`, `--parallel` | exécution parallèle via ParaTest |
| `--profile` | Top 10 des tests les plus lents |
| `--recreate-databases` | recréer les bases de test avant le run |
| `--drop-databases` | Supprimer les bases de test après le run |
| `--without-databases` | Ne pas préparer les bases |
| `--without-cache` | Ne pas préparer le cache |
| `--without-tty` | désactiver la sortie TTY (utile en CI / quand la sortie est bizarre) |

`--recreate-databases` / `--drop-databases` n'ont aucun effet ici (SQLite `:memory:`),
mais ils comptent si vous basculez sur PostgreSQL (§5.4).

### 4.3 Tester uniquement les tests modifiés

```bash
php vendor/bin/pest --dirty
```

Analyse le diff Git et ne lance que les fichiers de tests touches. Ideal avant de pousser.

### 4.4 Filtrage avance

```bash
php artisan test --filter="login"                 # par nom de méthode / regex
php vendor/bin/pest --exclude-filter="SlowTest"  # exclure un motif
php vendor/bin/pest --list-tests                 # lister sans exécuter
php vendor/bin/pest --list-test-files
php vendor/bin/pest --list-suites
php vendor/bin/pest --testsuite=Unit             # Unit ou Feature (seules dans phpunit.xml)
php vendor/bin/pest --testsuite=Feature
php vendor/bin/pest --exclude-testsuite=Unit
```

### 4.5 contrôle du cycle d'exécution

```bash
php vendor/bin/pest --bail                    # arrêt au 1er échec (feedback immédiat)
php vendor/bin/pest --retry                   # relance les tests en échec en premier
php vendor/bin/pest --repeat=3                # chaque test 3 fois (Détecte l'alatoire)
php vendor/bin/pest --fail-on-warning --fail-on-risky --fail-on-deprecation
php vendor/bin/pest --fail-on-empty-test-suite   # échoué si 0 test trouve
php vendor/bin/pest --stop-on-failure         # idem --bail, syntaxe PHPUnit
```

### 4.6 Ordre d'exécution

```bash
php vendor/bin/pest --order-by=random                 # Détecte les tests dépendants
php vendor/bin/pest --order-by=random --random-order-seed=42
php vendor/bin/pest --order-by=duration              # les plus lents d'abord
php vendor/bin/pest --reverse-order
```

### 4.7 Test Impact Analysis (TIA) — Pest 5

```bash
php vendor/bin/pest --tia                  # ne rejoué que les tests impactés, le reste vient du cache
php vendor/bin/pest --tia --fresh          # reconstruit le graphe de dépendances
php vendor/bin/pest --tia --filtered        # restreint aux fichiers affectés
php vendor/bin/pest --tia --locally         # TIA uniquement en local
php vendor/bin/pest --baseline              # affiche le dossier de stockage du graphe
php vendor/bin/pest --no-tia                # désactive la TIA pour ce run
```

### 4.8 Generation d'un test par IA

```bash
php vendor/bin/pest --ai "App\Models\User::isActive()"
php vendor/bin/pest --ai "App\Services\BillingService::chargé()"
```

### 4.9 Tests navigateur (`pest-plugin-browser`)

Le dossier **`tests/Browser` n'existe pas** et **n'est pas déclaré dans `phpunit.xml`**.
Pour l'activer :

1. créer le dossier et l'enregistrer dans `phpunit.xml` :
   ```xml
   <testsuite name="Browser">
       <directory>tests/Browser</directory>
   </testsuite>
   ```
2. Les tests navigateur ont besoin d'un serveur qui tourne (contrairement au reste) :
   ```bash
   herd start          # ou : composer dev  (serve + queue + vite)
   ```
3. écrire le test (`tests/Browser/LoginTest.php`) :
   ```php
   <?php

   test('user can log in', function () {
       visit('/login')
           ->type('email', 'john@example.com')
           ->type('password', 'password')
           ->press('Log in')
           ->assertPathIs('/dashboard');
   });
   ```
4. Lancer :
   ```bash
   php artisan test tests/Browser --compact
   ```

> C'est le seul type de test du projet qui dépend du réseau et d'un serveur web actif.

### 4.10 Mutation testing (`--mutate`) — qualité des tests

```bash
php vendor/bin/pest --mutate --covered-only --parallel
php vendor/bin/pest --mutate --class="App\Models\User"
php vendor/bin/pest --mutate --min=80          # seuil de mutation score
php vendor/bin/pest --mutate --profile         # top 10 des mutations les plus lentes
```

Principe : le moteur mute votre code (`+1`, `true → false`, supprime une ligne) et vérifie qu'un
test échoué. Si aucun test ne casse, la mutation est « survivante » = votre test ne testé pas ce cas.

### 4.11 `--drift` : migrer du code PHPUnit vers Pest

```bash
php vendor/bin/pest --drift                    # analyse tests/ et propose la conversion
php vendor/bin/pest --drift tests/Legacy      # sur un dossier précis
```

Convertit automatiquement : `extends TestCase` → `uses(TestCase::class)`, `namespace` → suppression,
`setUp()` → `beforeEach()`, `$this->assertX()` → `expect()->toX()`, attributs de groupe, data
providers, `@depends` → `depends()`…

**Ça ne modifie rien tout seul** : Ça produit le code converti, tu valides puis tu appliques.

### 4.12 Tests d'architecture

Le dossier `tests/Architecture` **n'existe pas** dans ce projet (le script composer existe mais
échouera tant que le dossier est absent). Si tu le créés :

```php
<?php
// tests/Architecture/ArchTest.php

arch('controllers')->expect('App\Http\Controllers')->toExtend('App\Http\Controllers\Controller');
arch('models')->expect('App\Models')->toUse('Illuminate\Database\Eloquent\Model');
arch('globals')->expect(['dd', 'dump', 'ray', 'var_dump'])->not->toBeUsed();
arch('middleware')->expect('App\Http')->toOnlyUse('Illuminate', 'Symfony', 'Laravel', 'App');
```

Ajouter la suite dans `phpunit.xml`, puis :

```bash
php vendor/bin/pest tests/Architecture --compact
```

### 4.13 Rapports et diagnostic

```bash
php vendor/bin/pest --profile                 # top 10 des tests les plus lents
php vendor/bin/pest --debug                   # sortie de debogage détaillée
php vendor/bin/pest --display-all-issues
php vendor/bin/pest --display-skipped --display-incomplete --display-deprecations
php vendor/bin/pest --diff-context=10
php vendor/bin/pest --columns=max
php vendor/bin/pest --log-junit=build/logs/junit.xml
php vendor/bin/pest --log-otr=build/logs/otr.xml
php vendor/bin/pest --testdox-html=build/logs/testdox.html
php vendor/bin/pest --testdox-text=build/logs/testdox.txt
php vendor/bin/pest --log-events-text=build/logs/events.txt
php vendor/bin/pest --include-git-information
php artisan test --without-tty                 # si la sortie est bizarre dans un terminal
```

> `pao` force `--no-output` : `--testdox`, `--debug` et les `--display-*` sont donc neutralises
> (§2.1). Le tableau de résumé de `bin/check.php` reste lisible.

---

## 5. Configuration des tests

### 5.1 `phpunit.xml` — toutes les directives

```xml
<phpunit bootstrap="vendor/autoload.php" colors="true">
```

| Directive | Role | Pourquoi cette valeur |
|---|---|---|
| `bootstrap="vendor/autoload.php"` | Autoloader chargé **avant** les tests | Standard PHPUnit |
| `colors="true"` | Sortie colorée | lisibilité |

#### Les deux suites déclarés

```xml
<testsuites>
    <testsuite name="Unit">    <directory>tests/Unit</directory>    </testsuite>
    <testsuite name="Feature"> <directory>tests/Feature</directory> </testsuite>
</testsuites>
```

Seuls `Unit` et `Feature` existent. Les autres scripts composer visent des dossiers absents (§5.7).
Ajouter un dossier ici **et** créer le dossier, sinon `--testsuite=X` échoué.

#### `<source>` — périmètre de la couverture

```xml
<source>
    <include>
        <directory>app</directory>
    </include>
</source>
```

Seul `app/` est mesuré. `database/`, `routes/`, `config/` sont **exclus**.

### 5.2 Les variables d'environnement injectées

Toutes ces valeurs **écrasent** ton `.env` pendant les tests (priorité aux `<env>` de `phpunit.xml`).

| Variable | Valeur | Ce que ca fait concrètement |
|---|---|---|
| `APP_ENV` | `testing` | Active `APP_ENV=testing` ; Laravel 13 chargé `bootstrap/cache/testing-*` |
| `APP_MAINTENANCE_DRIVER` | `file` | Le mode maintenance n'écrit pas en base pendant les tests |
| `BCRYPT_ROUNDS` | `4` | Hash bcrypt **beaucoup** plus rapide (4 au lieu de 12). Multiplie la vitesse de la suite. Jamais moins en prod. |
| `BROADCAST_CONNECTION` | `null` | Aucun serveur de broadcast → les tests d'événements ne plantent pas |
| `CACHE_STORE` | `array` | Cache **en mémoire**, vide après chaque test. Aucun Redis. |
| `DB_CONNECTION` | `sqlite` | Force SQLite |
| `DB_DATABASE` | `:memory:` | Base **volatile** : recréée a chaque exécution, détruite a la fin. Isole totalement la base de dev. |
| `DB_URL` | *(vide)* | Neutralise `DB_URL` du `.env` (une URL complete prendrait le dessus sur `DB_*`) |
| `MAIL_MAILER` | `array` | Les mails sont **captures en mémoire**. Testez-les avec `Mail::fake()` / `assertSent()` |
| `QUEUE_CONNECTION` | `sync` | Les jobs s'exécutent **immédiatement et en ligne**. Pas de Redis, pas d'Horizon. ⚠️ un job qui appelle une API externe sera réellement appelé → mockez-le. |
| `SESSION_DRIVER` | `array` | Sessions en mémoire, aucune table `sessions`, aucune pollution entre tests |
| `PULSE_ENABLED` | `false` | désactive Laravel Pulse (sinon il écrit en base) |
| `TELESCOPE_ENABLED` | `false` | désactive Telescope (absent du projet) |
| `NIGHTWATCH_ENABLED` | `false` | désactive Laravel Nightwatch (télémétrie) |

### 5.3 Variables NON présents mais utiles a ajouter

Le fichier est minimal. Voici ce que tu peux ajouter si tes tests en ont besoin :

```xml
<!-- Utiliser PostgreSQL au lieu de SQLite pour tester le vrai moteur -->
<env name="DB_CONNECTION" value="pgsql"/>
<env name="DB_DATABASE" value="manecms_testing"/>

<!-- désactiver complètement Scout / Meilisearch -->
<env name="SCOUT_DRIVER" value="null"/>

<!-- Redis réel au lieu du cache array -->
<env name="CACHE_STORE" value="redis"/>
<env name="QUEUE_CONNECTION" value="redis"/>
<env name="REDIS_DB" value="15"/>

<!-- Fichiers sur disque local au lieu de S3 -->
<env name="FILESYSTEM_DISK" value="local"/>

<!-- Horizon / Reverb -->
<env name="HORIZON_PREFIX" value="testing"/>
<env name="REVERB_HOST" value="localhost"/>

<!-- clé applicative stable : evite une clé régénérée par test -->
<env name="APP_KEY" value="base64:..."/>
```

⚠️ **`APP_KEY`** : sans valeur fixe, la clé peut changer d'une exécution a l'autre.
Si tu testés des cookies chiffrés ou des tokens, fige-la explicitement.

⚠️ **`SCOUT_DRIVER=meilisearch`** vient du `.env` : un modèle `Searchable` testé sans Meilisearch
qui tourne plante. Soit `SCOUT_DRIVER=null` + `Searchable::fake()` / `Scout::fake()`,
soit démarrez Meilisearch (Herd → Services, ou le binaire `meilisearch` directement).

### 5.4 Fichiers de configuration secondaires

| Fichier | Ce qu'il contrôle |
|---|---|
| `phpunit.xml` | suites, env de test, périmètre de coverage — **LE fichier de config test** |
| `tests/Pest.php` | classe de base, traits globaux, helpers, expectations custom |
| `tests/TestCase.php` | classe de base abstraite + helper Fortify |
| `bin/check.php` | orchestration du quality gate (§3) |
| `pint.json` | preset `laravel` (formatage) |
| `phpstan.neon` | `paths` + `level: 7` + extensions Larastan/Carbon |
| `config/insights.php` | preset PHP Insights (§5.6) |
| `.github/workflows/tests.yml` | CI (§10) |

### 5.5 `tests/Pest.php` — la configuration Pest

```php
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
```

C'est **le bloc le plus important** du fichier :

- `extend(TestCase::class)` : tous les tests dans `Feature` heritent de `Tests\TestCase`
  (donc de `Illuminate\Foundation\Testing\TestCase`, donc de l'application Laravel complete :
  routes, container, facades, base de données). C'est ce qui rend `get()`, `actingAs()` disponibles.
- `use(RefreshDatabase::class)` : **uniquement `Feature`**. Chaque test encadre son exécution dans
  une transaction et rollback → rapide, isolation totale.

  ⚠️ **Corollaire** : les tests dans `tests/Unit` n'ont **PAS** la base de données et **PAS**
  l'application Laravel (classe `PHPUnit\Framework\TestCase`). Un test en `Unit` qui fait
  `User::factory()->create()` **échoué**. Deux solutions :
  - déplacer le test dans `tests/Feature`, ou
  - étendre la config :
    ```php
    pest()->extend(TestCase::class)
        ->use(RefreshDatabase::class)
        ->in('Feature', 'Unit');
    ```

- `expect()->extend('toBeOne', …)` : exemple d'expectation personnalisée. Ajoute ici les tiennes
  plutot que de les dupliquer.
- `function something()` : helpers globaux (fixtures, builders).

### 5.6 `tests/TestCase.php` et `config/insights.php`

```php
abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
```

Helper projet : un test lie a une feature Fortify (2FA, passkeys, mot de passe) **ne plante pas** si
la feature est désactivée dans `config/fortify.php` — il est *skipped* :

```php
test('user can confirm two factor auth', function () {
    skipUnlessFortifyHas('two-factor-authentication');
    // ...
});
```

Ajoute ici tout `setUp()` global (mocks, bindings, `Http::fake()`, Timebox…).

`config/insights.php` est le fichier de preset PHP Insights. Il a été publié et **les
connexes pour Windows** :

```php
'remove' => [
    // …
    SyntaxCheck::class,   // incompatible Windows : JsonException sur parallel-lint.bat
],
```

Sans ce `remove`, `php artisan insights` échoué avec `JsonException: Syntax error` sur Windows
(le script `SyntaxCheck` lance `vendor\bin\parallel-lint.bat` et attend du JSON qui ne vient pas).
Pour le regénérer :

```bash
php artisan vendor:publish \
  --provider="NunoMaduro\PhpInsights\Application\Adapters\Laravel\InsightsServiceProvider" \
  --tag=config
```

### 5.7 Scripts Composer — l'état réel

Tous les scripts utilisent `@php vendor/bin/...`, qui appelle **le `php` du `PATH`**.
Sans le correctif §1.2, **ils échouent tous**.

| Script | Ce qu'il lance | état |
|---|---|---|
| `composer check` | `bin/check.php` | ✅ (voir §3.4 pour le détail des échecs) |
| `composer check:fix` / `check:dirty` / `check:full` | idem + options | ✅ |
| `composer test` | `quality` + `test:unit` + `test:feature` | ⚠️ échoué (phpstan `--level=max`, insights avant le fix) |
| `composer test:unit` | `pest tests/Unit --parallel` | ✅ |
| `composer test:feature` | `pest tests/Feature --parallel` | ✅ |
| `composer test:architecture` | `pest tests/Architecture` | ❌ dossier absent |
| `composer test:integration` | `pest tests/Integration --parallel` | ❌ dossier absent |
| `composer test:security` | `pest tests/Security --parallel` | ❌ dossier absent |
| `composer test:browser` | `pest tests/Browser --parallel` | ❌ dossier absent |
| `composer test:contract` | `pest tests/Contract` | ❌ dossier absent |
| `composer test:performance` | `pest tests/Performance` | ❌ dossier absent |
| `composer test:concurrency` | `pest tests/Concurrency` | ❌ dossier absent |
| `composer test:types` | `pest --type-coverage --min=100` | ❌ `memory_limit` 128M |
| `composer ci:check` | `composer test` | ❌ (même raison) |
| `composer quality` | format + rector + phpstan(max) + insights | ⚠️ |
| `composer quality:format` | `pint --test` | ✅ |
| `composer quality:rector` | `rector process --dry-run` | ✅ |
| `composer quality:phpstan` | `phpstan analyse --level=max` | ⚠️ très strict |
| `composer quality:insights` | `artisan insights` | ✅ |
| `composer lint` | `pint --parallel` (corrige) | ✅ |
| `composer lint:check` | `pint --parallel --test` (vérifie) | ✅ |
| `composer types:check` | `phpstan analyse` (level 7) | ✅ |

> **Correction durable** : faire pointer le `PATH` sur le PHP 8.4 de Herd (§1.2).

---

## 6. créer des tests

### 6.1 générer un fichier

```bash
php artisan make:test --pest PostTest                    # → tests/Feature/PostTest.php
php artisan make:test --pest --unit PriceCalculatorTest  # → tests/Unit/PriceCalculatorTest.php
php artisan make:test --pest --preset=phpunit PostTest   # preset PHPUnit au lieu de Pest
```

> `make:test` n'a **pas** d'option `--arch`. Pour les tests d'architecture, créés
> `tests/Architecture/ArchTest.php` a la main (§4.12).

### 6.2 Tests existants (pour s'y aligner)

```
tests/Unit/ExampleTest.php
tests/Feature/DashboardTest.php
tests/Feature/ExampleTest.php
tests/Feature/Auth/AuthenticationTest.php
tests/Feature/Auth/EmailVerificationTest.php
tests/Feature/Auth/PasswordConfirmationTest.php
tests/Feature/Auth/PasswordResetTest.php
tests/Feature/Auth/RegistrationTest.php
tests/Feature/Auth/TwoFactorChallengeTest.php
tests/Feature/Settings/ProfileUpdateTest.php
tests/Feature/Settings/SecurityTest.php
```

Les tests sont ranges par **domaine fonctionnel** (`Auth/`, `Settings/`) — suis cette convention
plutot que de tout mettre a plat.

### 6.3 Test HTTP (Feature)

```php
<?php

test('user can log in', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
});

test('user cannot log in with invalid password', function () {
    $user = User::factory()->create();

    $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});
```

Helpers dispo : `get()`, `post()`, `put()`, `patch()`, `delete()`, `getJson()`, `withHeaders()`,
`actingAs($user)`, `actingAs($user, 'admin')`, `assertAuthenticated()`, `assertRedirect()`,
`assertOk()`, `assertNotFound()`, `assertForbidden()`, `assertSee()`, `assertJson()`…

préfère l'`expect()` chaîné (convention du projet) :

```php
test('slug is generated from the title', function () {
    $post = Post::factory()->create(['title' => 'Hello World']);

    expect($post->slug)->toBe('hello-world')
        ->and($post->user_id)->toBeInt();
});
```

### 6.4 Test Livewire

```php
<?php

use Livewire\Livewire;

test('dashboard shows the user name', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee($user->name)
        ->assertSet('posts', fn ($posts) => $posts->count() === 5);
});
```

Autres assertions : `->call('method', $args)`, `->set('prop', $value)`, `->fillForm([...])`,
`->submitForm([...])`, `->assertHasErrors([...])`, `->assertRedirect(...)`, `->assertDispatched(...)`.

### 6.5 Test avec base de données

```php
test('post belongs to its author', function () {
    $user = User::factory()->create();
    $post = Post::factory()->for($user)->create();

    expect($post->user)->toBeInstanceOf(User::class)
        ->and($post->user_id)->toBe($user->id)
        ->and(Post::whereBelongingTo($user)->count())->toBe(1);
});
```

Factory disponible : `database/factories/UserFactory.php` (créer les autres avec
`php artisan make:factory PostFactory -m`).

### 6.6 Filesystem, Mail, Queue, Cache, Events, HTTP

```php
// Fichiers — S3 désactive, on utilise le disque 'local' avec Storage::fake()
Storage::fake('local');
$post->cover()->store('covers', 'local');
Storage::disk('local')->assertExists('covers/x.jpg');

// Mail — MAIL_MAILER=array
Mail::fake();
Notification::send($user, new WelcomeMail);
Mail::assertSent(WelcomeMail::class, fn ($m) => $m->hasTo($user->email));

// Queue — QUEUE_CONNECTION=sync : le job part tout de suite
Queue::fake();
$user->createPost(['title' => 'x']);
Queue::assertPushed(CreatePost::class);
Queue::assertNothingPushed();

// Cache — CACHE_STORE=array
Cache::put('k', 'v', 60);
expect(Cache::get('k'))->toBe('v');

// événements
Event::fake();
$post->publish();
Event::assertDispatched(PostPublished::class);

// HTTP sortant — a mocker TOUJOURS, sinon un vrai appel réseau
Http::fake([
    'api.stripe.com/*' => Http::response(['id' => 'ch_123'], 200),
]);
Http::assertSent(fn ($r) => $r->url() === 'https://api.stripe.com/v1/chargés');
```

### 6.7 Temps, rate limiting, données aléatoires

```php
$this->freezeTime();
$this->travel(5)->days();
$this->freezeSecond();

User::factory()->create(['name' => $this->faker->name()]);
$this->faker->sentence();
$this->faker->unique()->email();
fake()->randomDigit();
```

### 6.8 Attributs Pest utiles

```php
test('x', function () { /* … */ })
    ->group('billing')                 // groupe → --group=billing
    ->depends('a_previous_test')       // dépend d'un autre test
    ->skip('raison')                   // toujours skippé
    ->skipOnWindows()
    ->skipOnCI()
    ->throws(RuntimeException::class)  // attend une exception
    ->todo('a faire')
    ->covers(PostService::class)       // métadonnée de couverture
    ->timeout(10);                     // timeout en secondes

// datasets
test('x', function (string $value) { /* … */ })
    ->with(['a', 'b', 'c']);

test('x', function (array $payload) { /* … */ })
    ->with([
        ['payload' => ['a' => 1]],
        'cas null' => ['payload' => null],
    ]);
```

---

## 7. Couverture de code — **indisponible en l'état**

`xdebug` **et** `pcov` sont absents du PHP 8.4 de Herd. Toute commande de coverage échoué :

```bash
php artisan test --coverage                        # ERREUR : No code coverage driver available
php vendor/bin/pest --coverage-html=build/coverage
php vendor/bin/pest --coverage-clover=build/logs/clover.xml
php vendor/bin/pest --coverage --min=80
```

**Pour l'activer** : Herd → Settings → PHP 8.4 → **Extensions** → activer **Xdebug**
(ou **PCOV**, plus rapide et suffisant pour du coverage), puis `herd restart`
(ou `herd stop && herd start`).

vérifier ensuite :

```powershell
php -m | Select-String "xdebug|pcov"
```

Formats disponibles une fois le driver installe : `--coverage-clover`, `--coverage-cobertura`,
`--coverage-crap4j`, `--coverage-html`, `--coverage-openclover`, `--coverage-php`,
`--coverage-text`, `--coverage-xml`, `--branch-coverage`, `--path-coverage`,
`--coverage-filter`, `--only-covered`.

---

## 8. Type coverage

```bash
php vendor/bin/pest --type-coverage
php vendor/bin/pest --type-coverage --min=100     # exigence du composer.json
php vendor/bin/pest --type-coverage --show-missing
```

**échec brut** sur cette machine :

```
PHP Fatal error: Allowed memory size of 134217728 bytes exhausted
  at phar://.../vendor/phpstan/phpstan/phpstan.phar/resources/functionMap.php:59
```

`memory_limit` est a **128M** alors que l'analyseur en a besoin de plus. Le type coverage embarqué
PHPStan **dans le processus**, donc `PHPSTAN_MEMORY_LIMIT` ne suffit pas.

Contournements :

```bash
# Ponctuel (ce que fait bin/check.php)
php -d memory_limit=2G vendor/bin/pest --type-coverage
```

```ini
; Permanent : Herd → Settings → PHP 8.4 → php.ini
memory_limit = 2G
```

état actuel : **96,9 %** (passe).

---

## 9. Le workflow quotidien

```bash
# 1. Pendant le dev : uniquement le fichier touche, --bail pour le feedback immédiat
php artisan test tests/Feature/Settings/ProfileUpdateTest.php --bail

# 2. Avant commit : format + tests modifiés + analyse statique
php vendor/bin/pint --dirty            # corrige le formatage des fichiers touches
php vendor/bin/pest --dirty            # tests des fichiers touches
php vendor/bin/phpstan analyse

# OU en une fois, via le quality gate
herd php bin/check.php --dirty

# 3. Avant push : tout
herd php bin/check.php

# 4. Ouverture de PR : quality gate complet + étapes lentes
herd php bin/check.php --full
```

règle de la maison : **un test qui échoué ne se commente jamais** — `->skip()` doit avoir une
raison écrite. Pour les lister : `php vendor/bin/pest --display-skipped`.

---

## 10. CI (`.github/workflows/tests.yml`)

déclenche sur : push sur `main`, et toute pull request.

```yaml
php-version: '8.3'      # ❌ incohérent : PHPUnit 13 / Symfony 8 exigent >= 8.4.1
node-version: '22'
run: composer setup     # composer install + .env + key:generate + migrate + pnpm install + build
run: composer ci:check  # = composer test = quality + test:unit + test:feature
```

### 10.1 Ce qui est casse

1. **`php-version: '8.3'` → `composer setup` échoué** au `composer install`
   (`Your Composer dependencies require a PHP version ">= 8.4.1"`).
   **Correction : `php-version: '8.4'`**.

2. **`composer setup` lance `php artisan migrate --force`** → tente de migrer **Postgres**
   (`.env` copie depuis `.env.example` → `DB_CONNECTION=pgsql`) sans serveur Postgres sur le runner
   `ubuntu-latest`. La CI plante la aussi.
   **Correction** : ne pas migrer pendant `setup` (`RefreshDatabase` s'en charge sur SQLite
   en mémoire), ou ajouter un service Postgres.

3. `coverage: none` → aucun driver de coverage, cohérent avec l'absence de Xdebug (§7).

4. `phpunit.xml` ne déclare que `Unit` et `Feature` → `composer test` est cohérent de ce point de vue.

5. `composer ci:check` lance `composer quality` qui inclut `quality:phpstan` (`--level=max`) et
   `quality:rector` (sans `rector.php`). Le niveau `max` est beaucoup plus strict que le `level: 7`
   de `phpstan.neon` → probablement des hundreds d'erreurs.

### 10.2 Workflow CI corrige minimal

```yaml
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pdo_sqlite, sqlite3
          coverage: none

      - name: Setup Node
        uses: actions/setup-node@v5
        with:
          node-version: '22'

      # .env sans migration Postgres : RefreshDatabase gère SQLite en mémoire
      - name: prépare test environment
        run: cp .env.example .env && php artisan key:generate

      - name: Run quality gate
        run: php bin/check.php --full
```

---

## 11. Recapitulatif des variables de configuration

### 11.1 Dans `phpunit.xml` (prioritaire sur `.env`)

| Var | Actuelle | Recommandation |
|---|---|---|
| `APP_KEY` | *absente* | **ajouter** une clé fixe pour des tests de chiffrement reproductibles |
| `DB_CONNECTION` | `sqlite` | garder `sqlite` (rapide, isole) |
| `DB_DATABASE` | `:memory:` | garder (aucune persistance) |
| `SCOUT_DRIVER` | *absente* | **ajouter** `null` si tu testés des modèles `Searchable` sans Meilisearch |
| `FILESYSTEM_DISK` | *absente* | **ajouter** `local` si tu testés les uploads (le `.env` dit `s3`) |
| `REDIS_DB` | *absente* | **ajouter** `15` si tu forces un Redis réel |
| `SESSION_DRIVER` | `array` | garder |
| `QUEUE_CONNECTION` | `sync` | passer a `redis` uniquement pour tester Horizon |
| `CACHE_STORE` | `array` | garder |
| `MAIL_MAILER` | `array` | garder |
| `BCRYPT_ROUNDS` | `4` | garder |
| `BROADCAST_CONNECTION` | `null` | garder |
| `PULSE_ENABLED` | `false` | garder |
| `TELESCOPE_ENABLED` | `false` | garder |
| `NIGHTWATCH_ENABLED` | `false` | garder |

### 11.2 Dans `.env.example` (dev) — celles qui influencent les tests

| Var | Valeur | Impact sur les tests |
|---|---|---|
| `DB_CONNECTION` | `pgsql` | **écrasée** (`sqlite`). Le schéma est valide sur Postgres en dev et SQLite en test. |
| `DB_DATABASE` | `manecms` | **écrasée** (`:memory:`) |
| `FILESYSTEM_DISK` | `s3` | **non écrasée** → risque en test. Ajouter `FILESYSTEM_DISK=local`. |
| `SCOUT_DRIVER` | `meilisearch` | **non écrasée** → risque en test. Ajouter `SCOUT_DRIVER=null`. |
| `QUEUE_CONNECTION` | `redis` | **écrasée** (`sync`) |
| `CACHE_STORE` | `redis` | **écrasée** (`array`) |
| `BROADCAST_CONNECTION` | `reverb` | **écrasée** (`null`) |
| `MAIL_MAILER` | `log` | **écrasée** (`array`) |
| `SESSION_DRIVER` | `database` | **écrasée** (`array`) |
| `APP_ENV` | `local` | **écrasée** (`testing`) |
| `BCRYPT_ROUNDS` | `12` | **écrasée** (`4`) |

**règle** : toute variable d'infrastructure dans `.env.example` qui n'est **pas** listee dans
`<php><env>` de `phpunit.xml` peut fuir en test. C'est le checklist a relire a chaque nouveau
service ajoute.

### 11.3 Variables système

| Variable | Actuelle | Action |
|---|---|---|
| `PATH` | `php` = Laragon 8.3.12 | **mettre `~\.config\herd\bin\php84` en tête** (§1.2) |
| `memory_limit` | 128M | passer a `2G` pour le type coverage (§8) |
| Extensions | ni xdebug ni pcov | activer Xdebug/PCOV pour la couverture (§7) |

---

## 12. Recapitulatif des problèmes connus

| symptôme | Cause | Solution |
|---|---|---|
| `Composer detected issues in your platform: >= 8.4.1` | `php` du PATH = 8.3 (Laragon) | `$env:Path = "$env:USERPROFILE\.config\herd\bin\php84;" + $env:Path` (§1.2) |
| `herd php …` renvoie toujours 0 | le proxy Herd sous Windows mange le code de sortie | utiliser le PATH corrigé (§1.2) |
| `No code coverage driver available` | pas de `xdebug`/`pcov` | Herd → Settings → PHP 8.4 → Extensions → activer, puis `herd restart` (§7) |
| `Allowed memory size of 134217728 bytes exhausted` sur `--type-coverage` | `memory_limit = 128M` | `php -d memory_limit=2G …` ou `memory_limit = 2G` dans le php.ini (§8) |
| `Options --no-output and --testdox cannot be used together` | `laravel/pao` force `--no-output` | `composer remove --dev laravel/pao` (§2.1) |
| `JsonException: Syntax error` sur `artisan insights` | `SyntaxCheck` lance `parallel-lint.bat` (Windows) | `SyntaxCheck::class` dans `remove` de `config/insights.php` (§5.6) |
| `No "rector.php" config found` mais exit 0 | pas de `rector.php` → Rector ne fait rien | créer `rector.php`, ou ignorer le stage (`--skip=rector`) |
| `rector.php` flagged par Pint (`concat_space`, `line_ending`) | fichier généré avec `__DIR__ . '/x'` et en CRLF | `herd php vendor/bin/pint rector.php` |
| `No tests found in tests/Architecture` | dossier absent | créer le dossier (§4.12) ou ignorer le script |
| Migration qui passe en dev mais casse en test | SQLite vs Postgres | rendre la migration agnostique |
| Test en `tests/Unit` qui n'a pas la DB | `RefreshDatabase` limité a `Feature` | déplacer en `Feature` ou étendre `tests/Pest.php` (§5.5) |
| Job qui appelle une API pour de vrai | `QUEUE_CONNECTION=sync` | `Queue::fake()` ou `Http::fake()` |
| modèle `Searchable` qui plante | `SCOUT_DRIVER=meilisearch` non écrase | `SCOUT_DRIVER=null` + `Searchable::fake()` |
| Upload qui tente S3 | `FILESYSTEM_DISK=s3` non écrase | `Storage::fake('s3')` ou `FILESYSTEM_DISK=local` |
| `--parallel` plus lent que séquentiel | coût de démarrage de ParaTest | ne l'utiliser qu'au-dela de ~500 tests |
| CI rouge sur `composer setup` | `php-version: '8.3'` dans le workflow | passer a `'8.4'` (§10.1) |

---

## 13. Aide-mémoire

```powershell
# Tout lancer (quality gate complet)
herd php bin/check.php
herd php bin/check.php --full
herd php bin/check.php --dirty
herd php bin/check.php --fix

# Tests
php artisan test --compact
php artisan test tests/Feature/Auth/AuthenticationTest.php
php artisan test --filter="test_user_can_log_in"
php vendor/bin/pest --dirty
php artisan test --bail
php artisan test --parallel

# qualité individuelle
php vendor/bin/pint --dirty
php vendor/bin/phpstan analyse
php artisan insights
php vendor/bin/rector process --dry-run

# Couverture et types (prérequis dans §7 / §8)
php artisan test --coverage --min=80
php -d memory_limit=2G vendor/bin/pest --type-coverage --min=100

# créer un test
php artisan make:test --pest NomDuTest
php artisan make:test --pest --unit NomDuTest
```
