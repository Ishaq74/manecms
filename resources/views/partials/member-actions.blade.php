{{-- Actions cell of the members table: only rendered when the Policy Engine allows at least one action. --}}
<x-mane::icon-button
    icon="pencil-square"
    size="sm"
    :label="__('Manage :name', ['name' => $member->user->name])"
    wire:click="manage('{{ $member->id }}')"
    data-test="manage-member-{{ $member->id }}"
/>
