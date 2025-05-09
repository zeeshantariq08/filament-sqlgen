
# filament-sqlgen

A FilamentPHP package that integrates a Gemini-powered AI assistant into your Laravel admin panel — converting natural
language into SQL and showing results in real-time.

## Features

- **Text-to-SQL AI**: Converts natural language questions into SQL using Google Gemini and executes them securely.
- **Read-only access**: Executes only `SELECT` queries for safety.
- **Interactive UI**: Results are rendered in a styled HTML table within the Filament widget.
- **Easy setup**: Plug-and-play widget for any FilamentPHP admin panel.

## Requirements

- Laravel 8 or above
- FilamentPHP 3.x or above
- A Gemini API key from Google AI

## Installation

Install via Composer:

```bash
composer require zeeshantariq/filament-sqlgen
```

## Configuration

### 1. Publish the views (optional)

```bash
php artisan vendor:publish --provider="ZeeshanTariq\FilamentSqlGen\FilamentSqlGenServiceProvider"
```

### 2. Publish the configuration file

```bash
php artisan vendor:publish --provider="ZeeshanTariq\FilamentSqlGen\FilamentSqlGenServiceProvider" --tag="filament-sqlgen-config"
```

## Publishing Migrations

To publish the package's migrations to your application's `database/migrations` directory, run the following Artisan command:

```bash
php artisan vendor:publish --provider="ZeeshanTariq\FilamentSqlGen\FilamentSqlGenServiceProvider" --tag="filament-sqlgen-migrations"


This will create a `filament-sqlgen.php` file in your `config` directory. You can customize various settings, such as the default table style or query timeout, in this configuration file.

### 3. Add your AI settings to `.env`

🔹 **For Gemini**:
In your `.env` file, add the following:

```env
AI_PROVIDER=gemini

GEMINI_API_KEY=your-gemini-api-key
GEMINI_API_ENDPOINT=https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent
```

🔹 **For OpenAI**:
In your `.env` file, add the following:

```env
AI_PROVIDER=openai

OPENAI_API_KEY=your-openai-api-key
OPENAI_MODEL=gpt-3.5-turbo
OPENAI_API_ENDPOINT=https://api.openai.com/v1/chat/completions
```

### Configuration Notes:

- **AI_PROVIDER**: Choose between `gemini` and `openai`. This selects which AI service to use.

- **GEMINI_API_KEY / OPENAI_API_KEY**: Your respective API keys for Gemini and OpenAI.

- **GEMINI_API_ENDPOINT / OPENAI_API_ENDPOINT**: The API endpoint for Gemini or OpenAI.

- **OPENAI_MODEL**: The OpenAI model you want to use (e.g., `gpt-3.5-turbo`).

## Usage

Add the widget to your Filament dashboard or resource page:

```php
use ZeeshanTariq\FilamentSqlGen\Filament\Widgets\SqlGenWidget;

public static function getWidgets(): array
{
    return [
        SqlGenWidget::class,
    ];
}
```

Users can then type questions like:

> "How many users signed up today?"

The AI will respond by generating and executing a query like:

```sql
SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE();
```

The results are shown in a neat, scrollable table.

## Security

Only `SELECT` queries are allowed to prevent unwanted changes to your database. Write/update/delete operations are
blocked by design.

## Customization

To override the widget view:

1. Publish the views as shown above.
2. Modify the Blade view at:  
   `resources/views/vendor/filament-sqlgen/widgets/sql-gen-widget.blade.php`

You can style it using Tailwind or customize the logic as needed.

## History Log

Added history log functionality to track SQL generation events.

## Contributing

Contributions are welcome! Please fork the repo, make changes, and open a pull request.

## License

This package is open-source and licensed under the [MIT license](https://opensource.org/licenses/MIT).
