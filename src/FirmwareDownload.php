<?php namespace Caleano\Freifunk\FirmwareDownload;

use Caleano\Freifunk\FirmwareDownload\WordpressRouting as Route;
use WP_Post;

defined('ABSPATH') or die('NOPE');

class FirmwareDownload
{
    public function __construct()
    {
        Route::get('router/firmware', [$this, 'onGetSelect']);
    }

    public function onGetSelect(WP_Post $page)
    {
        $page->post_title = 'Firmware Selector';
        $template = Template::render('select');
        $template = Template::removeWhitespace($template);
        $page->post_content = $template;

        return $page;
    }
}
