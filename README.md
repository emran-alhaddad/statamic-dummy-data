````markdown
# Dummy Data Addon for Statamic

The Dummy Data addon for Statamic allows you to quickly inject dummy data into your collections and taxonomies. This is useful for testing and development purposes, enabling you to populate your site with sample content effortlessly.

## Features

- Inject dummy data into any collection or taxonomy.
- Specify the number of records or terms to inject.
- Automatically generates data based on the collection or taxonomy structure.

## Installation

To install the Dummy Data addon, use Composer:

```bash
composer require emran-alhaddad/statamic-dummy-data
```
````

## Configuration

No additional configuration is required. Once installed, the command is ready to use.

## Usage

To inject dummy data into your collections or taxonomies, use the following Artisan command:

```bash
php artisan statamic-dummy-data:inject
```

### Injecting Data into a Collection

1. Run the command:

   ```bash
   php artisan statamic-dummy-data:inject
   ```

2. Choose `Collection` when prompted.
3. Select the collection you want to inject data into.
4. Specify the number of records to inject.

### Injecting Data into a Taxonomy

1. Run the command:

   ```bash
   php artisan statamic-dummy-data:inject
   ```

2. Choose `Taxonomy` when prompted.
3. Select the taxonomy you want to inject data into.
4. Specify the number of terms to inject.

## Development

### Directory Structure

The main files of this addon are located in the `src` directory:

```
src/
├── Commands/
│   └── InjectDummyData.php
└── ServiceProvider.php
```

### Service Provider

The `ServiceProvider.php` registers the Artisan command:

```php
<?php

namespace Emran\DummyData;

use Illuminate\Support\ServiceProvider;
use Emran\DummyData\Commands\InjectDummyData;

class ServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            InjectDummyData::class,
        ]);
    }

    public function boot()
    {
        // Your boot logic here
    }
}
```

### Command

The `InjectDummyData.php` command is responsible for injecting the dummy data:

```php
<?php

namespace Emran\DummyData\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;

class InjectDummyData extends Command
{
    protected $signature = 'statamic-dummy-data:inject';
    protected $description = 'Interactively injects dummy data into collections or taxonomies';

    public function handle()
    {
        $type = $this->choice('What do you want to inject data into?', ['Collection', 'Taxonomy'], 0);
        if ($type === 'Collection') {
            $this->injectIntoCollection();
        } else {
            $this->injectIntoTaxonomy();
        }
    }

    protected function injectIntoCollection()
    {
        $collections = DB::table('collections')->where('handle', '!=', 'pages')->pluck('title', 'handle')->toArray();
        if (empty($collections)) {
            $this->error('No collections found.');
            return;
        }
        $collectionHandle = $this->choice('Select a collection', array_keys($collections), 0);
        $collectionTitle = $collections[$collectionHandle];
        $count = $this->ask('How many records do you want to inject?', 10);
        $structure = $this->getCollectionStructure($collectionHandle);
        $this->injectData($collectionHandle, $collectionTitle, $count, 'collection', $structure);
    }

    protected function injectIntoTaxonomy()
    {
        $taxonomies = DB::table('taxonomies')->pluck('title', 'handle')->toArray();
        if (empty($taxonomies)) {
            $this->error('No taxonomies found.');
            return;
        }
        $taxonomyHandle = $this->choice('Select a taxonomy', array_keys($taxonomies), 0);
        $taxonomyTitle = $taxonomies[$taxonomyHandle];
        $count = $this->ask('How many terms do you want to inject?', 10);
        $structure = $this->getTaxonomyStructure($taxonomyHandle);
        $this->injectData($taxonomyHandle, $taxonomyTitle, $count, 'taxonomy', $structure);
    }

    protected function injectData($handle, $title, $count, $type, $structure)
    {
        if ($type == 'collection') {
            $collection = Collection::findByHandle($handle);
            for ($i = 0; $i < $count; $i++) {
                $data = $this->generateDummyData($structure);
                $entry = Entry::make()
                    ->collection($collection)
                    ->slug('dummy-entry-' . $i)
                    ->data($data);
                $entry->save();
            }
            $this->info("$count dummy entries injected into the collection: $title");
        } else {
            $taxonomy = Taxonomy::findByHandle($handle);
            for ($i = 0; $i < $count; $i++) {
                $data = $this->generateDummyData($structure);
                $term = Term::make()
                    ->taxonomy($taxonomy)
                    ->slug('dummy-term-' . $i)
                    ->data($data);
                $term->save();
            }
            $this->info("$count dummy terms injected into the taxonomy: $title");
        }
    }

    protected function getCollectionStructure($handle)
    {
        $collection = DB::table('collections')->where('handle', $handle)->first();
        $settings = json_decode($collection->settings, true);
        $blueprintHandle = $settings['blueprint'] ?? null;
        if (!$blueprintHandle) {
            $this->warn("No blueprint defined for collection: $handle. Falling back to default fields.");
            return $this->getDefaultFields();
        }
        return $this->getBlueprintFields($blueprintHandle);
    }

    protected function getTaxonomyStructure($handle)
    {
        $taxonomy = DB::table('taxonomies')->where('handle', $handle)->first();
        $settings = json_decode($taxonomy->settings, true);
        $blueprintHandle = $settings['blueprint'] ?? null;
        if (!$blueprintHandle) {
            $this->warn("No blueprint defined for taxonomy: $handle. Falling back to default fields.");
            return $this->getDefaultFields();
        }
        return $this->getBlueprintFields($blueprintHandle);
    }

    protected function getBlueprintFields($blueprintHandle)
    {
        $blueprint = DB::table('blueprints')->where('handle', $blueprintHandle)->first();
        if (!$blueprint) {
            return [];
        }
        $fields = [];
        $tabs = json_decode($blueprint->tabs, true);
        foreach ($tabs as $tab) {
            foreach ($tab['sections'] as $section) {
                foreach ($section['fields'] as $field) {
                    $fields[] = $field['field'];
                }
            }
        }
        return $fields;
    }

    protected function getDefaultFields()
    {
        return [
            ['handle' => 'title', 'type' => 'text'],
            ['handle' => 'description', 'type' => 'textarea'],
            ['handle' => 'date', 'type' => 'date'],
            ['handle' => 'image', 'type' => 'image']
        ];
    }

    protected function generateDummyData($structure)
    {
        $data = [];
        foreach ($structure as $field) {
            $data[$field['handle']] = $this->generateFieldValue($field);
        }
        return $data;
    }

    protected function generateFieldValue($field)
    {
        $type = $field['type'] ?? $field['field']['type'];
        $handle = $field['handle'];

        switch ($type) {
            case 'text':
            case 'textarea':
                return 'Dummy text content for ' . $handle;
            case 'number':
                return rand(1, 100);
            case 'boolean':
                return rand(0, 1) == 1;
            case 'date':
                return now()->subDays(rand(1, 365))->toDateString();
            case 'image':
                return 'path/to/dummy-image.jpg';
            case 'markdown':
                return '# Dummy Markdown Content';
            case 'select':
                return 'option_' . rand(1, 5);
            case 'checkboxes':
                return ['option_' . rand(1, 5)];
            case 'grid':
                return [['field' => 'Dummy Grid Content']];
            case 'relationship':
                return 'related_entry_id';
            case 'user':
                return 'dummy_user_id';
            case 'entries':
                return 'dummy_entry_id';
            case 'assets':
                return 'dummy_asset_id';
            case 'tags':
                return ['tag1', 'tag2', 'tag3'];
            case 'table':
                return [['column1' => 'value1', 'column2' => 'value2']];
            case 'array':
                return ['item1', 'item2', 'item3'];
            case 'fieldset':
                return $this->generateDummyData($field['fields']);
            case 'paragraph':
                return [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Dummy paragraph content']]]];
            case 'list':
                return ['Item 1', 'Item 2', 'Item 3'];
            case

 'link':
                return ['title' => 'Dummy Link Title', 'url' => 'https://example.com'];
            case 'button':
                return ['text' => 'Dummy Button Text', 'link' => 'https://example.com'];
            default:
                return 'Unknown field type ' . $handle;
        }
    }
}
```

## Contributions

Contributions are welcome. Please create pull requests or issues on our [GitHub repository](https://github.com/emran-alhaddad-alhaddad/statamic-dummy-data).

## License

This addon is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

```

```
