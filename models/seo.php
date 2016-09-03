<?php

if (!defined('IN_ANWSION'))
{
	die;
}

class seo_class extends AWS_MODEL
{
	public function baidu_push($urls, $site, $token, $type = 'original')
	{
		$api = 'http://data.zz.baidu.com/urls?site=' . $site . '&token=' . $token;
		if($type)
		{
			$api .= ('&type=' . $type);
		}

		$ch = curl_init();
		$options =  array(
		    CURLOPT_URL => $api,
		    CURLOPT_POST => true,
		    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_POSTFIELDS => implode("\n", $urls),
		    CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
		);
		curl_setopt_array($ch, $options);
		return curl_exec($ch);
	}

	public function baidu_push_all($rel_url, $type = 'original')
	{
		$seo_baseurl = get_setting('seo_base_url');
		$seo_baseurl_m = get_setting('seo_base_url_m');
		$baidu_push_site = get_setting('baidu_push_site');
		$baidu_push_site_m = get_setting('baidu_push_site_m');
		$baidu_push_token = get_setting('baidu_push_token');
		
		$push_urls = array($seo_baseurl . $rel_url);
		$result = $this->baidu_push($push_urls, $baidu_push_site, $baidu_push_token, $type);
		error_log('[baidu push] url: ' . $seo_baseurl . $rel_url . ', status: ' . $result);

		$push_urls = array($seo_baseurl_m . $rel_url);
		$result = $this->baidu_push($push_urls, $baidu_push_site_m, $baidu_push_token, $type);
		error_log('[baidu push mobile] url: ' . $seo_baseurl_m . $rel_url . ', status: ' . $result);
	}
}
