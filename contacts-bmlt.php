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
    /**
     * Singleton instance of the class.
     *
     * @var null|self
     */
    private static $instance = null;

    /**
     * Initialize the plugin and set up its functionality.
     *
     * This constructor function is called when an instance of the plugin class is created.
     * It hooks the 'pluginSetup' method to the 'init' action, which sets up the plugin's functionality.
     */
    public function __construct()
    {
        add_action('init', [$this, 'pluginSetup']);
    }

    /**
     * Set up the plugin's functionality and actions.
     *
     * This function is responsible for setting up various actions and hooks based on whether
     * the current context is in the WordPress admin or frontend.
     * - In the admin context, it adds menu options and enqueues backend files.
     * - In the frontend context, it enqueues frontend files and registers a shortcode for displaying meetings.
     */
    public function pluginSetup(): void
    {
        if (is_admin()) {
            add_action('admin_menu', [$this, 'optionsMenu']);
            add_action("admin_enqueue_scripts", [$this, "enqueueBackendFiles"], 500);
        } else {
            add_action("wp_enqueue_scripts", [$this, "enqueueFrontendFiles"]);
            add_shortcode('contacts_bmlt', [$this, 'showContacts']);
        }
    }

    /**
     * Set up the plugin settings menu page.
     *
     * This function is responsible for setting up the plugin's settings menu page in the WordPress admin panel.
     * It creates a menu using the Settings class and associates it with the plugin file.
     */
    public function optionsMenu(): void
    {
        $dashboard = new Settings();
        $dashboard->createMenu(plugin_basename(__FILE__));
    }

    /**
     * Display contacts using a shortcode.
     *
     * This function is used to display meetings on a WordPress page or post using a shortcode.
     * It creates a new instance of the Shortcode class and renders the shortcode with the provided attributes.
     *
     * @param array $atts An associative array of attributes passed to the shortcode.
     * @return string The rendered content of the shortcode.
     */
    public function showContacts($atts): string
    {
        $shortcode = new Shortcode();
        return $shortcode->render($atts);
    }

    /**
     * Enqueue backend CSS and JavaScript files for the plugin.
     *
     * This function enqueues the necessary CSS and JavaScript files for the plugin's backend settings page.
     *
     * @param string $hook The current admin page's hook name.
     */
    public function enqueueBackendFiles(string $hook): void
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

    /**
     * Enqueue frontend CSS files for the plugin.
     *
     * This function enqueues the 'upcoming meetings' CSS file for use in the frontend.
     * It is typically used to include stylesheets required for displaying content or features
     * on the public-facing side of the website.
     */
    public function enqueueFrontendFiles(): void
    {
        wp_enqueue_style('contacts-bmlt', plugin_dir_url(__FILE__) . 'css/contacts_bmlt.css', false, '1.21	', 'all');
    }

    /**
     * Get the instance of the class (Singleton pattern).
     *
     * @return self The instance of the class.
     */
    public static function getInstance(): self
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

ContactsBmlt::getInstance();
