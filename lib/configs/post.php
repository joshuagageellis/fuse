<?php
/**
 * Post Post Type Config.
 * Defines the api connection and field mapping for the post post type.
 *
 * @package fuse
 */

$fuse_post_config = new Fuse_Config(
	'post',
	'events',
	'https://mockend.com/joshuagageellis/fuse/',
);

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

$fuse_post_config->define_field_map(
	'post_title',
	array(
		'meta_type'     => 'insert',
		'data_type'     => 'string',
		'api_field_key' => 'title',
		'manual'        => false,
		'manual_value'  => '',
	)
);

$fuse_post_config->define_field_map(
	'post_content',
	array(
		'meta_type'     => 'insert',
		'data_type'     => 'string',
		'api_field_key' => 'title',
		'manual'        => false,
		'manual_value'  => '',
	)
);

$fuse_post_config->define_field_map(
	'post_status',
	array(
		'meta_type'     => 'insert',
		'data_type'     => 'bool',
		'manual'        => true,
		'manual_value'  => 'publish',
	)
);

$fuse_post = new Fuse( $fuse_post_config );
$fuse_post->register();
