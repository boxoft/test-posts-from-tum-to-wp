<?php

require 'IXR_Library.php';

class T2WP
{
	private $_api_key;
	private $_wp_url;
	private $_wp_user;
	private $_wp_pass;
	private $_debug_mode;

	public function __construct($api_key, $wp_url, $wp_user, $wp_pass, $debug_mode=FALSE)
	{
		$this->_api_key    = $api_key;
		$this->_wp_url     = rtrim($wp_url, '/') . '/';
		$this->_wp_user    = $wp_user;
		$this->_wp_pass    = $wp_pass;
		$this->_debug_mode = $debug_mode;
	}

	protected function get($url)
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

	protected function add($content)
	{
		$wp_xmlrpc = $this->_wp_url . 'xmlrpc.php';

		$client = new IXR_Client($wp_xmlrpc);

		if (!$client->query('metaWeblog.newPost', '', $this->_wp_user, $this->_wp_pass, $content, TRUE))
			die('metaWeblog.newPost ' . $client->getErrorCode() . ' : ' . $client->getErrorMessage());

		$post_id = $client->getResponse();

		if ($post_id && $this->_debug_mode)
			echo 'Post ID: #' . $post_id . "\n";

		sleep(1);

		return $post_id;
	}

	protected function addCommonFields($tumblr_post, &$content)
	{
		$content['description'] .= '<dl>';
		$content['description'] .= '<dt>Tumblr Site Name</dt>';
		$content['description'] .= '<dd>' . $tumblr_post->blog_name . '</dd>';
		$content['description'] .= '<dt>Tumblr Site URL</dt>';
		$content['description'] .= '<dd><a href="' . $tumblr_post->post_url . '" target="_blank">' . $tumblr_post->post_url . '</a></dd>';
		$content['description'] .= '<dt>Tumblr Post Date</dt>';
		$content['description'] .= '<dd>' . $tumblr_post->date . '</dd>';
		$content['description'] .= '</dl>';

		$content['mt_keywords'] = $tumblr_post->tags;
	}

	protected function addText($tumblr_post)
	{
		if (!empty($tumblr_post->title))
			$content['title'] = $tumblr_post->title;
		else
			$content['title'] = ucwords(str_replace('-', ' ', $tumblr_post->slug));

		$content['description'] = $tumblr_post->body;

		$this->addCommonFields($tumblr_post, $content);

		$this->add($content);
	}

	protected function addPhoto($tumblr_post)
	{
		$content['title'] = 'Photo ' . $tumblr_post->id;

		$photos = $tumblr_post->photos;
		foreach ($photos as $photo)
		{
			$content['description'] .= '<img src="' . $photo->original_size->url . '" />';
			$content['description'] .= $photo->caption;
		}
		$content['description'] .= $tumblr_post->caption;

		$this->addCommonFields($tumblr_post, $content);

		$this->add($content);
	}

	protected function addQuote($tumblr_post)
	{
		$content['title'] = 'Quote ' . $tumblr_post->id;

		$content['description'] = '<blackquote>' . $tumblr_post->text . '</blackquote>';
		$content['description'] .= '<p>' . $tumblr_post->source . '</p>';

		$this->addCommonFields($tumblr_post, $content);

		$this->add($content);
	}

	protected function addLink($tumblr_post)
	{
		$content['title'] = 'Link ' . $tumblr_post->id;

		$content['description'] = '<a href="' . $tumblr_post->url . '" target="_blank">' . $tumblr_post->title . '</a>';
		$content['description'] .= $tumblr_post->description;

		$this->addCommonFields($tumblr_post, $content);

		$this->add($content);
	}

	protected function addChat($tumblr_post)
	{
		$content['title'] = 'Chat ' . $tumblr_post->id;

		$content['description'] = '<h3>' . $tumblr_post->title . '</h3>';
		$content['description'] .= $tumblr_post->body;

		$content['description'] .= '<ul>';
		foreach ($tumblr_post->dialogue as $d)
		{
			$content['description'] .= '<li><strong>' . $d->label . '</strong> ' . $d->phrase . '</li>';
		}
		$content['description'] .= '</ul>';

		$this->addCommonFields($tumblr_post, $content);

		$this->add($content);
	}

	protected function addAudio($tumblr_post)
	{
		$content['title'] = 'Audio ' . $tumblr_post->id;

		$content['description'] .= '<dl>';
		$content['description'] .= '<dt>Artist</dt>';
		$content['description'] .= '<dd>' . $tumblr_post->artist . '</dd>';
		$content['description'] .= '<dt>Album</dt>';
		$content['description'] .= '<dd>' . $tumblr_post->album . '</dd>';
		$content['description'] .= '<dt>Year</dt>';
		$content['description'] .= '<dd>' . $tumblr_post->year . '</dd>';
		$content['description'] .= '<dt>Track</dt>';
		$content['description'] .= '<dd>' . $tumblr_post->track . '</dd>';
		$content['description'] .= '<dt>Track Name</dt>';
		$content['description'] .= '<dd>' . $tumblr_post->track_name . '</dd>';
		$content['description'] .= '<dt>Caption</dt>';
		$content['description'] .= '<dd>' . $tumblr_post->caption . '</dd>';
		$content['description'] .= '<dt>Player</dt>';
		$content['description'] .= '<dd>' . $tumblr_post->player . '</dd>';
		$content['description'] .= '<dt>Audio URL</dt>';
		$content['description'] .= '<dd>' . $tumblr_post->audio_url . '</dd>';
		$content['description'] .= '</dl>';

		$this->addCommonFields($tumblr_post, $content);

		$this->add($content);
	}

	protected function addVideo($tumblr_post)
	{
		$content['title'] = 'Video ' . $tumblr_post->id;

		$player = $tumblr_post->player;
		$content['description'] = $player[count($player) - 1]->embed_code;
		$content['description'] .= $tumblr_post->permalink_url;
		$content['description'] .= $tumblr_post->caption;

		$this->addCommonFields($tumblr_post, $content);

		$this->add($content);
	}

	protected function addAnswer($tumblr_post)
	{
		$content['title'] = ucwords(str_replace('-', ' ', $tumblr_post->slug));

		$content['description'] = '<h3><a href="' . $tumblr_post->asking_url . '" target="_blank">' . $tumblr_post->asking_name . '</a> asked: ';
		$content['description'] .= $tumblr_post->question . '</h3>';
		$content['description'] .= $tumblr_post->answer;

		$this->addCommonFields($tumblr_post, $content);

		$this->add($content);
	}

	public function getPostsFromTumblr($id, $limit=20)
	{
		if ($this->_debug_mode)
			echo "Tumblr ID: " . $id . "\n";

		// Please refer to http://www.tumblr.com/docs/en/api/v2#posts for more details.

		$api_url = 'http://api.tumblr.com/v2/blog/' . $id . '.tumblr.com/posts?api_key=' . $this->_api_key . '&limit=' . $limit;
		$result = json_decode($this->get($api_url));

		if (!empty($result) && !empty($result->meta) && !empty($result->meta->status) && $result->meta->status === 200)
			return $result->response->posts;

		return FALSE;
	}

	public function addPostsToWordPress($tumblr_posts)
	{
		if ($tumblr_posts)
		{
			foreach ($tumblr_posts as $tumblr_post)
			{
				$add = 'add' . ucfirst($tumblr_post->type);
				$this->$add($tumblr_post);
			}
		}
	}

}
