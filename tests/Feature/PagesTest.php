<?php

use App\Models\ChangeLog;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->withoutVite();
});

it('opens in Arabic on a first visit, even for an English browser', function () {
    $this->withHeader('Accept-Language', 'en')
        ->get('/mage')
        ->assertOk()
        ->assertSee('الساحر')
        ->assertSessionHas('locale', 'ar');
});

it('shows the three classes on the home page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(['warriorg.webp', 'mageg.webp', 'archerg.webp']);
});

it('sends the four trees and all skills of a class', function (string $class, int $skills) {
    $this->get("/{$class}")
        ->assertOk()
        ->assertViewHas('payload', fn (array $payload) => $payload['class'] === $class
            && count($payload['trees']) === 4
            && count($payload['skills']) === $skills);
})->with([
    'warrior' => ['warrior', 120],
    'mage' => ['mage', 124],
    'archer' => ['archer', 117],
]);

it('returns 404 for an unknown class', function () {
    $this->get('/thief')->assertNotFound();
});

it('shows the 404 page in the chosen language and keeps that language', function () {
    $this->withSession(['locale' => 'ar'])
        ->get('/no-such-page')
        ->assertNotFound()
        ->assertSee('الصفحة غير موجودة')
        ->assertSessionHas('locale', 'ar');
});

it('lists every patch on the change log, newest first', function () {
    $dates = ChangeLog::query()->distinct()->orderByDesc('dt')->pluck('dt')
        ->map(fn ($dt) => Carbon::parse($dt)->locale('en')->translatedFormat('F j, Y'))
        ->all();

    $this->withSession(['locale' => 'en'])
        ->get('/changelog')
        ->assertOk()
        ->assertSeeInOrder($dates);
});

it('goes back to the same page in English after a language switch', function () {
    $this->from('/mage')
        ->get('/locale/en?to=%2Fchangelog')
        ->assertRedirect('/changelog')
        ->assertSessionHas('locale', 'en');

    $this->get('/changelog')->assertOk();
});

it('keeps the query string after a language switch', function () {
    $this->get('/locale/en?to='.urlencode('/mage?skill=121'))
        ->assertRedirect('/mage?skill=121');
});

it('never redirects to another site after a language switch', function () {
    foreach (['//example.com', 'https://example.com', '/\\example.com', ''] as $to) {
        $this->get('/locale/en?to='.urlencode($to))->assertRedirect(route('Home'));
    }
    $this->get('/locale/en')->assertRedirect(route('Home'));
});

it('the language link points back to the current page', function () {
    $this->get('/changelog')
        ->assertOk()
        ->assertSee(e(route('locale.switch', ['locale' => 'en', 'to' => '/changelog'])), false);
});
