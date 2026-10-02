<?php

use App\Domain\Platform\Errors\DomainError;
use App\Domain\Tenancy\Actions\AcceptInvitation;
use App\Domain\Tenancy\Exceptions\InvitationRejected;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::sidebar')] #[Title('Invitation')] class extends Component {
    #[Locked]
    public string $invitationId = '';

    #[Locked]
    public string $token = '';

    public string $tenantName = '';

    public string $roleLabel = '';

    public string $inviterName = '';

    public string $email = '';

    public string $status = '';

    public ?string $error = null;

    public function mount(AcceptInvitation $acceptInvitation, string $invitation, string $token): void
    {
        try {
            $found = $acceptInvitation->find($this->user(), $invitation, $token);
        } catch (InvitationRejected $rejected) {
            abort($rejected->status(), $rejected->getMessage());
        }

        $this->invitationId = $found->id;
        $this->token = $token;
        $this->tenantName = $found->tenant->name;
        $this->roleLabel = $found->role->label();
        $this->inviterName = $found->inviter->name ?? __('a former member');
        $this->email = $found->email;
        $this->status = $found->status()->value;
    }

    public function accept(AcceptInvitation $acceptInvitation): void
    {
        try {
            $workspace = $acceptInvitation($this->user(), $this->invitationId, $this->token);
        } catch (DomainError $error) {
            $this->error = $error->getMessage();

            return;
        }

        $this->redirectRoute('workspace.home', ['workspace' => $workspace->id], navigate: true);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}; ?>

<section class="mx-auto flex w-full max-w-lg flex-col gap-6">
    <x-mane::page-header :title="__('Join :space', ['space' => $tenantName])" :description="__(':inviter invited :email to join this space as :role.', ['inviter' => $inviterName, 'email' => $email, 'role' => $roleLabel])" />

    <x-mane::card>
        <div class="flex flex-col gap-4">
            @if ($error !== null)
                <x-mane::alert tone="danger" :text="$error" data-test="invitation-error" />
            @endif

            @if ($status === 'pending')
                <p class="text-sm text-fg-muted">{{ __('You will find the space in your workspace switcher once you have joined it.') }}</p>

                <x-mane::button block icon="check" loading="accept" wire:click="accept" :text="__('Join the space')" data-test="accept-invitation-button" />
            @else
                <x-mane::empty-state
                    kind="no-access"
                    :title="match ($status) { 'accepted' => __('This invitation has already been used.'), 'revoked' => __('This invitation has been revoked.'), default => __('This invitation has expired. Ask for a new one.') }"
                />
            @endif
        </div>
    </x-mane::card>
</section>
