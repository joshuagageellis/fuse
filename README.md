# Fuse Plugin
This plugin is a test plugin for syncing content from an arbitrary endpoint to a WordPress post type. It is not intended for *immediate* production use — please use at your own risk.

## Setting up Post Type Syncing
Configs for each post type should be placed in the './lib/configs' directory, though once the plugin is activated the classes will be available for use in the theme. A general post type config looks like so:

```php

// Define the config object.
$fuse_post_config = new Fuse_Config(
	'post',
	'events',
	'https://mockend.com/joshuagageellis/fuse/', // Sample API endpoint.
);

// Define field maps.
$fuse_post_config->define_field_map(
	'ID',
	array(
		'meta_type'     => 'insert',
		'data_type'     => 'number',
		'api_field_key' => 'id',
		'manual'        => false,
		'manual_value'  => '',
	)
);

// Supports nested json parsing with dot notation.
$fuse_post_config->define_field_map(
	'post_title',
	array(
		'meta_type'     => 'insert',
		'data_type'     => 'string',
		'api_field_key' => 'title.rendered', // Example of nested json.
		'manual'        => false,
		'manual_value'  => '',
	)
);

// ... and so on.

// Register the config with the Fuse class.
$fuse_post = new Fuse( $fuse_post_config );

// Register the cron hooks.
$fuse_post->register();
```

## Cron Schedules
The plugin uses WP cron to schedule update events. The process is separated into two schedules, `parent` and `child`. The `parent` schedule fetches the API and registers an options value with a task pointer. The `child` schedule runs more frequently, iterating through the data based on batch total and task pointer. The `child` schedule is also responsible for updating the task pointer and clearing the options value when the task is complete.