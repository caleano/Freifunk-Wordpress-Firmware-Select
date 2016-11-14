<?php namespace Caleano\Freifunk\FirmwareDownload;

use DOMDocument;
use DOMElement;
use DOMXPath;
use WP_Error;

defined('ABSPATH') or die('NOPE');

/**
 * Class Template
 *
 * @TODO: Manifest?
 */
class DirectoryListingParser
{
    protected static $dirRegex = '^[^/]+.+/$';

    /**
     * @param string $url
     * @return array
     */
    public static function parse($url)
    {
        $firmwareDirectories = [];
        $firmwares = [];
        if (!($dirs = self::getLinks($url, self::$dirRegex))) {
            return [];
        }

        foreach ($dirs as $dir) {
            $type = trim($dir, '/');
            $versionDirs = self::getLinks($url . $dir, self::$dirRegex);

            foreach ($versionDirs as $versionDir) {
                $firmwareDirectories[$type][] = trim($versionDir, '/');
            }
        }

        $url = trim($url, '/');
        foreach ($firmwareDirectories as $type => $typeDir) {
            foreach ($typeDir as $variant) {
                $firmwareUrl = implode('/', [$url, $type, $variant, '']);

                $files = self::getLinks($firmwareUrl, '\.(bin|gz|elf|img|vdi|vmdk)$');

                foreach ($files as $file) {
                    $firmwares[$type][$variant][] = [
                        'file' => $file,
                        'url'  => $firmwareUrl . $file,
                    ];
                }
            }
        }

        return $firmwares;
    }

    /**
     * Get a list of Links
     *
     * @param string $url
     * @param string $regex
     * @return array
     */
    private static function getLinks($url, $regex = '')
    {
        if (!($content = self::getPage($url . '?F=0'))) {
            return [];
        }

        $html = new DOMDocument();
        if (!@$html->loadHTML($content)) {
            return [];
        }

        $xpath = new DOMXpath($html);
        $properties = $xpath->query('//ul//li//a');
        $dirs = [];
        foreach ($properties as $item) {
            /** @var DOMElement $item */

            if (!self::matches($item, $regex)) {
                continue;
            }

            $dirs[] = $item->getAttribute('href');
        }

        return $dirs;
    }

    /**
     * Check if the link href matches the regex
     *
     * @param DOMElement $link
     * @param string     $regex
     * @return bool
     */
    private static function matches(DOMElement $link, $regex)
    {
        $href = $link->getAttribute('href');
        $regex = str_replace('~', '\\~', $regex);

        return (bool)preg_match('~' . $regex . '~', $href);
    }

    /**
     * Request a web page
     *
     * @param string $url
     * @return string
     */
    private static function getPage($url)
    {
        $response = wp_remote_get($url);
        if (
            $response instanceof WP_Error
            || $response['response']['code'] != 200
        ) {
            return null;
        }

        $content = $response['body'];

        return $content;
    }
}
