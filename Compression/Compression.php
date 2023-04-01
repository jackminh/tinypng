<?php
namespace Compression;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Compression {

	/**
	 * 日志文件
	 * @var string
	 */
	private $log_file = "compression.log";
	/**
	 * 日志器
	 * @var null
	 */
	private $loger = null;
	/**
	 * 日志名
	 * @var string
	 */
	private $log_name = "compression";
	/**
	 * 图片类型
	 * @var [type]
	 */
	private $limit_pic_extensions = [
		'webp', 'jpeg', 'png',
	];

	private $limit_pic_types = [
		'image/png', '', 'image/jpeg',
	];

	/**
	 * 错误信息
	 * @var string
	 */
	private $error = "";

	/**
	 *  压缩了多少张图片
	 * @var integer
	 */
	private $compression_count = 0;

	/**
	 * @param [$app_key] tinypng上的app_key
	 * @param [type] 日志文件名
	 */
	public function __construct($app_key, $log_file = null) {
		try {
			\Tinify\setKey($app_key);
			$this->loger = $this->getLoger($log_file ? $log_file : $this->log_file);
		} catch (\Exception $e) {
			die($e->getMessage);
		}
	}

	/**
	 * @param  [type] 源图片
	 * @param  [type] 生成的目标图片
	 * @return [type] 处理结果
	 */
	public function single_pic_handler($source_pic_name, $resize = false, $weight = 150, $height = 100) {
		if (!$this->checkPic($source_pic_name)) {
			return false;
		}
		$dist_pic_name = $this->createDistPic($source_pic_name);
		return $this->handler($source_pic_name, $dist_pic_name, $resize, $weight, $height);
	}
	/**
	 * 多图片处理
	 * @param  [$directory] 图片目录
	 * @return [type]
	 */
	public function multi_pic_handler($directory, $resize = false, $weight = 150, $height = 100) {
		if (!is_dir($directory)) {
			$this->error = "多图片处理，传入图片所在目录";
			return false;
		}
		$pics = $this->findAllPics($directory);
		$result = [];
		if (!empty($pics) && is_array($pics)) {
			foreach ($pics as $key => $pic) {
				$dist_pic_name = $this->createDistPic($pic);
				$result[] = $this->handler($pic, $dist_pic_name, $resize, $weight, $height);
			}
		}
		return $result;
	}

	/**
	 * 获取压缩次数
	 * @return [type]
	 */
	public function getCount() {
		$this->compression_count = \Tinify\compressionCount();
		return $this->compression_count;
	}
	/**
	 * 返回错误
	 * @return [type]
	 */
	public function getError() {
		return $this->error;
	}
	/**
	 * 创建压缩后的图片所在目录并返回上目标图片
	 * @param  [type]	源图片
	 * @param  [$type] single:单个文件压缩 multi:目录下遍历图片
	 * @return [type]
	 */
	private function createDistPic($source_pic_name) {
		$path_parts = pathinfo($source_pic_name);
		$dirname = $path_parts['dirname'];
		$tmp_dirname = $dirname . "_" . date("Y-m-d", time());
		if (!file_exists($tmp_dirname)) {
			mkdir($tmp_dirname, 0777, true);
		}
		$dist_pic_name = $tmp_dirname . "/" . $path_parts['basename'];
		if (file_exists($dist_pic_name)) {
			unlink($dist_pic_name);
			touch($dist_pic_name);
		}
		return $dist_pic_name;
	}

	/**
	 * 检查图片
	 * @param  [type]
	 * @return [type]
	 */
	private function checkPic($source_pic_name) {
		$extension = pathinfo($source_pic_name, PATHINFO_EXTENSION);
		if (!in_array($extension, $this->limit_pic_extensions)) {
			$this->error = "不支持此图片格式，请选择webp,jpeg,png图片进行压缩";
			return false;
		}
		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
		}
		if (isset($finfo)) {
			$type = finfo_file($finfo, $source_pic_name);
		}
		if (!in_array($type, $this->limit_pic_types)) {
			$this->error = "不支持此图片mime，请选择image/png,image/jpeg图片进行压缩";
			return false;
		}
		return true;
	}

	/**
	 * @param  [type]
	 * @return [type]
	 */
	private function findAllPics($dir) {
		$root = scandir($dir);
		foreach ($root as $value) {
			if ($value === '.' || $value === '..') {continue;}
			if (is_file("$dir/$value") && $this->checkPic("$dir/$value")) {
				$result[] = "$dir/$value";
				continue;}
			foreach ($this->findAllPics("$dir/$value") as $value) {
				$result[] = $value;
			}
		}
		return $result;
	}

	/**
	 * 压缩处理器
	 * @param  [file_name] 源图片
	 * @param  [dist_file_name] 压缩后的图片
	 * @param  [resize] 是否缩放图片
	 * @param  [width] 图片宽度
	 * @param  [height] 图片高度
	 * @return [array] 每张图片处理结果
	 */
	private function handler($file_name, $dist_file_name, $resize = false, $width = 150, $height = 100) {
		$message = "";
		try {
			if ($resize) {
				$source = \Tinify\fromFile($file_name);
				$resized = $source->resize(array(
					'method' => 'fit',
					'width' => $width ? $width : 150,
					'height' => $height ? $height : 100,
				));
				$resized->toFile($dist_file_name);
			} else {
				\Tinify\fromFile($file_name)->toFile($dist_file_name);
			}
		} catch (\Exception $e) {
			$message = $e->getMessage;
			$log_message = "{$file_name}#####{$message}";
			$this->loger->addError($log_message);
		}
		if ($message === "") {
			$compression_infos[$file_name] = [
				'status' => true,
				'message' => 'success',
			];
		} else {
			$compression_infos[$file_name] = [
				'status' => false,
				'message' => $message,
			];
		}
		return $compression_infos;
	}
	/**
	 * @return object $log
	 */
	private function getLoger() {
		$log = new Logger($this->log_name);
		$log->pushHandler(new StreamHandler($this->log_file, Logger::WARNING));
		return $log;
	}

}