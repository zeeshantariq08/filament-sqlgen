# filament-sqlgen

A FilamentPHP package that integrates a Gemini- or OpenAI-powered AI assistant into your Laravel admin panel — converting natural language into SQL and showing results in real-time.

## Features

* **Text-to-SQL AI**: Converts natural language questions into SQL using Gemini or OpenAI and executes them securely.
* **Read-only access**: Executes only `SELECT` queries for safety.
* **Interactive UI**: Results are rendered in a styled HTML table within the Filament widget.
* **Easy setup**: Plug-and-play widget for any FilamentPHP admin panel.
* **AI provider config**: Supports Gemini and OpenAI with flexible config options including temperature and token limits.
* **History logging**: Track all user queries and generated SQL with optional database logging.

## Requirements

* Laravel 8 or above
* FilamentPHP 3.x or above
* A Gemini or OpenAI API key

## Installation

Install via Composer:

```bash
composer require zeeshantariq/filament-sqlgen
```

## Configuration

### Publish Assets

You can publish configuration, views, migrations, or the schema separately:

🔧 **Publish Config**

```bash
php artisan vendor:publish --tag="filament-sqlgen-config"
```

🎨 **Publish Views**

```bash
php artisan vendor:publish --tag="filament-sqlgen-views"
```

📦 **Publish Migrations**

```bash
php artisan vendor:publish --tag="filament-sqlgen-migrations"
```

📊 **Publish Schema File**

```bash
php artisan vendor:publish --tag="filament-sqlgen-schema"
```

This will allow you to customize the package settings, views, schema, and database behavior individually.

### Add your AI settings to `.env`

🔹 **For Gemini**:

```env
AI_PROVIDER=gemini
GEMINI_API_KEY=your-gemini-api-key
GEMINI_API_ENDPOINT=https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent
GEMINI_TEMPERATURE=0.2
GEMINI_MAX_OUTPUT_TOKENS=1024
```

🔹 **For OpenAI**:

```env
AI_PROVIDER=openai
OPENAI_API_KEY=your-openai-api-key
OPENAI_MODEL=gpt-3.5-turbo
OPENAI_API_ENDPOINT=https://api.openai.com/v1/chat/completions
OPENAI_TEMPERATURE=0.2
OPENAI_MAX_TOKENS=1024
```

### Configuration Notes:

* **AI\_PROVIDER**: Choose between `gemini` and `openai`.
* **API Keys**: Add the respective keys for each provider.
* **Temperature and Max Tokens**: Tune the model's creativity and output length.

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

Only `SELECT` queries are allowed. All destructive operations like `UPDATE`, `DELETE`, or `DROP` are blocked.

## Customization

To override the widget view:

1. Publish the views as shown above.
2. Modify the Blade file at:
   `resources/views/vendor/filament-sqlgen/widgets/sql-gen-widget.blade.php`

You can style it with Tailwind or modify the layout/logic to fit your needs.

## History Log

Track and store all SQL generation activity using the built-in logging model (`SqlGenLog`).

## Contributing

Contributions are welcome! Please fork the repo, make changes, and open a pull request.

## License

This package is open-source and licensed under the [MIT license](https://opensource.org/licenses/MIT).
