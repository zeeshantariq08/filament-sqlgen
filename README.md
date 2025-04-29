
# filament-ai-agent

A FilamentPHP package for integrating a Gemini-powered AI chat agent into Laravel admin panels.

## Features

- **Gemini-powered AI chat agent**: Allows your admin panel users to interact with an AI chatbot that answers queries based on your database.
- **Real-time SQL generation**: The AI can generate SQL queries based on user questions and execute them dynamically.
- **Customizable and easy integration**: Integrates seamlessly with FilamentPHP, allowing you to add AI functionality to your Laravel admin panel with minimal effort.

## Requirements

- Laravel 8 or above
- FilamentPHP 2.x or above
- Gemini API access (for generating SQL queries based on user input)

## Installation

You can install this package via Composer:

```bash
composer require zeeshantariq/filament-ai-agent
```

## Configuration

1. **Publish the configuration**:
   Run the following command to publish the package’s config file:

   ```bash
   php artisan vendor:publish --provider="ZeeshanTariq\FilamentAiAgent\FilamentAiAgentServiceProvider"
   ```

2. **Set up Gemini API Key**:
   Create a `.env` file in your project root (if you don’t already have one) and add your Gemini API key like this:

   ```env
   GEMINI_API_KEY=your-gemini-api-key-here
   ```

3. **Add the Widget**:
   In your Filament admin panel, you can now add the AI chat widget to any page by using the following code:

   ```php
   use ZeeshanTariq\FilamentAiAgent\Filament\Widgets\AIChatBotWidget;

   // In your Filament page or resource
   public static function getWidgets(): array
   {
       return [
           AIChatBotWidget::class,
       ];
   }
   ```

## Usage

Once you’ve added the widget to your Filament admin panel, users can ask questions, and the AI will generate SQL queries to answer the query. The results are displayed in a dynamic HTML table.

### Example:

**User asks**: "How many products are in stock today?"

The AI will generate the SQL query:

```sql
SELECT COUNT(*) FROM products WHERE DATE(created_at) = CURDATE();
```

Then, it will execute this query and display the result in a table format in the admin panel.

## Customizing the Widget

You can customize the appearance and behavior of the chat widget by modifying the views in the package. The main view is located at `resources/views/vendor/filament-ai-agent/widgets/a-i-chat-bot-widget.blade.php`.

Feel free to adjust the design, layout, and content as needed.

## Contributing

We welcome contributions! If you find any bugs or want to add features, please fork the repository and submit a pull request. Be sure to follow the contribution guidelines provided.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
