<?php
/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Kacper Golewski <k.golewski@gmail.com>
 */

namespace Moosh\Command\Generic\Plugin;
use Moosh\MooshCommand;

class PluginFetchInfo extends MooshCommand
{
    protected $results = array();
    protected $visited_urls = array();
    protected $fetched_plugins = 0;

    public function __construct()
    {
        parent::__construct('fetchinfo', 'plugin');
        $this->addOption('p|path:', 'path to result json file', home_dir() . "/.moosh/plugins.json");
        $this->addOption('l|limit:', 'limit fetched results');
        $this->addOption('i|categoryid:', 'search only in given category id');
    }

    public function bootstrapLevel()
    {
        return self::$BOOTSTRAP_NONE;
    }

    public function execute()
    {
        $this->load_json();
        if ($this->expandedOptions['categoryid']) {
            $this->make_request("https://moodle.org/plugins/browse.php?list=category&id=".
                                $this->expandedOptions['categoryid'], 
                                array($this, 'parse_listing'));
        } 
        else {
            $this->make_request("https://moodle.org/plugins/index.php", array($this, 'parse_listing'));
        }
        $this->save_json();
    }

    protected function parse_listing($url, $dom, $meta) {
        $xpath = new \DOMXpath($dom);

        $category_links = $xpath->query('//div[@class="category-name"]/a/@href');
        foreach ($category_links as $link) {
            $this->make_request($link->value, array($this, 'parse_listing'));
        }
        $next_page_links = $xpath->query('//a[@class="next"]/@href');
        foreach ($next_page_links as $link) {
            $this->make_request($link->value, array($this, 'parse_listing'));
            break;
        }
        $plugin_links = $xpath->query('//div[@class="plugin-moodleversions"]/a/@href');
        foreach ($plugin_links as $link) {
            $this->make_request($link->value, array($this, 'parse_plugin'));
        }
        $this->save_json();
    }

    protected function parse_plugin($url, $dom, $meta)
    {
        $xpath = new \DOMXpath($dom);
        $parts = parse_url($url);
        parse_str($parts['query'], $query);

        try {
            $moodle_version = $query['moodle_version'];

            if (array_key_exists('plugin', $query)) {
                $name = $query['plugin'];
            }
            else {
                $name_node = $xpath->query("//div[@class='frankenstyle muted']/text()");
                if ($name_node->length) {
                    $name = $name_node->item(0)->nodeValue;
                }
                else {
                    $name = $xpath->query("//h2[@class='title']/a/text()")->item(0)->nodeValue;
                }
            }

            $download_url = $xpath->query("//a[@class='download btn latest']/@href")->item(0)->nodeValue;
            $full_name = $xpath->query("//h2[@class='title']/a/text()")->item(0)->nodeValue;
            $short_description = $xpath->query("//div[@class='shortdescription']/text()")->item(0)->nodeValue;
        }
        catch(\Exception $e) {
            echo "Failed to parse page: " . $url . "\n" . $e . "\n"; flush();
        }
        if(!array_key_exists($name, $this->results)) {
            $this->results[$name] = array(
                "full_name" => $full_name,
                "short_description" => $short_description,
                "moodle_versions" => array(), 
            );
        }

        $this->results[$name]['moodle_versions'][$moodle_version] = array(
            "url" => $url,
            "download_url" => $download_url,
        );

        $this->fetched_plugins++;
    }

    protected function make_request($url, $callback, $meta=null)
    {
        if ($this->expandedOptions['limit'] && $this->fetched_plugins >= $this->expandedOptions['limit']) {
            return;
        } 
        if(in_array($url, $this->visited_urls)) {
            return;
        }
        $this->visited_urls[] = $url;
        echo "Fetching: " . $url . "\n"; flush();
        if ($meta===null) {
            $meta = array();
        }
        for($retry=0; $retry < 3; $retry++) {
            if($retry > 0) {
                echo "Retrying... (" . $retry . ")" . "\n"; flush();
            }
            try {
                $page = @file_get_contents($url);
                if($page === false) {
                    throw new \Exception("file_get_contents failed");
                }
                $dom = new \DOMDocument();
                libxml_use_internal_errors(true);
                $dom->loadHTML($page);
                libxml_clear_errors();
                call_user_func($callback, $url, $dom, $meta);
                return;
            }
            catch(\Exception $e) {
                echo "Failed to load page DOM at $url: " . $e->getMessage() . "\n"; flush();
            }
        }   
    }

    protected function save_json() {
        file_put_contents($this->expandedOptions['path'], json_encode($this->results));
    }

    protected function load_json() {
        $file = file_get_contents(($this->expandedOptions['path']));
        if ($file) {
            $this->results = json_decode($file);
        }
    }
}