<?php

require 'IXR_Library.php';

function http_get($url)
{
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_NOBODY, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

	$result = curl_exec($ch);
	curl_close($ch);

	return $result;
}

// http://www.tumblr.com/docs/en/api/v2#posts
function get_posts_from_tum($tumblr_id, $api_key)
{
	echo "\nTumblr ID: " . $tumblr_id . "\n";

	$api_url = 'http://api.tumblr.com/v2/blog/' . $tumblr_id . '.tumblr.com/posts?api_key=' . $api_key;
	$result = json_decode(http_get($api_url));

	if (isset($result) && isset($result->meta) && isset($result->meta->status) && $result->meta->status === 200)
	{
		return $result->response->posts;
	}

	return FALSE;
}

function add_posts_to_wp($wp_url, $wp_user, $wp_pass, $tumblr_posts)
{
	$wp_url = rtrim($wp_url, '/') . '/';
	$wp_xmlrpc = $wp_url . 'xmlrpc.php';

	if ($tumblr_posts)
	{
		foreach ($tumblr_posts as $tumblr_post)
		{
			switch ($tumblr_post->type)
			{
				case 'video':
					$title = substr(strip_tags($tumblr_post->caption), 0, 30);
					$description = $tumblr_post->caption;
					$post_format = 'video';
					$custom_fields = array(
						array(
							'key' => 'post_formats',
							'value' => array('post_format' => $post_format, 'video_external' => $tumblr_post->permalink_url)
						)
					);
					break;

				case 'photo':
					$title = substr(strip_tags($tumblr_post->caption), 0, 30);
					$description = $tumblr_post->caption;
					$post_format = 'image';
					$custom_fields = array(
						array(
							'key' => 'post_formats',
							'value' => array('post_format' => $post_format, 'image_upload_file' => $tumblr_post->photos[0]->original_size->url)
						)
					);
					break;

				case 'link':
					$title = $tumblr_post->title;
					$description = $tumblr_post->description;
					$post_format = 'link';
					$custom_fields = array(
						array(
							'key' => 'post_formats',
							'value' => array('post_format' => $post_format, 'link_label_1' => $title, 'link_url_1' => $tumblr_post->url)
						)
					);
					break;

				default:
					$title = $tumblr_post->title;
					$description = $tumblr_post->body;
			}

			if (empty ($title))
				$title = $tumblr_post->blog_name . ' ' . $tumblr_post->id;

			$client = new IXR_Client($wp_xmlrpc);
			$content['title'] = $title;
			$content['description'] = $description;
			$content['description'] .= '<p><a href="' . $tumblr_post->post_url . '">' . $tumblr_post->post_url . '</a></p>';
			$content['mt_keywords'] = $tumblr_post->tags;
			if ($post_format) $content['wp_post_format'] = $post_format;
			$content['custom_fields'] = $custom_fields;

			if (!$client->query('metaWeblog.newPost', '', $wp_user, $wp_pass, $content, true))
			{
				die('metaWeblog.newPost ' . $client->getErrorCode() . " : " . $client->getErrorMessage());
			}

			$post_id = $client->getResponse();

			if ($post_id)
			{
				echo 'Post ID: #' . $post_id . "\n";
			}

			sleep(1);
		} // foreach ($tumblr_posts as $tumblr_post)
	} // if ($tumblr_posts)
}
