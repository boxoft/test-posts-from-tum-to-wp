<?php

require 'includes/T2WP.php';
require 'includes/config.php';

global $api_key, $wp_url, $wp_user, $wp_pass, $tumblr_ids;

$wp = new T2WP($api_key, $wp_url, $wp_user, $wp_pass, $debug_mode=TRUE);

echo "\nBegin\n\n";

foreach ($tumblr_ids as $tumblr_id)
{
	$posts = $wp->getPostsFromTumblr($tumblr_id, $limit=5);
	print_r($posts);
	$wp->addPostsToWordPress($posts);
}

echo "\nEnd\n";

?>