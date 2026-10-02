<?php

use App\Domain\Identity\Actions\RevokeSessions;
use App\Domain\Identity\DeviceName;
use App\Domain\Identity\Models\UserDevice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('layouts::sidebar')] #[Title('Sessions')] class extends Component {
    use Interactions;

    private const int DEVICE_LIMIT = 20;

    public string $password = '';

    public bool $showRevokeOthersModal = false;

    /**
     * Browsers signed in to the account, newest activity first.
     *
     * Session ids are bearer secrets: the page only ever sees their hash.
     *
     * @return list<array{key: string, device: string, ip: string|null, last_active: CarbonImmutable, current: bool}>
     */
    #[Computed]
    public function sessions(): array
    {
        $currentId = session()->getId();

        return DB::table('sessions')
            ->where('user_id', $this->user()->id)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn (object $session): array => [
                'key' => RevokeSessions::keyOf((string) $session->id),
                'device' => DeviceName::from(is_string($session->user_agent) ? $session->user_agent : null),
                'ip' => is_string($session->ip_address) ? $session->ip_address : null,
                'last_active' => CarbonImmutable::createFromTimestamp((int) $session->last_activity),
                'current' => hash_equals($currentId, (string) $session->id),
            ])
            ->values()
            ->all();
    }

    /**
     * @return Illuminate\Database\Eloquent\Collection<int, UserDevice>
     */
    #[Computed]
    public function devices(): Illuminate\Database\Eloquent\Collection
    {
        return UserDevice::query()->where('user_id', $this->user()->id)->latest('last_seen_at')->limit(self::DEVICE_LIMIT)->get();
    }

    public function revoke(RevokeSessions $revokeSessions, string $sessionKey): void
    {
        $revokeSessions->one($this->user(), $sessionKey, session()->getId());

        unset($this->sessions);
        $this->toast()->success(__('Session signed out.'))->send();
    }

    public function revokeOthers(RevokeSessions $revokeSessions): void
    {
        try {
            $count = $revokeSessions->others($this->user(), $this->password, session()->getId());
        } finally {
            $this->reset('password');
        }

        $this->showRevokeOthersModal = false;
        unset($this->sessions);
        $this->toast()->success(trans_choice('{0} No other session was open.|{1} One other session signed out.|[2,*] :count other sessions signed out.', $count, ['count' => $count]))->send();
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}; ?>

<x-pages::settings.layout :heading="__('Sessions')" :subheading="__('Where your account is signed in. Sign out what you do not recognise.')">
    <x-mane::card>
        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <x-mane::section-header :level="3" :title="__('Open sessions')" />

                @if (count($this->sessions) > 1)
                    <x-mane::button variant="secondary" size="sm" icon="arrow-right-start-on-rectangle" :text="__('Sign out the other sessions')" wire:click="$set('showRevokeOthersModal', true)" data-test="revoke-other-sessions-button" />
                @endif
            </div>

            @error('session')
                <x-mane::alert tone="warning" :text="$message" />
            @enderror

            <ul class="divide-y divide-line" data-test="sessions">
                @foreach ($this->sessions as $session)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="session-{{ $loop->index }}">
                        <div class="flex items-center gap-3">
                            <x-mane::icon name="computer-desktop" class="size-5 text-fg-muted" />
                            <div class="flex flex-col">
                                <span class="font-medium text-fg">{{ $session['device'] }}</span>
                                <span class="text-sm text-fg-muted">{{ $session['ip'] ?? '—' }} · {{ $session['last_active']->diffForHumans() }}</span>
                            </div>
                        </div>

                        @if ($session['current'])
                            <x-mane::status tone="success" :text="__('This browser')" />
                        @else
                            <x-mane::button size="sm" variant="ghost" :text="__('Sign out')" wire:click="revoke('{{ $session['key'] }}')" loading="revoke" data-test="revoke-session-{{ $loop->index }}" />
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </x-mane::card>

    <x-mane::card>
        <div class="flex flex-col gap-4">
            <x-mane::section-header :level="3" :title="__('Known devices')" :description="__('You get an email when your account signs in from a device that is not on this list.')" />

            @if ($this->devices->isEmpty())
                <x-mane::empty-state kind="empty" :title="__('No device recorded yet')" />
            @else
                <ul class="divide-y divide-line" data-test="devices">
                    @foreach ($this->devices as $device)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="device-{{ $device->id }}">
                            <span class="font-medium text-fg">{{ App\Domain\Identity\DeviceName::from($device->user_agent) }}</span>
                            <span class="text-sm text-fg-muted">
                                {{ __('First seen :first, last seen :last', ['first' => $device->first_seen_at->translatedFormat('j M Y'), 'last' => $device->last_seen_at->diffForHumans()]) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </x-mane::card>

    <x-mane::modal id="revoke-other-sessions" size="md" wire="showRevokeOthersModal" :title="__('Sign out the other sessions?')">
        <x-mane::form wire:submit="revokeOthers" :dirty-notice="false">
            <p class="text-sm text-fg-muted">{{ __('Every other browser is signed out, including those that remembered you.') }}</p>

            <x-mane::password wire:model="password" :label="__('Your password')" required autocomplete="current-password" data-test="revoke-others-password" />

            <x-slot:actions>
                <x-mane::button variant="ghost" :text="__('Cancel')" wire:click="$set('showRevokeOthersModal', false)" />
                <x-mane::button type="submit" variant="danger" loading="revokeOthers" :text="__('Sign out the other sessions')" data-test="confirm-revoke-others-button" />
            </x-slot:actions>
        </x-mane::form>
    </x-mane::modal>
</x-pages::settings.layout>
