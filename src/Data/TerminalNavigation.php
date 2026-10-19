<?php

declare(strict_types=1);

namespace LBHurtado\FormFlowManager\Data;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/** A server-selected terminal result; never construct from untrusted request input. */
final readonly class TerminalNavigation
{
    public function __construct(public string $url) {}

    public function toResponse(Request $request): Response
    {
        $response = $request->header('X-Inertia')
            ? Inertia::location($this->url)
            : redirect()->away($this->url, 303);

        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
