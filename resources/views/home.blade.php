@php
    $modules = [
        ['sparkles', 'Expérience', ['CMS', 'Médias', 'SEO', 'CRM', 'Marketing', 'Service', 'Communauté', 'Knowledge', 'LMS', 'Collaboration']],
        ['cog-6-tooth', 'Opérations', ['Finance', 'Achats', 'Stocks', 'WMS', 'Fabrication', 'Qualité', 'Actifs', 'Maintenance', 'Flotte', 'Projets', 'RH', 'Paie']],
        ['shopping-cart', 'Transactions', ['Commerce', 'PIM', 'Prix', 'Abonnements', 'POS', 'Paiements', 'Réservations', 'Billetterie', 'Adhésions', 'Contrats', 'Signatures']],
        ['bolt', 'Transversal', ['Workflow', 'Approbations', 'Automatisation', 'Recherche', 'Analytics', 'API', 'Intégrations', 'IA', 'Audit', 'Extensions']],
        ['building-office', 'Plateforme', ['Identité', 'Tenants', 'Organisation', 'Party', 'MDM', 'Politiques', 'Canaux', 'Localisation', 'Juridictions', 'Conformité']],
    ];

    $primitives = [
        ['Identité & Tenant', 'Une identité, une frontière d\'isolation claire.'],
        ['Party', 'Clients, fournisseurs, salariés, membres : une seule fiche, plusieurs rôles.'],
        ['Politiques', 'Autoriser, refuser ou exiger une approbation, partout.'],
        ['Argent, temps, quantités', 'Jamais de float, des fuseaux explicites, des unités maîtrisées.'],
        ['Événements', 'Chaque changement alimente recherche, analytics, 360° et IA.'],
        ['Documents', 'Versions, modèles, signatures et archivage unifiés.'],
    ];

    $security = [
        ['lock-closed', 'Tenant isolé'],
        ['receipt-percent', 'Ledger immuable'],
        ['arrow-path', 'Idempotence'],
        ['document-check', 'Audit complet'],
    ];

    $templates = ['Particulier', 'Freelance', 'Services pro', 'Agence', 'Commerce', 'E-commerce', 'Restaurant', 'Hôtel', 'Hôtellerie', 'Événements', 'Industrie', 'Distribution', 'Logistique', 'BTP', 'Immobilier', 'Automobile', 'Éducation', 'Non-profit', 'Association', 'Marketplace', 'SaaS par abonnement', 'Santé (socle)', 'Secteur public (socle)'];

    $stack = ['Laravel', 'Livewire', 'Blade', 'Tailwind CSS', 'TallStackUI', 'ManeUI', 'Alpine.js', 'PostgreSQL', 'Redis', 'Meilisearch', 'Reverb', 'S3', 'OpenAPI', 'Pest', 'PHPStan'];

    $reliability = [
        ['Isolation des tenants', 'garantie jusque dans PostgreSQL (Row-Level Security) : sans contexte, accès refusé.'],
        ['Comptabilité immuable', 'écritures équilibrées, périodes verrouillées, audit append-only.'],
        ['Zéro double paiement, zéro double réservation', 'grâce aux clés d\'idempotence et aux contraintes d\'exclusion.'],
        ['Pannes maîtrisées', 'outbox, inbox, reprises, compensation, runbooks et restauration testée.'],
        ['Politiques et séparation des tâches', 'créer, approuver, exécuter et clôturer par des acteurs différents.'],
    ];

    $assistant = [
        ['Mêmes permissions', 'que l\'utilisateur, jamais plus.'],
        ['Actions sensibles approuvées', 'par un humain : paiement, remboursement, paie, comptabilité.'],
        ['Protégée', 'contre l\'injection de prompt, la fuite inter-tenants et les dérives de coût.'],
        ['Auditée et évaluée', 'modèle, outils, décisions, coût et durée de chaque exécution.'],
    ];

    $faq = [
        ['ManeCMS est-il adapté à une petite structure ?', 'Oui. Les modèles métiers n\'activent que les modules utiles : un freelance et un groupe multi-entités partagent le même socle.'],
        ['Mes données sont-elles isolées de celles des autres ?', 'L\'isolation est appliquée à plusieurs niveaux, dont PostgreSQL (RLS), et testée sur l\'API, la recherche, les exports et l\'IA.'],
        ['L\'IA peut-elle agir seule sur ma comptabilité ou mes paiements ?', 'Non. Les actions sensibles passent par le même moteur d\'approbation qu\'un humain.'],
        ['Puis-je étendre ManeCMS ?', 'Oui : un SDK permet d\'ajouter champs, blocs, fournisseurs de paiement, outils IA, rapports et widgets, sans contourner l\'isolation, les politiques ni l\'audit.'],
        ['La conformité réglementaire est-elle garantie ?', 'L\'architecture est prête pour plusieurs pays ; la conformité exacte est validée par pack de juridiction et par une gouvernance adaptée.'],
    ];

    $pipeline = ['Lead', 'Devis', 'Commande', 'Paiement', 'Stock', 'Facture', 'Compta'];

    $workflow = ['Lead', 'Opportunité', 'Devis', 'Approbation', 'Commande', 'Livraison', 'Facture', 'Paiement'];

    $kpis = [
        ['Chiffre d\'affaires', '128 400 €', '▲ 12 %'],
        ['Pipeline CRM', '46 opportunités', '▲ 5'],
        ['Stock critique', '3 produits', 'à réapprovisionner'],
        ['Réservations', '212 ce mois', '0 conflit'],
    ];

    $stats = [
        ['4', 'langues dès la V1 : fr, en, es, ar (RTL)'],
        ['WCAG 2.2 AA', 'accessibilité ciblée'],
        ['100 %', 'couverture de types visée'],
        ['10', 'canaux : web, POS, portails, API, IA…'],
    ];
@endphp

<x-layouts::guest :title="__('Home')">
    <div class="flex flex-col">
        <section class="relative pb-10 text-center">
            <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[600px] bg-primary-200/50 blur-3xl dark:bg-primary-500/10" aria-hidden="true"></div>

            <x-badge light round sm :text="__('Business · Contenu · Commerce · Opérations · IA')" />

            <h1 class="font-sora mx-auto mt-6 max-w-[900px] text-[clamp(2.3rem,6vw,4.6rem)] font-extrabold leading-[1.03] tracking-[-0.03em] text-dark-900 dark:text-white">
                {{ __('Une plateforme.') }}
                <span class="text-primary-600 dark:text-primary-400">{{ __('Toute') }}</span>
                {{ __('votre organisation.') }}
            </h1>

            <p class="mx-auto mt-5 max-w-[660px] text-[1.15rem] leading-relaxed text-dark-500 dark:text-dark-400">
                {{ __('ManeCMS réunit CMS, CRM, ERP, e-commerce, paiements, réservations, RH, paie et IA sur un même socle : mêmes identités, mêmes données, mêmes règles. Fini les outils qui ne se parlent pas.') }}
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <x-button href="#cta" :text="__('Démarrer avec ManeCMS')" />

                <x-button flat href="#modules" :text="__('Explorer les modules')" />
            </div>

            <div class="mt-10 flex flex-wrap items-center justify-center gap-2">
                @foreach ([__('Any person'), __('Any organization'), __('Any language'), __('Any channel'), __('Any business')] as $claim)
                    <span class="font-sora rounded-full border border-dark-200 bg-white px-3 py-1.5 text-[0.78rem] font-semibold uppercase tracking-[0.08em] text-dark-600 dark:border-dark-700 dark:bg-dark-800 dark:text-dark-300">
                        {{ $claim }}
                    </span>
                @endforeach
            </div>

            <x-card paddingless bordered round="2xl" class="mx-auto mt-11 max-w-[920px] text-start shadow-2xl">
                <div class="flex items-center gap-2 border-b border-dark-200 px-4 py-3 dark:border-dark-700">
                    <span class="size-2.5 rounded-full bg-primary-500" aria-hidden="true"></span>
                    <span class="size-2.5 rounded-full bg-dark-200 dark:bg-dark-600" aria-hidden="true"></span>
                    <span class="size-2.5 rounded-full bg-dark-200 dark:bg-dark-600" aria-hidden="true"></span>
                </div>

                <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($kpis as [$label, $value, $delta])
                        <div class="rounded-xl border border-dark-200 p-3.5 dark:border-dark-700">
                            <p class="text-[0.75rem] text-dark-500 dark:text-dark-400">{{ $label }}</p>
                            <p class="font-sora mt-0.5 text-[1.35rem] font-bold tracking-[-0.03em] text-dark-900 dark:text-white">{{ $value }}</p>
                            <p class="text-[0.75rem] font-semibold text-primary-600 dark:text-primary-400">{{ $delta }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-wrap gap-1.5 px-4 pb-4">
                    @foreach ($pipeline as $step)
                        <span class="min-w-[90px] flex-1 rounded-lg bg-primary-50 px-1.5 py-2 text-center text-[0.78rem] font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">
                            {{ $step }}
                        </span>
                    @endforeach
                </div>
            </x-card>
        </section>

        <section id="modules" class="py-20 sm:py-[84px]">
            <div class="mx-auto max-w-[1160px]">
                <p class="font-sora text-[0.8rem] font-bold uppercase tracking-[0.1em] text-primary-600 dark:text-primary-400">
                    {{ __('Tout-en-un') }}
                </p>

                <h2 class="font-sora mt-3 max-w-[760px] text-[clamp(1.8rem,4vw,2.9rem)] font-extrabold leading-[1.08] tracking-[-0.03em] text-dark-900 dark:text-white">
                    {{ __('60+ domaines métier, un seul produit') }}
                </h2>

                <p class="mt-4 max-w-[640px] text-[1.05rem] text-dark-500 dark:text-dark-400">
                    {{ __('Pas de plugins greffés après coup : chaque domaine possède ses règles, ses données et ses événements, et tous partagent le même socle.') }}
                </p>

                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($modules as [$icon, $title, $items])
                        <x-card round="2xl">
                            <div class="flex size-10 items-center justify-center rounded-[11px] bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                                <x-icon :name="$icon" />
                            </div>

                            <h3 class="font-sora mt-3.5 text-[1.1rem] font-semibold tracking-[-0.03em] text-dark-900 dark:text-white">{{ $title }}</h3>

                            <div class="mt-3.5 flex flex-wrap gap-[7px]">
                                @foreach ($items as $item)
                                    <span class="rounded-full bg-primary-50 px-2.5 py-1 text-[0.8rem] font-medium text-dark-800 dark:bg-primary-500/10 dark:text-dark-100">{{ $item }}</span>
                                @endforeach
                            </div>
                        </x-card>
                    @endforeach

                    <x-card round="2xl">
                        <div class="flex size-10 items-center justify-center rounded-[11px] bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                            <x-icon name="plus" />
                        </div>

                        <h3 class="font-sora mt-3.5 text-[1.1rem] font-semibold tracking-[-0.03em] text-dark-900 dark:text-white">{{ __('Et les suivants') }}</h3>

                        <p class="mt-2 text-[0.93rem] text-dark-500 dark:text-dark-400">
                            {{ __('Le socle reste le même : ajouter un domaine n\'introduit jamais un second jeu d\'identités, de règles ni d\'audit.') }}
                        </p>
                    </x-card>
                </div>
            </div>
        </section>

        <section class="border-y border-dark-200 bg-dark-50 py-20 dark:border-dark-700 dark:bg-dark-800 sm:py-[84px]">
            <div class="mx-auto max-w-[1160px]">
                <p class="font-sora text-[0.8rem] font-bold uppercase tracking-[0.1em] text-primary-600 dark:text-primary-400">
                    {{ __('Le socle commun') }}
                </p>

                <h2 class="font-sora mt-3 max-w-[760px] text-[clamp(1.8rem,4vw,2.9rem)] font-extrabold leading-[1.08] tracking-[-0.03em] text-dark-900 dark:text-white">
                    {{ __('Universel là où c\'est universel. Typé là où le métier diffère.') }}
                </h2>

                <p class="mt-4 max-w-[640px] text-[1.05rem] text-dark-500 dark:text-dark-400">
                    {{ __('ManeCMS ne cache pas la complexité derrière un objet « générique ». Il combine primitives universelles, domaines spécialisés, modèles métiers et règles protégées par le code.') }}
                </p>

                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($primitives as [$title, $text])
                        <x-card round="2xl">
                            <h3 class="font-sora text-[1.1rem] font-semibold tracking-[-0.03em] text-dark-900 dark:text-white">{{ $title }}</h3>
                            <p class="mt-2 text-[0.93rem] text-dark-500 dark:text-dark-400">{{ $text }}</p>
                        </x-card>
                    @endforeach
                </div>

                <div class="mt-10 flex flex-wrap items-center justify-center">
                    @foreach ($workflow as $step)
                        <span class="relative m-1 inline-flex items-center">
                            <span class="font-sora rounded-xl border border-dark-200 bg-white px-4 py-3.5 text-[0.9rem] font-semibold tracking-[-0.03em] text-dark-800 dark:border-dark-600 dark:bg-dark-900 dark:text-dark-100">
                                {{ $step }}
                            </span>

                            @unless ($loop->last)
                                <x-icon name="arrow-right" class="absolute -right-[13px] z-10 size-4 text-primary-600 dark:text-primary-400" />
                            @endunless
                        </span>
                    @endforeach
                </div>

                <p class="mx-auto mt-5 max-w-[640px] text-center text-[1.05rem] text-dark-500 dark:text-dark-400">
                    {{ __('Un flux de bout en bout, sans ressaisie, sans export-import.') }}
                </p>
            </div>
        </section>

        <section class="py-20 sm:py-[84px]">
            <div class="mx-auto grid max-w-[1160px] items-center gap-12 lg:grid-cols-2">
                <div>
                    <p class="font-sora text-[0.8rem] font-bold uppercase tracking-[0.1em] text-primary-600 dark:text-primary-400">
                        {{ __('Fiabilité par conception') }}
                    </p>

                    <h2 class="font-sora mt-3 text-[clamp(1.8rem,4vw,2.9rem)] font-extrabold leading-[1.08] tracking-[-0.03em] text-dark-900 dark:text-white">
                        {{ __('Les règles critiques ne dépendent pas de la bonne volonté d\'un écran') }}
                    </h2>

                    <ul class="mt-6 grid gap-3">
                        @foreach ($reliability as [$title, $text])
                            <li class="relative ps-7 text-dark-500 dark:text-dark-400">
                                <x-icon name="check" class="absolute start-0 top-0.5 size-4 text-primary-600 dark:text-primary-400" />

                                <span class="text-[0.95rem] leading-relaxed">
                                    <span class="font-semibold text-dark-800 dark:text-white">{{ $title }}</span>
                                    {{ $text }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    @foreach ($security as [$icon, $label])
                        <x-card round="2xl" class="text-center">
                            <div class="mx-auto flex size-10 items-center justify-center rounded-[11px] bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                                <x-icon :name="$icon" />
                            </div>

                            <p class="font-sora mt-3 text-[0.98rem] font-semibold tracking-[-0.03em] text-dark-900 dark:text-white">{{ $label }}</p>
                        </x-card>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="border-y border-dark-200 bg-dark-50 py-20 dark:border-dark-700 dark:bg-dark-800 sm:py-[84px]">
            <div class="mx-auto grid max-w-[1160px] items-center gap-12 lg:grid-cols-2">
                <x-card round="2xl" class="grid gap-3 !p-5 shadow-2xl">
                    <div class="ms-auto max-w-[88%] rounded-xl bg-primary-50 px-3.5 py-2.5 text-[0.9rem] text-dark-800 dark:bg-primary-500/10 dark:text-dark-100">
                        {{ __('Montre-moi les impayés de plus de 30 jours.') }}
                    </div>

                    <div class="max-w-[88%] rounded-xl border border-dark-200 bg-white px-3.5 py-2.5 text-[0.9rem] text-dark-600 dark:border-dark-700 dark:bg-dark-900 dark:text-dark-300">
                        {{ __('14 factures, 38 200 € au total, limitées à votre périmètre. Je prépare des relances ?') }}
                    </div>

                    <div class="ms-auto max-w-[88%] rounded-xl bg-primary-50 px-3.5 py-2.5 text-[0.9rem] text-dark-800 dark:bg-primary-500/10 dark:text-dark-100">
                        {{ __('Oui, prépare-les.') }}
                    </div>

                    <div class="max-w-[88%] rounded-xl border border-dashed border-primary-300 px-3.5 py-2.5 text-[0.9rem] text-primary-700 dark:border-primary-700 dark:text-primary-300">
                        {{ __('14 brouillons créés — en attente de votre approbation avant tout envoi.') }}
                    </div>
                </x-card>

                <div>
                    <p class="font-sora text-[0.8rem] font-bold uppercase tracking-[0.1em] text-primary-600 dark:text-primary-400">
                        {{ __('IA contrôlée') }}
                    </p>

                    <h2 class="font-sora mt-3 text-[clamp(1.8rem,4vw,2.9rem)] font-extrabold leading-[1.08] tracking-[-0.03em] text-dark-900 dark:text-white">
                        {{ __('Un copilote qui respecte vos droits et vos règles') }}
                    </h2>

                    <p class="mt-4 max-w-[640px] text-[1.05rem] text-dark-500 dark:text-dark-400">
                        {{ __('L\'IA propose, prépare et simule. Le domaine valide, autorise et exécute.') }}
                    </p>

                    <ul class="mt-6 grid gap-3">
                        @foreach ($assistant as [$title, $text])
                            <li class="relative ps-7 text-dark-500 dark:text-dark-400">
                                <x-icon name="check" class="absolute start-0 top-0.5 size-4 text-primary-600 dark:text-primary-400" />

                                <span class="text-[0.95rem] leading-relaxed">
                                    <span class="font-semibold text-dark-800 dark:text-white">{{ $title }}</span>
                                    {{ $text }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        <section class="py-20 sm:py-[84px]">
            <div class="mx-auto max-w-[1160px]">
                <p class="font-sora text-[0.8rem] font-bold uppercase tracking-[0.1em] text-primary-600 dark:text-primary-400">
                    {{ __('Modèles métiers') }}
                </p>

                <h2 class="font-sora mt-3 max-w-[760px] text-[clamp(1.8rem,4vw,2.9rem)] font-extrabold leading-[1.08] tracking-[-0.03em] text-dark-900 dark:text-white">
                    {{ __('23 modèles prêts à installer, pour chaque type d\'organisation') }}
                </h2>

                <p class="mt-4 max-w-[640px] text-[1.05rem] text-dark-500 dark:text-dark-400">
                    {{ __('Choisissez un modèle : modules, rôles, workflows, documents et tableaux de bord sont configurés pour vous. Les packs de juridiction (fiscalité, paie) sont versionnés.') }}
                </p>

                <div class="mt-7 flex flex-wrap gap-[7px]">
                    @foreach ($templates as $template)
                        <span class="rounded-full bg-primary-50 px-2.5 py-1 text-[0.8rem] font-medium text-dark-800 dark:bg-primary-500/10 dark:text-dark-100">
                            {{ $template }}
                        </span>
                    @endforeach
                </div>

                <div class="mt-9 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($stats as [$value, $label])
                        <x-card round="2xl" class="py-[22px] text-center">
                            <p class="font-sora text-[2rem] font-extrabold tracking-[-0.03em] text-primary-600 dark:text-primary-400">{{ $value }}</p>
                            <p class="mt-1 text-[0.85rem] text-dark-500 dark:text-dark-400">{{ $label }}</p>
                        </x-card>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="border-y border-dark-200 bg-dark-50 py-20 dark:border-dark-700 dark:bg-dark-800 sm:py-[84px]">
            <div class="mx-auto max-w-[1160px]">
                <p class="font-sora text-[0.8rem] font-bold uppercase tracking-[0.1em] text-primary-600 dark:text-primary-400">
                    {{ __('Fondations techniques') }}
                </p>

                <h2 class="font-sora mt-3 text-[clamp(1.8rem,4vw,2.9rem)] font-extrabold leading-[1.08] tracking-[-0.03em] text-dark-900 dark:text-white">
                    {{ __('Moderne, ouvert, vérifiable') }}
                </h2>

                <p class="mt-4 max-w-[640px] text-[1.05rem] text-dark-500 dark:text-dark-400">
                    {{ __('Monolithe modulaire Laravel, interface Livewire avec TallStackUI et design system ManeUI, API versionnée documentée en OpenAPI, extensions via SDK.') }}
                </p>

                <div class="mt-7 flex flex-wrap gap-[7px]">
                    @foreach ($stack as $technology)
                        <span class="rounded-full bg-primary-50 px-2.5 py-1 text-[0.8rem] font-medium text-dark-800 dark:bg-primary-500/10 dark:text-dark-100">{{ $technology }}</span>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="py-20 sm:py-[84px]">
            <div class="mx-auto max-w-[1160px]">
                <p class="font-sora text-[0.8rem] font-bold uppercase tracking-[0.1em] text-primary-600 dark:text-primary-400">
                    {{ __('Questions fréquentes') }}
                </p>

                <h2 class="font-sora mt-3 text-[clamp(1.8rem,4vw,2.9rem)] font-extrabold leading-[1.08] tracking-[-0.03em] text-dark-900 dark:text-white">
                    {{ __('Vos questions') }}
                </h2>

                <x-accordion shadowless bordered class="mt-7 max-w-[820px] [&_summary]:font-sora [&_summary]:text-[1.02rem]">
                    @foreach ($faq as $index => [$question, $answer])
                        <x-accordion.items :title="$question" :id="'faq-'.$index">
                            {{ $answer }}
                        </x-accordion.items>
                    @endforeach
                </x-accordion>
            </div>
        </section>

        <section id="cta" class="border-t border-dark-200 bg-dark-50 py-20 text-center dark:border-dark-700 dark:bg-dark-800 sm:py-[84px]">
            <div class="mx-auto max-w-[1160px]">
                <img src="/logo-light.svg" alt="" width="64" height="64" class="mx-auto size-16 dark:hidden" />
                <img src="/logo-dark.svg" alt="" width="64" height="64" class="mx-auto hidden size-16 dark:block" />

                <h2 class="font-sora mx-auto mt-4 max-w-[760px] text-[clamp(1.8rem,4vw,2.9rem)] font-extrabold leading-[1.08] tracking-[-0.03em] text-dark-900 dark:text-white">
                    {{ __('Un seul outil. Toute votre organisation.') }}
                </h2>

                <p class="mx-auto mt-4 max-w-[640px] text-[1.05rem] text-dark-500 dark:text-dark-400">
                    {{ __('Rejoignez ManeCMS et reprenez le contrôle de votre contenu, de vos clients et de vos opérations.') }}
                </p>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <x-button :href="route('register')" navigate :text="__('Démarrer gratuitement')" />

                    <x-button flat :href="route('login')" navigate :text="__('Parler à l\'équipe')" />
                </div>
            </div>
        </section>
    </div>
</x-layouts::guest>