<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LBHurtado\FormFlowManager\Data\TerminalNavigation;

beforeEach(function () {
    Route::get('/terminal-navigation', fn (Request $request) => (new TerminalNavigation('https://example.com/landing'))->toResponse($request));
});

it('performs an ordinary terminal navigation as a GET redirect', function () {
    $this->get('/terminal-navigation')->assertStatus(303)->assertRedirect('https://example.com/landing')
        ->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('Cache-Control', 'no-store, private');
});

it('directs Inertia to navigate the current tab', function () {
    $this->withHeaders(['X-Inertia' => 'true'])->get('/terminal-navigation')
        ->assertStatus(409)->assertHeader('X-Inertia-Location', 'https://example.com/landing');
});
