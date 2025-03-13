<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create([
        'preferences' => [
            'keywords' => ['technology', 'coding'],
            'sources' => ['TechCrunch'],
            'categories' => ['Technology'],
        ],
    ]);
});

// Unauthenticated tests
test('unauthenticated users cannot access preferences', function () {
    // GET endpoint should return 401
    $this->getJson(route('user-preferences.index'))
        ->assertUnauthorized();

    // PUT endpoint should also return 401
    $this->putJson(route('user-preferences.update'))
        ->assertUnauthorized();
});

test('it returns user preferences', function () {
    $response = actingAs($this->user)
        ->getJson(route('user-preferences.index'));

    $response->assertOk()
        ->assertJsonFragment([
            'keywords' => ['technology', 'coding'],
            'sources' => ['TechCrunch'],
            'categories' => ['Technology'],
        ]);
});

test('it returns empty collection when user has no preferences', function () {
    $user = User::factory()->create(['preferences' => null]);

    $response = actingAs($user)
        ->getJson(route('user-preferences.index'));

    $response->assertOk()
        ->assertExactJson([
            'data' => [],
        ]);
});

test('it updates user preferences - success', function (array $input) {
    $response = actingAs($this->user)
        ->putJson(route('user-preferences.update'), $input);

    $response
        ->assertOk()
        ->assertJsonFragment($input);

    // Verify that the user record was updated
    $this->user->refresh();
    expect($this->user->preferences)->toMatchArray($input);
})->with([
    'complete preferences' => [
        'input' => [
            'keywords' => ['php', 'laravel'],
            'sources' => ['BBC'],
            'categories' => ['Programming'],
            'authors' => ['John Doe'],
        ],
    ],
    'partial preferences' => [
        'input' => [
            'keywords' => ['php'],
        ],
    ],
    'empty arrays' => [
        'input' => [
            'keywords' => [],
            'sources' => [],
            'categories' => [],
            'authors' => [],
        ],
    ],
]);

test('it preserves existing preferences when updating partially', function () {
    // Update only "sources"
    $response = actingAs($this->user)
        ->putJson(route('user-preferences.update'), [
            'sources' => ['BBC'],
        ]);

    $response->assertOk()
        ->assertJsonFragment([
            'sources' => ['BBC'],
        ]);

    $this->user->refresh();

    expect($this->user->preferences['sources'])->toBe(['BBC']);
    expect($this->user->preferences['keywords'])->toBe(['technology', 'coding']);
    expect($this->user->preferences['categories'])->toBe(['Technology']);
});
