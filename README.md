# Laravel News Aggregator

This is a simple news aggregator that fetches news from different sources and displays them using api.

This project is a case study for the Laravel framework.

## Features

-   Fetch news from different sources through command line `php artisan fetch:news {provider}`

-   Display news from different sources through api `api/articles`

-   List and Update User Preferences `api/user-preferences`

-   Fetching news regularly using Laravel Scheduler (every 6 hours)

-   Full test coverage (90%+)

## Supported News Providers

-   [News API](https://newsapi.org/)
-   [News AI](https://newsapi.ai/documentation?tab=searchArticles)
-   [The Guardian](https://open-platform.theguardian.com/documentation/search)

## Technical Requirements

-   PHP 8.3
-   Laravel 12

## Installation

1. Clone the repository

```bash
git clone git@github.com:nagi1/news-aggregator.git
```

2. Install dependencies

```bash
composer install
```

3. Create a `.env` file

```bash
cp .env.example .env
```

4. Generate an application key

```bash
php artisan key:generate
```

5. Run the migrations

```bash
php artisan migrate
```

6. Generate Test User and Get Authorization Token

```bash
php artisan create:test-user
```

## Usage

### Fetch News From Providers

To fetch news from a provider, use the following command:

```bash
php artisan fetch:news {provider}
```

Where `{provider}` is one of the following from `NewsProviderEnum`:

-   `news-api`
-   `news-ai`
-   `guardian`

## API Endpoints

### Authentication

For authentication, send the authorization token in the header as follows:

```bash
Authorization Bearer {token}
```

### Display News

For displaying news, use the following endpoint:

```bash
GET api/articles
```

You have options to filter and search for articles using the following query parameters:

-   `search` - Search for articles using the keyword in the title or description or content.

-   `sources` - Filter articles by source.

-   `category` - Filter articles by category.

-   `date` - Filter articles by date. Format: `Y-m-d`

You can pass `all` to just list all of the articles without any filter.

This endpoint is paginated and you can use the following query parameters to navigate through the pages.

Response:

```json
{
    "data": [
        {
            "id": 1,
            "slug": "non-reiciendis-tenetur-voluptas-aut-qui-et-officiis",
            "title": "New Technology in Coding",
            "api_provider": "news-api",
            "source": "TechCrunch",
            "author": "Mrs. Lavina Mayer Jr.",
            "category": "Technology",
            "description": "Et quia a esse quas autem asperiores.",
            "content": "Sit delectus dolor velit est et nihil ab. Mollitia quod sint id dignissimos ut quo ipsam. Ut sunt sit officia commodi et facilis. Exercitationem sint aut aut. Quia et omnis et fugiat.",
            "url": "http://www.altenwerth.org/tempore-impedit-autem-tenetur-aspernatur-dignissimos-velit-aspernatur-eum",
            "image": "https://via.placeholder.com/640x480.png/005588?text=harum",
            "published_at": "1995-11-15T21:10:20.000000Z",
            "created_at": "2025-01-01T12:00:00.000000Z",
            "updated_at": "2025-01-01T12:00:00.000000Z"
        }
    ],
    "links": {
        "first": "http://news-aggregation.test/api/articles?page=1",
        "last": "http://news-aggregation.test/api/articles?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "links": [
            { "url": null, "label": "&laquo; Previous", "active": false },
            {
                "url": "http://news-aggregation.test/api/articles?page=1",
                "label": "1",
                "active": true
            },
            { "url": null, "label": "Next &raquo;", "active": false }
        ],
        "path": "http://news-aggregation.test/api/articles",
        "per_page": 50,
        "to": 1,
        "total": 1
    }
}
```

### User Preferences

For listing and updating user preferences, use the following endpoints:

```bash
GET api/user-preferences
```

Response:

```json
{
    "data": {
        "categories": ["business", "entertainment"],
        "sources": ["bbc-news", "cnn"],
        "keywords": ["apple", "microsoft"],
        "authors": ["John Doe", "Jane Doe"]
    }
}
```

```bash
PUT api/user-preferences
```

Request:

```json
{
    "categories": ["business", "entertainment"],
    "sources": ["bbc-news", "cnn"],
    "keywords": ["apple", "microsoft"],
    "authors": ["John Doe", "Jane Doe"]
}
```

## Tests

I used Laravel Pest for testing. To run the tests, use the following command:

Because `fullText` search was used in the articles table, testing using SQLite will not work. Therefore, I recommend using MySQL for testing.

For your convenience, I have created a command that prepares the test database for you.

```bash
php artisan prepare-test-db
```

```bash
./vendor/bin/pest
```
