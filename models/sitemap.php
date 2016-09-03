<?php

if (!defined('IN_ANWSION'))
{
	die;
}

class sitemap_class extends AWS_MODEL
{
	const SITEMAP_SUCCESS = 1;
	const SITEMAP_DIR_NOT_EXIST	= -1;
	const SITEMAP_DIR_NOT_WRITABLE	= -2;
	const SITEMAP_DIR_NOT_EXIST_M	= -3;
	const SITEMAP_DIR_NOT_WRITABLE_M	= -4;
	const SITEMAP_BASE_URL_NULL	= -5;
	const SITEMAP_BASE_URL_NULL_M = -6;

	public function get_question_entries($datetime = null)
	{
		$entries = array();
		if($question_entries = $this->model('question')->get_question_entries_by_datetime($datetime))
		{
			foreach ($question_entries as $key => $value) 
			{
				$entries[$key]['permalink'] = 'question/' . $value['question_id'];
				$entries[$key]['updated'] = date_friendly($value['update_time'], 604800, 'Y-m-d');
			}
		}

		return $entries;
	}

	public function get_article_entries($datetime = null)
	{
		$entries = array();
		if($article_entries = $this->model('article')->get_article_entries_by_datetime($datetime))
		{
			foreach ($article_entries as $key => $value) 
			{
				$entries[$key]['permalink'] = 'article/' . $value['id'];
				$entries[$key]['updated'] = date_friendly($value['add_time'], 604800, 'Y-m-d');
			}
		}

		return $entries;
	}

	public function get_topic_entries($datetime = null)
	{
		$entries = array();
		if($topic_entries = $this->model('topic')->get_topic_entries_by_datetime($datetime))
		{
			foreach ($topic_entries as $key => $value) 
			{
				$entries[$key]['permalink'] = 'topic/' . $value['topic_id'];
				$entries[$key]['updated'] = date_friendly($value['add_time'], 604800, 'Y-m-d');
			}
		}

		return $entries;
	}

	public function get_user_entries($datetime = null)
	{
		$entries = array();
		if($user_entries = $this->model('account')->get_user_entries_by_datetime($datetime))
		{
			foreach ($user_entries as $key => $value) 
			{
				$entries[$key]['permalink'] = 'people/' . $value['uid'];
				$entries[$key]['updated'] = date('Y-m-d', $value['last_active']);
			}
		}

		return $entries;
	}

	public function get_site_entries($datetime = null)
	{
		$entries = array();
		$question_entries = $this->get_question_entries($datetime);
		if(is_array($question_entries))
		{
			$entries = array_merge($entries, $question_entries);
		}

		$article_entries = $this->get_article_entries($datetime);
		if(is_array($article_entries))
		{
			$entries = array_merge($entries, $article_entries);
		}

		$topic_entries = $this->get_topic_entries($datetime);
		if(is_array($topic_entries))
		{
			$entries = array_merge($entries, $topic_entries);
		}

		$user_entries = $this->get_user_entries($datetime);
		if(is_array($user_entries))
		{
			$entries = array_merge($entries, $user_entries);
		}

		return $entries;
	}

	public function generate_sitemap($filename, $baseurl, $entries, $ismobile = false)
	{
		$xml = new DomDocument('1.0', 'utf-8'); 
		$xml->formatOutput = true; 

		// creating base node
		$urlset = $xml->createElement('urlset'); 
		$urlset -> appendChild(
		    new DomAttr('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9')
		);

		// appending it to document
		$xml -> appendChild($urlset);

		// building the xml document with your website content
		foreach($entries as $entry)
		{
		    //Creating single url node
		    $url = $xml->createElement('url'); 

		    //Filling node with entry info
		    
		    if($ismobile)
		    {
		    	$url -> appendChild( $xml->createElement('mobile:mobile'));
		    }
		    $url -> appendChild( $xml->createElement('loc', $baseurl . $entry['permalink']) ); 
		    $url -> appendChild( $lastmod = $xml->createElement('lastmod', $entry['updated']) ); 
		    $url -> appendChild( $changefreq = $xml->createElement('changefreq', 'always')); 
		    $url -> appendChild( $priority = $xml->createElement('priority', '0.8') ); 

		    // append url to urlset node
		    $urlset -> appendChild($url);
		}

		$xml->save($filename);
		gzip_file($filename);
		unlink($filename);

		$this->model('setting')->set_vars(array('sitemap_update_time' => time()));
	}

	public function generate_sitemap_all($reset = false)
	{
		$sitemap_dir = get_setting('sitemap_dir');
        $sitemap_dir_m = get_setting('sitemap_dir_m');
        $sitemap_basename = get_setting('sitemap_basename');
        $sitemap_basename_m = get_setting('sitemap_basename_m');
        $update_time = get_setting('sitemap_update_time');

        if($reset)
        {
        	$update_time = null;
        }

        if(!file_exists($sitemap_dir))
        {
        	return self::SITEMAP_DIR_NOT_EXIST;
        }
        $sitemap_filename = $sitemap_dir . '/sitemap.xml';

        if(!is_writable($sitemap_dir))
        {
            return self::SITEMAP_DIR_NOT_WRITABLE;
        }

        if(!file_exists($sitemap_dir_m))
        {
            return self::SITEMAP_DIR_NOT_EXIST_M;
        }
        $sitemap_filename_m = $sitemap_dir_m . '/sitemap.xml';

        if(!is_writable($sitemap_dir_m))
        {
            return self::SITEMAP_DIR_NOT_WRITABLE_M;
        }

        if(!strlen(trim($sitemap_basename)))
        {
            return self::SITEMAP_BASE_URL_NULL;
        }

        if(!strlen(trim($sitemap_basename_m)))
        {
            return self::SITEMAP_BASE_URL_NULL_M;
        }

        $entries = $this->model('sitemap')->get_site_entries($update_time);
        $this->model('sitemap')->generate_sitemap($sitemap_filename, $sitemap_basename, $entries);
        $this->model('sitemap')->generate_sitemap($sitemap_filename_m, $sitemap_basename_m, $entries, true);

        return self::SITEMAP_SUCCESS;
	}
}
