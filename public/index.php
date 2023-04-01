<?php
date_default_timezone_set('Asia/Shanghai');
require_once __DIR__ . "/../vendor/autoload.php";
$config = require_once __DIR__ . "/../config/tinypng.php";
/*******************传单个文件路径****************************************/
// use Compression\Compression;
// $app_key = $config['APP_KEY'];
// $log_file = "/data/www/ack/logs/tinypng.log";
// $compression = new Compression($app_key, $log_file);
// $source_pic_name = "/data/www/ack/tests/images/d.jpeg";
// $compression->single_pic_handler($source_pic_name);
// var_dump($compression->getError());

/**********************处理整个目录下的所有图片*********************************/
use Compression\Compression;
$app_key = $config['APP_KEY'];
$log_file = "/data/www/ack/logs/tinypng.log";
$compression = new Compression($app_key, $log_file);
$directory = "/data/www/ack/tests/images";
$compression->multi_pic_handler($directory, true, 240, 120);
var_dump($compression->getError());