<?php defined('ABSPATH') or die('NOPE');

/*
Plugin Name: Freifunk Firmware Select
Plugin URI: https://caleano.com
Description: Freifunk Firmware selector
Version: 1.0.0
Author: Igor Scheller
Author URI: https://igorshp.de
License: MIT
*/

use Caleano\Freifunk\FirmwareDownload\FirmwareDownload;
use Caleano\Freifunk\FirmwareDownload\Template;
use Caleano\Freifunk\FirmwareDownload\WordpressRouting;

require_once __DIR__ . '/src/WordpressRouting.php';
require_once __DIR__ . '/src/FirmwareDownload.php';
require_once __DIR__ . '/src/Template.php';

$router = new WordpressRouting();
$template = new Template();
$firmwareDownload = new FirmwareDownload();
