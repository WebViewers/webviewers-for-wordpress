<?php
/*
 * Plugin Name: Webviewer
 * Plugin URI: 
 * Description: Embed attached content in your Wordpress blog using the [webviewer] macro.
 * Version: 0.1
 * Author: Caleb James DeLisle
 * Author URI: https://github.com/cjdelisle/
 * License: LGPLv2.1 (http://www.gnu.org/licenses/lgpl-2.1.txt)
*/

define('WP_WEBVIEWER_DIR', plugin_dir_path(__FILE__));

// workaround for http://core.trac.wordpress.org/ticket/16953
function wp_webviewer_get_url()
{
    static $url;
    if (!$url) {
        $url = preg_replace('/.*\/wp-content\/plugins\/([^\/]*)\/.*$/', '$1', __FILE__);
        if ($url && $url !== __FILE__) {
            $url = plugins_url() . '/' . $url . '/';
        } else {
            error_log("could not find directory location ".__FILE__.":".__LINE__);
            $url = plugin_dir_url(__FILE__);
        }
    }
    return $url;
}
define('WP_WEBVIEWER_URL', wp_webviewer_get_url());



function wp_webviewer_init() {
    wp_register_script('wp_webviewer_jschannel', WP_WEBVIEWER_URL.'resources/jschannel.js');
    wp_register_script('wp_webviewer_renderjs_jquery',
                       WP_WEBVIEWER_URL.'resources/renderjs_jquery.js');
    wp_register_script('wp_webviewer_renderjs',
                       WP_WEBVIEWER_URL.'resources/renderjs.js',
                       array('wp_webviewer_jschannel','wp_webviewer_renderjs_jquery'));
    wp_register_script('wp_webviewer_main',
                       WP_WEBVIEWER_URL.'resources/webviewer.js',
                       array('wp_webviewer_renderjs'));
}
add_action('init', 'wp_webviewer_init');



function wp_webviewer_get_viewer($fileType, $action) {
    $path = WP_WEBVIEWER_DIR.'viewers/';
    $dh = opendir($path);
    while (false !== ($filename = readdir($dh))) {
        if ($filename === '..' || $filename === '.') { continue; }
        $fpath = $path.$filename;
        if (!is_dir($fpath)) { continue; }
        $filePath = $fpath.'/package.json';
        if (!file_exists($filePath)) { continue; }
        $content = file_get_contents($filePath);
        if (!($json = json_decode($content))) { continue; }
        if (!isset($json->resilience)) { continue; }
        if (isset($json->modelVersion) && $json->modelVersion != "0.1") { continue; }
        if (!isset($json->resilience->actions)) { continue; }
        if (!isset($json->resilience->actions->{$action})) { continue; }
        $fileTypes = $json->resilience->actions->{$action};
        if (!in_array($fileType, $fileTypes)) { continue; }
        // Found an appropriate viewer...
        $main = isset($json->resilience->main) ? $json->resilience->main : 'index.html';
        return str_replace(WP_WEBVIEWER_DIR, WP_WEBVIEWER_URL, $fpath.'/'.$main);
    }
    return NULL;
}



function wp_webdriver_get_fileType($url)
{
    return preg_replace('/^.*\.([^.]*)$/', '$1', $url);
}



// shortcode [embed id=123 width=300px height=200px]
function wp_webviewer_embed($atts)
{
    extract(shortcode_atts(array(
                'id' => '-1',
                'url' => '',
                'width' => '800px',
                'height' => '400px'
                    ), $atts));

    if ($id >= 0) {
      $url = wp_get_attachment_url($id);
    } else if ($url === '') {
        return "[webviewer: MISSING ATTACHMENT ID OR URL!]";
    }

    $fileType = wp_webdriver_get_fileType($url);
    $viewer = wp_webviewer_get_viewer($fileType, 'view');

    if ($viewer === NULL) {
        return "[webviewer: NO WEBVIEWER AVAILABLE FOR FILE TYPE: '".htmlentities($fileType)."']";
    }

    wp_enqueue_script('wp_webviewer_main');

    return '<div style="width:'.htmlentities($width).';height:'.htmlentities($height).'" '
               .'data-gadget="'.htmlentities($viewer).'" '
               .'data-gadget-content="'.htmlentities($url).'"></div>';
}
add_shortcode('webviewer', 'wp_webviewer_embed');

?>
