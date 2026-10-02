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
    <section class="relative overflow-hidden px-4 pb-10 pt-16 text-center sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[600px] bg-brand-subtle blur-3xl" aria-hidden="true"></div>

        <x-mane::badge size="sm" :text="__('Business · Contenu · Commerce · Opérations · IA')" />

        <h1 class="font-display mx-auto mt-6 max-w-[900px] text-[clamp(2.3rem,6vw,4.6rem)] font-extrabold leading-[1.03] tracking-[-0.03em] text-fg">
            {{ __('Une plateforme.') }}
            <span class="text-brand">{{ __('Toute') }}</span>
            {{ __('votre organisation.') }}
        </h1>

        <p class="mx-auto mt-5 max-w-[660px] text-[1.15rem] leading-relaxed text-fg-muted">
            {{ __('ManeCMS réunit CMS, CRM, ERP, e-commerce, paiements, réservations, RH, paie et IA sur un même socle : mêmes identités, mêmes données, mêmes règles. Fini les outils qui ne se parlent pas.') }}
        </p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <x-mane::button size="lg" href="#cta" :text="__('Démarrer avec ManeCMS')" />
            <x-mane::button size="lg" variant="ghost" href="#modules" :text="__('Explorer les modules')" />
        </div>

        <ul class="mt-10 flex flex-wrap items-center justify-center gap-2" aria-label="{{ __('Promise') }}">
            @foreach ([__('Toute personne'), __('Toute organisation'), __('Toute langue'), __('Tout canal'), __('Tout métier')] as $claim)
                <li wire:key="claim-{{ $loop->index }}"><x-mane::badge variant="muted" :text="$claim" /></li>
            @endforeach
        </ul>

        <div class="mx-auto mt-11 max-w-[920px] text-start" aria-hidden="true">
            <x-mane::card paddingless rounded="2xl" class="shadow-2xl">
                <div class="flex items-center gap-2 border-b border-line px-4 py-3">
                    <span class="size-2.5 rounded-full bg-brand"></span>
                    <span class="size-2.5 rounded-full bg-line"></span>
                    <span class="size-2.5 rounded-full bg-line"></span>
                </div>

                <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($kpis as [$label, $value, $delta])
                        <div class="rounded-surface border border-line p-3.5">
                            <p class="text-[0.75rem] text-fg-muted">{{ $label }}</p>
                            <p class="font-display mt-0.5 text-[1.35rem] font-bold tracking-[-0.03em] text-fg">{{ $value }}</p>
                            <p class="text-[0.75rem] font-semibold text-brand">{{ $delta }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-wrap gap-1.5 px-4 pb-4">
                    @foreach ($pipeline as $step)
                        <span class="min-w-[90px] flex-1 rounded-control bg-brand-subtle px-1.5 py-2 text-center text-[0.78rem] font-semibold text-brand">{{ $step }}</span>
                    @endforeach
                </div>
            </x-mane::card>
        </div>
    </section>

    <x-mane::section
        id="modules"
        :eyebrow="__('Tout-en-un')"
        :title="__('60+ domaines métier, un seul produit')"
        :lead="__('Pas de plugins greffés après coup : chaque domaine possède ses règles, ses données et ses événements, et tous partagent le même socle.')"
    >
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($modules as [$icon, $title, $items])
                <x-mane::feature :icon="$icon" :title="$title" wire:key="module-{{ $loop->index }}">
                    <ul class="flex flex-wrap gap-[7px]">
                        @foreach ($items as $item)
                            <li><x-mane::badge :text="$item" /></li>
                        @endforeach
                    </ul>
                </x-mane::feature>
            @endforeach

            <x-mane::feature icon="plus" :title="__('Et les suivants')">
                {{ __('Le socle reste le même : ajouter un domaine n\'introduit jamais un second jeu d\'identités, de règles ni d\'audit.') }}
            </x-mane::feature>
        </div>
    </x-mane::section>

    <x-mane::section
        tone="sunken"
        :eyebrow="__('Le socle commun')"
        :title="__('Universel là où c\'est universel. Typé là où le métier diffère.')"
        :lead="__('ManeCMS ne cache pas la complexité derrière un objet « générique ». Il combine primitives universelles, domaines spécialisés, modèles métiers et règles protégées par le code.')"
    >
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($primitives as [$title, $text])
                <x-mane::feature :title="$title" wire:key="primitive-{{ $loop->index }}">{{ $text }}</x-mane::feature>
            @endforeach
        </div>

        <ol class="mt-10 flex flex-wrap items-center justify-center" aria-label="{{ __('Un flux de bout en bout, sans ressaisie, sans export-import.') }}">
            @foreach ($workflow as $step)
                <li class="relative m-1 inline-flex items-center" wire:key="flow-{{ $loop->index }}">
                    <span class="font-display rounded-surface border border-line bg-surface px-4 py-3.5 text-[0.9rem] font-semibold tracking-[-0.03em] text-fg">{{ $step }}</span>

                    @unless ($loop->last)
                        <x-mane::icon name="arrow-right" class="absolute -end-[13px] z-10 size-4 text-brand rtl:rotate-180" />
                    @endunless
                </li>
            @endforeach
        </ol>

        <p class="mx-auto mt-5 max-w-[640px] text-center text-[1.05rem] text-fg-muted">
            {{ __('Un flux de bout en bout, sans ressaisie, sans export-import.') }}
        </p>
    </x-mane::section>

    <x-mane::section :eyebrow="__('Fiabilité par conception')" :title="__('Les règles critiques ne dépendent pas de la bonne volonté d\'un écran')">
        <div class="grid items-center gap-12 lg:grid-cols-2">
            <ul class="grid gap-3">
                @foreach ($reliability as [$title, $text])
                    <x-mane::check-item :title="$title" wire:key="reliability-{{ $loop->index }}">{{ $text }}</x-mane::check-item>
                @endforeach
            </ul>

            <div class="grid grid-cols-2 gap-4">
                @foreach ($security as [$icon, $label])
                    <x-mane::feature align="center" :icon="$icon" :title="$label" wire:key="security-{{ $loop->index }}" />
                @endforeach
            </div>
        </div>
    </x-mane::section>

    <x-mane::section
        tone="sunken"
        :eyebrow="__('IA contrôlée')"
        :title="__('Un copilote qui respecte vos droits et vos règles')"
        :lead="__('L\'IA propose, prépare et simule. Le domaine valide, autorise et exécute.')"
    >
        <div class="grid items-center gap-12 lg:grid-cols-2">
            <x-mane::card rounded="2xl" class="shadow-2xl">
                <div class="grid gap-3">
                    <p class="ms-auto max-w-[88%] rounded-surface bg-brand-subtle px-3.5 py-2.5 text-[0.9rem] text-fg">{{ __('Montre-moi les impayés de plus de 30 jours.') }}</p>
                    <p class="max-w-[88%] rounded-surface border border-line bg-surface px-3.5 py-2.5 text-[0.9rem] text-fg-muted">{{ __('14 factures, 38 200 € au total, limitées à votre périmètre. Je prépare des relances ?') }}</p>
                    <p class="ms-auto max-w-[88%] rounded-surface bg-brand-subtle px-3.5 py-2.5 text-[0.9rem] text-fg">{{ __('Oui, prépare-les.') }}</p>
                    <p class="max-w-[88%] rounded-surface border border-dashed border-brand px-3.5 py-2.5 text-[0.9rem] text-brand">{{ __('14 brouillons créés — en attente de votre approbation avant tout envoi.') }}</p>
                </div>
            </x-mane::card>

            <ul class="grid gap-3">
                @foreach ($assistant as [$title, $text])
                    <x-mane::check-item :title="$title" wire:key="assistant-{{ $loop->index }}">{{ $text }}</x-mane::check-item>
                @endforeach
            </ul>
        </div>
    </x-mane::section>

    <x-mane::section
        :eyebrow="__('Modèles métiers')"
        :title="__('23 modèles prêts à installer, pour chaque type d\'organisation')"
        :lead="__('Choisissez un modèle : modules, rôles, workflows, documents et tableaux de bord sont configurés pour vous. Les packs de juridiction (fiscalité, paie) sont versionnés.')"
    >
        <ul class="flex flex-wrap gap-[7px]">
            @foreach ($templates as $template)
                <li wire:key="template-{{ $loop->index }}"><x-mane::badge :text="$template" /></li>
            @endforeach
        </ul>

        <div class="mt-9 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($stats as [$value, $label])
                <x-mane::card rounded="2xl" class="text-center" wire:key="stat-{{ $loop->index }}">
                    <x-mane::stat class="items-center" :value="$value" :label="$label" />
                </x-mane::card>
            @endforeach
        </div>
    </x-mane::section>

    <x-mane::section
        tone="sunken"
        :eyebrow="__('Fondations techniques')"
        :title="__('Moderne, ouvert, vérifiable')"
        :lead="__('Monolithe modulaire Laravel, interface Livewire avec TallStackUI et design system ManeUI, API versionnée documentée en OpenAPI, extensions via SDK.')"
    >
        <ul class="flex flex-wrap gap-[7px]">
            @foreach ($stack as $technology)
                <li wire:key="stack-{{ $loop->index }}"><x-mane::badge variant="muted" :text="$technology" /></li>
            @endforeach
        </ul>
    </x-mane::section>

    <x-mane::section :eyebrow="__('Questions fréquentes')" :title="__('Vos questions')">
        <x-mane::accordion class="max-w-[820px] [&_summary]:font-display [&_summary]:text-[1.02rem]">
            @foreach ($faq as $index => [$question, $answer])
                <x-mane::accordion.item :title="$question" :id="'faq-'.$index">{{ $answer }}</x-mane::accordion.item>
            @endforeach
        </x-mane::accordion>
    </x-mane::section>

    <x-mane::section
        id="cta"
        tone="sunken"
        align="center"
        :title="__('Un seul outil. Toute votre organisation.')"
        :lead="__('Rejoignez ManeCMS et reprenez le contrôle de votre contenu, de vos clients et de vos opérations.')"
    >
        <div class="flex flex-wrap items-center justify-center gap-3">
            <x-mane::button size="lg" :href="route('register')" navigate :text="__('Démarrer gratuitement')" />
            <x-mane::button size="lg" variant="ghost" :href="route('login')" navigate :text="__('Parler à l\'équipe')" />
        </div>
    </x-mane::section>
</x-layouts::guest>
