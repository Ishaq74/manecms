<?php

use App\Domain\Platform\Models\Impersonation;
use App\Domain\Platform\ImpersonationSession;
use App\Models\User;

it('debugs', function (): void {
    [$user] = joinWorkspace();
    $impersonation = Impersonation::factory()->create(['operator_id' => User::factory()->operator(), 'user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->withSession([ImpersonationSession::SESSION_KEY => $impersonation->id])
        ->post(route('two-factor.enable'));

    dump($response->status(), $response->headers->get('content-type'), substr(strip_tags((string) $response->getContent()), 0, 600));
});
