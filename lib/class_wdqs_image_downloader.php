<?php

class Wdqs_ImageDownloader {

	private $_data;

	private function __construct () {
		$this->_data = new Wdqs_Options;
	}

	public static function serve () {
		$me = new Wdqs_ImageDownloader;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		if ($this->_data->get('download_images-images')) {
			add_filter('wdqs-image-img_src', array($this, 'download_image'));
		}
		if ($this->_data->get('download_images-links')) {
			add_filter('wdqs-link-img_src', array($this, 'download_image'));
		}
	}

	public function download_image ($src) {
		if ($this->is_local_image($src)) return $src; // Local image

		// Download the image and write to temp file
		$image = wp_remote_get($src, array(
			'sslverify' => false,
		));
		if (200 != wp_remote_retrieve_response_code($image)) return $src; // Request error
		$img = wp_remote_retrieve_body($image);
		$tmp_img_file = tempnam(sys_get_temp_dir(), 'wdqs-image');
		file_put_contents($tmp_img_file, $img);

		// Validate the image
		$data = getimagesize($tmp_img_file);
		if (!$data || empty($data[2])) {
			@unlink($tmp_img_file);
			return $src;
		}
		if (!in_array($data[2], $this->get_supported_image_types())) {
			@unlink($tmp_img_file);
			return $src;
		}

		// To be extra safe, open it and write the resource
		$filename = pathinfo($src, PATHINFO_FILENAME);
		$extension = $create_proc = $write_proc = false;
		switch ($data[2]) {
			case IMAGETYPE_GIF:
				$extension = 'gif';
				$create_proc = 'imagecreatefromgif';
				$write_proc = 'imagegif';
				break;
			case IMAGETYPE_PNG:
				$extension = 'png';
				$create_proc = 'imagecreatefromgpng';
				$write_proc = 'imagepng';
				break;
			case IMAGETYPE_JPEG:
			case IMAGETYPE_JPEG2000:
				$extension = 'jpg';
				$create_proc = 'imagecreatefromjpeg';
				$write_proc = 'imagejpeg';
				break;

		}
		if (!$extension || !$create_proc || !$write_proc) {
			@unlink($tmp_img_file);
			return $src;
		}

		$image_info = $this->get_media_library_path($filename, $extension, false);
		if (!$image_info) {
			@unlink($tmp_img_file);
			return $src;
		}

		$resource = $create_proc($tmp_img_file);
		if (!$write_proc($resource, $image_info['path'])) {
			@unlink($tmp_img_file);
			return $src;
		}

		@unlink($tmp_img_file);

		return $image_info['url'];
	}

	public function is_local_image ($src) {
		return preg_match('/^' . preg_quote(site_url(), '/') . '/i', $src);
	}

	public function get_supported_image_types () {
		$ret = array();
		if (function_exists('imagecreatefromgif')) $ret[] = IMAGETYPE_GIF;
		if (function_exists('imagecreatefrompng')) $ret[] = IMAGETYPE_PNG;
		if (function_exists('imagecreatefromjpeg')) {
			$ret[] = IMAGETYPE_JPEG;
			$ret[] = IMAGETYPE_JPEG2000;
		}
		return $ret;
	}

	public function get_media_library_path ($filename, $extension) {
		$uploads = wp_upload_dir();
		if (empty($uploads) || !empty($uploads['error'])) return false;
		$path = trailingslashit($uploads['path']);
		$url = trailingslashit($uploads['url']);

		$fullpath = "{$path}{$filename}.{$extension}";
		while (file_exists($fullpath)) {
			$filename .= rand(0,9);
			$fullpath = "{$path}{$filename}.{$extension}";
		}
		return array(
			'path' => $fullpath,
			'url' => "{$url}{$filename}.{$extension}",
		);
	}

}
Wdqs_ImageDownloader::serve();