<?php

/*
Plugin Name: Contacts BMLT
Plugin URI: https://wordpress.org/plugins/contacts-bmlt/
Contributors: pjaudiomv, bmltenabled
Author: bmlt-enabled
Description: This plugin returns helpline and website info for service bodies Simply add [contacts_bmlt] shortcode to your page and set shortcode attributes accordingly. Required attributes are root_server.
Version: 1.3.0
Install: Drop this directory into the "wp-content/plugins/" directory and activate it.
*/
/* Disallow direct access to the plugin file */
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    die('Sorry, but you cannot access this page directly.');
}

spl_autoload_register(function (string $class) {
    if (strpos($class, 'ContactsBmlt\\') === 0) {
        $class = str_replace('ContactsBmlt\\', '', $class);
        require __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
    }
});

use ContactsBmlt\Settings;
use ContactsBmlt\Shortcode;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class ContactsBmlt
// phpcs:enable PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public $options = [];

    private static $instance = null;

    public function __construct()
    {
        add_action('init', [$this, 'pluginSetup']);
    }

    public function pluginSetup()
    {
        if (is_admin()) {
            add_action('admin_menu', [$this, 'optionsMenu']);
            add_action("admin_enqueue_scripts", [$this, "enqueueBackendFiles"], 500);
        } else {
            add_action("wp_enqueue_scripts", [$this, "enqueueFrontendFiles"]);
            add_shortcode('contacts_bmlt', [$this, 'showContacts']);
        }
    }

    public function optionsMenu()
    {
        $dashboard = new Settings();
        $dashboard->createMenu(plugin_basename(__FILE__));
    }

    public function showContacts($atts)
    {
        $shortcode = new Shortcode();
        return $shortcode->render($atts);
    }

    public function enqueueBackendFiles($hook)
    {
        if ($hook !== 'settings_page_contacts-bmlt') {
            return;
        }
        $base_url = plugin_dir_url(__FILE__);
        wp_enqueue_style('contacts-bmlt-admin-ui-css', $base_url . 'css/redmond/jquery-ui.css', [], '1.11.4');
        wp_enqueue_style('chosen', $base_url . 'css/chosen.min.css', [], '1.2', 'all');
        wp_enqueue_script('chosen', $base_url . 'js/chosen.jquery.min.js', ['jquery'], '1.2', true);
        wp_enqueue_script('contacts-bmlt-admin', $base_url . 'js/contacts_bmlt_admin.js', ['jquery'], filemtime(plugin_dir_path(__FILE__) . 'js/contacts_bmlt_admin.js'), false);
        wp_enqueue_script('common');
        wp_enqueue_script('jquery-ui-accordion');
    }

    public function enqueueFrontendFiles($hook)
    {
        wp_enqueue_style('contacts-bmlt', plugin_dir_url(__FILE__) . 'css/contacts_bmlt.css', false, '1.20	', 'all');
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

ContactsBmlt::getInstance();
