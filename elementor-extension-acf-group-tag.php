<?php

/**
 * Plugin Name: Elementor ACF Group Tag Extension
 * Description: Unlock group acf fields in dynamic tags in elementor.
 * Author URI: https://github.com/umairahmed17
 * Author: Umair Ahmed
 */

/**
 * Defining namespaces
 */

namespace UMRCP;


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Main Elementor Test Extension Class
 *
 * The main class that initiates and runs the plugin.
 *
 * @since 1.0.0
 */
final class ElementorExtensionACFGroup
{

    /**
     * Plugin Version
     *
     * @var string The plugin version.
     * @since 1.0.0
     */
    const VERSION = '1.0.0';

    /**
     * Minimum Elementor Version
     *
     * @var string Minimum Elementor version required to run the plugin.
     * @since 1.0.0
     */
    const MINIMUM_ELEMENTOR_VERSION = '2.0.0';

    /**
     * Minimum PHP Version
     *
     * @var string Minimum PHP version required to run the plugin.
     * @since 1.0.0
     */
    const MINIMUM_PHP_VERSION = '7.0';

    /**
     * Instance
     *
     * @access private
     * @static
     * @var ElementorExtension The single instance of the class.
     * @since 1.0.0
     */
    private static $_instance = null;

    /**
     * Instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @access public
     * @static
     * @since 1.0.0
     *
     * @return ElementorExtension An instance of the class.
     */
    public static function instance()
    {

        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     *
     * @access public
     * @since 1.0.0
     */
    public function __construct()
    {

        add_action('plugins_loaded', [$this,  'on_plugins_loaded']);
        add_action('wp_enqueue_scripts', [$this, 'load_custom_scripts']);
    }

    /**
     * Adding our CSS and JS assets to the plugin
     */

    public function load_custom_scripts()
    {
        //No scripts as of yet
        return;
    }

    /**
     * On Plugins Loaded
     *
     * Checks if Elementor has loaded, and performs some compatibility checks.
     * If All checks pass, inits the plugin.
     *
     * Fired by `plugins_loaded` action hook.
     *
     * @access public
     * @since 1.0.0
     */
    public function on_plugins_loaded()
    {

        if ($this->is_compatible()) {
            add_action('elementor/init', [$this, 'init']);
        }
    }

    /**
     * Compatibility Checks
     *
     * Checks if the installed version of Elementor meets the plugin's minimum requirement.
     * Checks if the installed PHP version meets the plugin's minimum requirement.
     *
     * @access public
     * @since 1.0.0
     */
    public function is_compatible()
    {

        // Check if Elementor installed and activated
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'admin_notice_missing_main_plugin']);
            return false;
        }

        // Check for required Elementor version
        if (!version_compare(ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_elementor_version']);
            return false;
        }

        // Check for required PHP version
        if (version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '<')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_php_version']);
            return false;
        }

        return true;
    }

    /**
     * Initialize the plugin
     *
     * Load the plugin only after Elementor (and other plugins) are loaded.
     * Load the files required to run the plugin.
     *
     * Fired by `plugins_loaded` action hook.
     *
     * @access public
     * @since 1.0.0
     */
    public function init()
    {
        //removing validate id which is returning wrong id due to cache
        // Look at `acf\validate_post_id` filter in advanced-custom-fields/includes/revisions.php
        // Fix a long-standing issue with ACF, where fields sometimes aren't shown
        // in previews (ie. from Preview > Open in new tab).
        if (class_exists('acf_revisions')) {
            // Reference to ACF's <code>acf_revisions</code> class
            // We need this to target its method, acf_revisions::acf_validate_post_id
            $acf_revs_cls = acf()->revisions;

            // This hook is added the ACF file: includes/revisions.php:36 (in ACF PRO v5.11)
            remove_filter('acf/validate_post_id', array($acf_revs_cls, 'acf_validate_post_id', 10));
        }

        // Add Plugin actions
        add_action('elementor/dynamic_tags/register_tags', [$this, 'register_tags']);
    }

    /**
     * Init Widgets
     *
     * Include widgets files and register them
     *
     * @access public
     * @since 1.0.0
     */
    public function init_widgets()
    {
        return; //No widgets as of yet
    }

    /**
     * Register Tags
     *
     * Register new Elementor widgets.
     *
     * @access public
     */
    public function register_tags($dynamic_tags)
    {
        require_once __DIR__ . '/Tags/ACFGroupTag.php';

        // Register Tags
        $dynamic_tags->register_tag(new Tags\ACFGroupTag());
    }

    /**
     * Admin notice
     *
     * Warning when the site doesn't have Elementor installed or activated.
     *
     * @access public
     * @since 1.0.0
     */
    public function admin_notice_missing_main_plugin()
    {

        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        $message = sprintf(
            /* translators: 1: Plugin name 2: Elementor */
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'custom-elementor-widget'),
            '<strong>' . esc_html__('Elementor Test Extension', 'custom-elementor-widget') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'custom-elementor-widget') . '</strong>'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Admin notice
     *
     * Warning when the site doesn't have a minimum required Elementor version.
     *
     * @access public
     * @since 1.0.0
     */
    public function admin_notice_minimum_elementor_version()
    {

        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        $message = sprintf(
            /* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'custom-elementor-widget'),
            '<strong>' . esc_html__('Elementor Test Extension', 'custom-elementor-widget') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'custom-elementor-widget') . '</strong>',
            self::MINIMUM_ELEMENTOR_VERSION
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Admin notice
     *
     * Warning when the site doesn't have a minimum required PHP version.
     *
     * @access public
     * @since 1.0.0
     */
    public function admin_notice_minimum_php_version()
    {

        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        $message = sprintf(
            /* translators: 1: Plugin name 2: PHP 3: Required PHP version */
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'custom-elementor-widget'),
            '<strong>' . esc_html__('Elementor Test Extension', 'custom-elementor-widget') . '</strong>',
            '<strong>' . esc_html__('PHP', 'custom-elementor-widget') . '</strong>',
            self::MINIMUM_PHP_VERSION
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
}

ElementorExtensionACFGroup::instance();
