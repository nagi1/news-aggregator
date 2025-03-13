<?php

use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create([
        'preferences' => [
            'keywords' => ['technology', 'coding'],
            'sources' => ['TechCrunch'],
            'categories' => ['Technology'],
        ],
    ]);

    Carbon::setTestNow('2025-01-01 12:00:00');
});

test('unauthenticated users cannot access articles', function () {
    $response = $this->getJson(route('articles.index'));

    $response->assertUnauthorized();
});

test('it returns articles based on user preferences when no search criteria is provided', function () {
    // Create articles that match preferences
    $matchingArticle = Article::factory()->create([
        'title' => 'New Technology in Coding',
        'source' => 'TechCrunch',
        'category' => 'Technology',
    ]);

    // Create articles that don't match preferences
    $nonMatchingArticle = Article::factory()->create([
        'title' => 'Sports News',
        'source' => 'ESPN',
        'category' => 'Sports',
    ]);

    $response = $this->actingAs($this->user)->getJson(route('articles.index'));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingArticle->id);
});

test('it returns articles based on search criteria when provided', function () {
    // Create test articles
    $searchArticle = Article::factory()->create([
        'title' => 'Specific Search Term',
        'source' => 'CNN',
        'category' => 'Politics',
    ]);

    $otherArticle = Article::factory()->create([
        'title' => 'Unrelated Article',
        'source' => 'BBC',
        'category' => 'Entertainment',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('articles.index', ['search' => 'Specific']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $searchArticle->id);
});

test('it can filter articles by source', function () {
    // Create test articles
    $bbcArticle = Article::factory()->create(['source' => 'BBC']);
    $cnnArticle = Article::factory()->create(['source' => 'CNN']);

    $response = $this->actingAs($this->user)
        ->getJson(route('articles.index', ['source' => 'BBC']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $bbcArticle->id);
});

test('it can filter articles by category', function () {
    // Create test articles
    $techArticle = Article::factory()->create(['category' => 'Technology']);
    $sportsArticle = Article::factory()->create(['category' => 'Sports']);

    $response = $this->actingAs($this->user)
        ->getJson(route('articles.index', ['category' => 'Sports']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $sportsArticle->id);
});

test('it can filter articles by date', function () {
    // Create test articles
    $todayArticle = Article::factory()->create(['published_at' => now(), 'title' => 'Today Article']);
    $yesterdayArticle = Article::factory()->create(['published_at' => now()->subDay()]);

    $response = $this->actingAs($this->user)
        ->getJson(route('articles.index', [
            'date' => now()->format('Y-m-d'),
            'all' => true,
        ]));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $todayArticle->id);
});

test('it respects the provided limit parameter', function () {
    // Create 10 articles
    Article::factory()->count(10)->create();

    $response = $this->actingAs($this->user)
        ->getJson(route('articles.index', ['limit' => 5, 'all' => true]));

    $response->assertOk()
        ->assertJsonCount(5, 'data');
});

test('it enforces maximum limit of 100', function () {
    actingAs($this->user)
        ->getJson(route('articles.index', ['limit' => 150, 'all' => true]))
        ->assertUnprocessable();
});

test('it respects pagination', function () {
    // Create 20 articles
    Article::factory()->count(20)->create();

    $response = $this->actingAs($this->user)
        ->getJson(route('articles.index', ['limit' => 10, 'page' => 2, 'all' => true]));

    $response->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.current_page', 2);
});

test('it can combine multiple filter criteria', function () {
    // Create articles with different combinations
    $targetArticle = Article::factory()->create([
        'title' => 'Target Article',
        'source' => 'CNN',
        'category' => 'Politics',
        'published_at' => now(),
    ]);

    $notMatchingArticle = Article::factory()->create([
        'title' => 'Target Article',
        'source' => 'BBC',
        'category' => 'Sports',
        'published_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('articles.index', [
            'search' => 'Target',
            'source' => 'CNN',
            'category' => 'Politics',
            'date' => now()->format('Y-m-d'),
        ]));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $targetArticle->id);
});

test('it returns empty collection when no articles match criteria', function () {
    // Create an article that won't match our search
    Article::factory()->create(['title' => 'Unrelated']);

    $response = $this->actingAs($this->user)
        ->getJson(route('articles.index', ['search' => 'NonexistentTerm']));

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});
