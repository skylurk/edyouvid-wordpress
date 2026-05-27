<?php
if(!defined('LINK_PREVIEW')) return;
$linkPreview = new linkPreview();

class linkPreview {

	/**
	 * Fetch data from LinkPreview API or locally
	 */
	public function get_url_data($url) {
		$linkpreview_settings = linkpreview_get_settings();
		$linkpreview_settings['linkpreview_max_url_size'] = ($linkpreview_settings['linkpreview_max_url_size'] == 0 || !$linkpreview_settings['linkpreview_max_url_size']) ? 128 : $linkpreview_settings['linkpreview_max_url_size'];
		if (strlen($url) > (int) $linkpreview_settings['linkpreview_max_url_size']) return false;
		if ((int)$linkpreview_settings['linkpreview_cache_time'] > 0) $json = get_transient('lp_'.md5($url));
		if (!$json) {
			if ($linkpreview_settings['linkpreview_local_curl'] == "on") $prepare = $this->get_local_data($url);
			else $prepare = $this->get_api_data($url);

			if (!$prepare) return false;
			$source_url = parse_url($url);
			$prepare->host_url = $source_url['host'];
			if ($linkpreview_settings['linkpreview_favicon'] == 'on') {
				$favicon_url = $source_url['scheme'].'://'.$source_url['host'].'/favicon.ico';
				$prepare->favicon = $favicon_url;
			}
			$json = json_encode($prepare);
			if ($linkpreview_settings['linkpreview_cache_time'] > 0 && !isset($prepare->error)) set_transient('lp_'.md5($url),$json,(int)$linkpreview_settings['linkpreview_cache_time']*60);
		}
		$object = json_decode($json);
		return $object;
	}

	/**
	 * Get remote data from API
	 */
	public function get_api_data($url) {
		$linkpreview_settings = linkpreview_get_settings();
		$query_url = 'http://api.linkpreview.net/?'.http_build_query(array('q'=>$url,'key'=>$linkpreview_settings['linkpreview_api_key'],'origin'=>'wplp'));
		if (function_exists('curl_version')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, $query_url);
			$json = curl_exec($ch);
			curl_close($ch);
		}
		else {
			$json = file_get_contents($query_url);
		}
		$data = json_decode($json);
		if ($data->title == '' && $data->description == '') return false;
		return $data;
	}

	/**
	 * Get remote data locally
	 */
	public function get_local_data($url) {
		$html = $this->fetch_local_url($url);
		$doc = new DOMDocument();
		@$doc->loadHTML($html);
		$nodes = $doc->getElementsByTagName('title');
		$data = new stdClass();
		$data->title = $nodes->item(0)->nodeValue;
		$metas = $doc->getElementsByTagName('meta');
		for ($i = 0; $i < $metas->length; $i++)
		{
			$meta = $metas->item($i);
			if ($meta->getAttribute('name') == 'description') $data->description = $meta->getAttribute('content');
			if ($meta->getAttribute('property') == 'og:image') $data->image = $meta->getAttribute('content');
		}
		return $data;
	}

	/**
	 * Retrieve data through curl or file_get_contents
	 */
	function fetch_local_url($url) {
		if (function_exists('curl_version')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, $url);
			$data = curl_exec($ch);
			curl_close($ch);
		}
		if (!isset($data) || $data == '') {
			$data = file_get_contents($url);
		}
		return $data;
	}

	/**
	 * Static view
	 */
	public function showtime($url) {
		ob_start();
		$object = $this->get_url_data($url);
		if ($object) {
			if (!is_rtl()) include(LINKPREVIEW_STATIC_VIEW);
			else include(LINKPREVIEW_STATIC_VIEW_RTL);
		}
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/**
	 * Filter out specific links
	 */
	public function is_file($url) {
		$linkpreview_settings = linkpreview_get_settings();
		$file_formats = explode(',', $linkpreview_settings['linkpreview_filter_out']);
		$path_info = pathinfo($url);
		if (isset($path_info['extension']) && in_array(strtolower($path_info['extension']), $file_formats)) {
			return true;
		}
		return false;
	}

	/**
	 * Filter out classes
	 */
	public function is_class($class) {
		$linkpreview_settings = linkpreview_get_settings();
		$classes = explode(" ", $class);
		$exclude_classes = explode(',', $linkpreview_settings['linkpreview_exclude_class']);
		foreach ($exclude_classes as $exclude_class) {
			if (in_array($exclude_class, $classes)) {
				return true;
			}
		}
		return false;
	}
}