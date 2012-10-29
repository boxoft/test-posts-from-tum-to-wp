<?php

require 'includes/functions.php';
require 'includes/config.php';

global $api_key, $wp_url, $wp_user, $wp_pass;

$tumblr_ids = array(
	'boxoft'
);

foreach ($tumblr_ids as $tumblr_id)
{
	$posts = get_posts_from_tum($tumblr_id, $api_key);
	if ($posts)
	{
		add_posts_to_wp($wp_url, $wp_user, $wp_pass, $posts);
	}
}

echo "\nDone!\n";

?>