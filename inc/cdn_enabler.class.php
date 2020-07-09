<?php

/**
 * CDN_Enabler
 *
 * @since 0.0.1
 */

class CDN_Enabler
{


    /**
     * pseudo-constructor
     *
     * @since   0.0.1
     * @change  0.0.1
     */

    public static function instance() {
        new self();
    }


    /**
     * constructor
     *
     * @since   0.0.1
     * @change  1.0.9
     */

    public function __construct() {
        /* CDN rewriter hook */
        add_action(
            'template_redirect',
            [
                __CLASS__,
                'handle_rewrite_hook',
            ]
        );

        /* Rewrite rendered content in REST API */
        add_filter(
            'the_content',
            [
                __CLASS__,
                'rewrite_the_content',
            ],
            100
        );

        /* Hooks */
        add_action(
            'admin_init',
            [
                __CLASS__,
                'register_textdomain',
            ]
        );
        add_action(
            'admin_init',
            [
                'CDN_Enabler_Settings',
                'register_settings',
            ]
        );
        add_action(
            'admin_menu',
            [
                'CDN_Enabler_Settings',
                'add_settings_page',
            ]
        );
        add_filter(
            'plugin_action_links_' .CDN_ENABLER_BASE,
            [
                __CLASS__,
                'add_action_link',
            ]
        );

        /* admin notices */
        add_action(
            'all_admin_notices',
            [
                __CLASS__,
                'cdn_enabler_requirements_check',
            ]
        );
    }


    /**
     * add action links
     *
     * @since   0.0.1
     * @change  0.0.1
     *
     * @param   array  $data  alreay existing links
     * @return  array  $data  extended array with links
     */

    public static function add_action_link($data) {
        // check permission
        if ( ! current_user_can('manage_options') ) {
            return $data;
        }

        return array_merge(
            $data,
            [
                sprintf(
                    '<a href="%s">%s</a>',
                    add_query_arg(
                        [
                            'page' => 'cdn_enabler',
                        ],
                        admin_url('options-general.php')
                    ),
                    __("Settings")
                ),
            ]
        );
    }


    /**
     * run uninstall hook
     *
     * @since   0.0.1
     * @change  0.0.1
     */

    public static function handle_uninstall_hook() {
        delete_option('cdn_enabler');
    }


    /**
     * run activation hook
     *
     * @since   0.0.1
     * @change  1.0.5
     */

    public static function handle_activation_hook() {
        add_option(
            'cdn_enabler',
            [
                'url'            => get_option('home'),
                'dirs'           => 'wp-content,wp-includes',
                'excludes'       => '.php',
                'relative'       => '1',
                'https'          => '',
            ]
        );
    }


    /**
     * check plugin requirements
     *
     * @since   0.0.1
     * @change  0.0.1
     */

    public static function cdn_enabler_requirements_check() {
        // WordPress version check
        if ( version_compare($GLOBALS['wp_version'], CDN_ENABLER_MIN_WP.'alpha', '<') ) {
            show_message(
                sprintf(
                    '<div class="error"><p>%s</p></div>',
                    sprintf(
                        __("CDN Enabler is optimized for WordPress %s. Please disable the plugin or upgrade your WordPress installation (recommended).", "cdn-enabler"),
                        CDN_ENABLER_MIN_WP
                    )
                )
            );
        }
    }


    /**
     * register textdomain
     *
     * @since   1.0.3
     * @change  1.0.3
     */

    public static function register_textdomain() {
        load_plugin_textdomain(
            'cdn-enabler',
            false,
            'cdn-enabler/lang'
        );
    }


    /**
     * return plugin options
     *
     * @since   0.0.1
     * @change  1.0.5
     *
     * @return  array  $diff  data pairs
     */

    public static function get_options() {
        return wp_parse_args(
            get_option('cdn_enabler'),
            [
                'url'             => get_option('home'),
                'dirs'            => 'wp-content,wp-includes',
                'excludes'        => '.php',
                'relative'        => 1,
                'https'           => 0,
            ]
        );
    }


    /**
     * return new rewriter
     *
     * @since   1.0.9
     * @change  1.0.9
     *
     */

    public static function get_rewriter() {
        $options = self::get_options();

        $excludes = array_map('trim', explode(',', $options['excludes']));

        $now = defined('MULTIPLE_DOMAIN_DOMAIN') ? constant('MULTIPLE_DOMAIN_DOMAIN') : get_option('home');
        $cdn_url = preg_match("yesterday17") ? $options['url'] : 'https://static.mmf.moe';

        return new CDN_Enabler_Rewriter(
            $now,
            $cdn_url,
            $options['dirs'],
            $excludes,
            $options['relative'],
            $options['https']
        );
    }


    /**
     * run rewrite hook
     *
     * @since   0.0.1
     * @change  1.0.9
     */

    public static function handle_rewrite_hook() {
        $options = self::get_options();

        // check if origin equals cdn url
        if (get_option('home') == $options['url']) {
            return;
        }

        $rewriter = self::get_rewriter();
        ob_start(array(&$rewriter, 'rewrite'));
    }


    /**
     * rewrite html content
     *
     * @since   1.0.9
     * @change  1.0.9
     */

    public static function rewrite_the_content($html) {
        $rewriter = self::get_rewriter();
        return $rewriter->rewrite($html);
    }

}
