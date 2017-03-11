<?php namespace Caleano\Freifunk\FirmwareDownload;

use Caleano\Freifunk\FirmwareDownload\WordpressRouting as Route;
use WP_Post;

defined('ABSPATH') or die('NOPE');

class FirmwareDownload
{
    public function __construct()
    {
        Route::get('router/firmware', [$this, 'onGetSelect']);
        Route::get('router/firmware/get', [$this, 'onGetList']);
    }

    /**
     * @param WP_Post $page
     * @return WP_Post
     */
    public function onGetSelect(WP_Post $page)
    {
        $page->post_title = 'Firmware Selector';
        $template = Template::render('select');
        $template = Template::removeWhitespace($template);
        $page->post_content = $template;

        return $page;
    }

    /**
     * @param WP_Post $page
     * @return array
     */
    public function onGetList(WP_Post $page)
    {
        $list = $this->getFirmwareList('https://dl.ffm.freifunk.net/firmware/');
        $return = [];

        foreach ($list as $stability => $items) {
            foreach ($items as $type => $images) {
                foreach ($images as $image) {
                    $return[$image['supplier']][$image['name']][$stability][$type][] = $image;
                }
            }
        }

        $return = $this->sortFirmwareList($return);

        return $return;
    }

    /**
     * Returns the firmware files list
     *
     * @param string $url
     * @return array
     */
    protected function getFirmwareList($url)
    {
        $firmware = DirectoryListingParser::parse($url);
        /*
            1: gluon
            2: community
            3: version
            4: stability
            5: build
            6: name
            7: ? is sysupgrade
            8: extension
         */
        $nameRegex = '^(\w+)-(\w+)-([\w\d\.]+)-(\w+)-(\d+)-(.+)(-sysupgrade)?\.(bin|(?:tar\.|img\.)?gz|tar|elf|img|vdi|vmdk)$';

        /*
        1: supplier
        2: ? name and version
         */
        $devicesRegex = '(\w+(?:-link|-64)?)(?:-(.*))?';

        /*
        1: Name
        2: ? Version
        3: ? Version
        */
        $versionRegex = '(.+)(?:-((?:rev-)?[\w]-?(?:\d+(?:\.\d+)?)?)?|(v\d+))?$';

        foreach ($firmware as $branch => &$types) {
            foreach ($types as $type => &$images) {
                foreach ($images as $key => &$image) {
                    // Parse router filename parts
                    if (!preg_match('/' . $nameRegex . '/U', $image['file'], $matches)) {
                        unset($images[$key]);
                        continue;
                    }

                    $name = $matches[6];
                    $image['name'] = $name;
                    $image['community'] = $matches[2];
                    $image['type'] = $matches[8];
                    $image['version'] = $matches[3] . '-' . $matches[4] . '-' . $matches[5];

                    // Parse router name details
                    preg_match('/' . $devicesRegex . '/', $name, $matches);
                    $image['supplier'] = $matches[1];

                    if (isset($matches[2])) {
                        $nameAndVersion = $matches[2];
                        // Parse router version
                        preg_match('/' . $versionRegex . '/U', $nameAndVersion, $matches);

                        $image['name'] = $matches[1];

                        if (isset($matches[2])) {
                            $image['revision'] = $matches[2];
                        } elseif (isset($matches[3])) {
                            $image['revision'] = $matches[3];
                        }
                    }

                    if (empty($image['revision'])) {
                        $image['revision'] = $image['name'];
                    }
                }
            }
        }

        return $firmware;
    }

    /**
     * @param array $firmwareList
     * @return mixed
     */
    protected function sortFirmwareList($firmwareList)
    {
        ksort($firmwareList);

        foreach ($firmwareList as &$listItem) {
            ksort($listItem);

            foreach ($listItem as &$stability) {
                uksort($stability, function ($a, $b) {
                    $priority = ['stable', 'test', 'dev'];

                    if (array_search($a, $priority) > array_search($b, $priority)) {
                        return 1;
                    }

                    return -1;
                });

                foreach ($stability as &$items) {
                    ksort($items);
                    foreach ($items as &$files) {
                        usort($files, function ($a, $b) {
                            if (isset($a['revision']) && isset($b['revision'])) {
                                $va = (float)preg_replace('/[^\d\.]/', '', $a['revision']);
                                $vb = (float)preg_replace('/[^\d\.]/', '', $b['revision']);
                                return ($va > $vb) ? 1 : -1;
                            }

                            if (isset($a['revision'])) {
                                return 1;
                            } elseif (isset($b['revision'])) {
                                return -1;
                            }

                            return strcmp($a['file'], $b['file']);
                        });
                    }
                }
            }
        }

        return $firmwareList;
    }
}
