<?php

namespace WP_Smart_Image_Resize;

/*
 * Class WP_Smart_Image_Resize\Plugin
 *
 * @package WP_Smart_Image_Resize\Inc
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

if ( ! class_exists( '\WP_Smart_Image_Resize\Plugin' ) ) :
    class Plugin
    {
        protected static $instance = null;

        /**
         * @return Plugin
         */
        public static function get_instance()
        {
            if ( is_null( static::$instance ) ) {
                static::$instance = new Plugin();
            }

            return static::$instance;
        }

        /**
         * Run plugin.
         */
        public function run()
        {
            $this->define_hooks();
            $this->load_dependencies();
            $this->maybe_upgrade();
        }

        function maybe_upgrade()
        {

            // Keep track of previous version.
            update_option( 'wp_sir_prev_plugin_version', get_option( 'wp_sir_plugin_version' ) );

            // Update the plugin version.
            update_option( 'wp_sir_plugin_version', WP_SIR_VERSION );
        }

        /**
         * Load js scripts.
         *
         * @return void
         */
        public function enqueue_scripts()
        {
            if ( ! $this->can_enqueue_scripts() ) {
                return;
            }

            wp_enqueue_script( 'wp-color-picker' );

            wp_enqueue_script(
                'multi-select',
                WP_SIR_URL . 'js/multiselect.min.js',
                [ 'jquery' ],
                null,
                true
            );

            wp_enqueue_script( 'jquery-ui-progressbar' );

            wp_enqueue_script(
                WP_SIR_NAME,
                WP_SIR_URL . 'js/scripts.js',
                [ 'multi-select', 'wp-color-picker', 'jquery-ui-progressbar' ],
                $this->get_main_build_version('js/scripts.js'),
                true
            );

            wp_localize_script( 'wp-smart-image-resize', 'wp_sir_object', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'wp-sir-ajax' ),
                'process_ml_upload_cookie'=> Process_Media_Library_Upload::COOKIE_NAME
            ] );
        }

        /**
         * Load css.
         *
         * @return void
         */
        public function enqueue_styles()
        {
            if ( ! $this->can_enqueue_scripts() ) {
                return;
            }

            wp_enqueue_style( 'wp-color-picker' );

            $wp_scripts = wp_scripts();

            wp_enqueue_style(
                'jquery-ui-theme-smoothness',
                sprintf(
                    '//ajax.googleapis.com/ajax/libs/jqueryui/%s/themes/smoothness/jquery-ui.css',
                    $wp_scripts->registered[ 'jquery-ui-core' ]->ver
                )
            );
            wp_enqueue_style(
                'multi-select',
                WP_SIR_URL . 'css/multiselect.min.css');
            wp_enqueue_style(
                WP_SIR_NAME,
                WP_SIR_URL . 'css/style.css',
                [ 'wp-color-picker', 'multi-select' ],
                $this->get_main_build_version('css/style.css')
            );
        }

        private function get_main_build_version($file){
            return defined('WP_DEBUG') && WP_DEBUG ?  filemtime(WP_SIR_DIR . $file): WP_SIR_VERSION;
        }
        public function can_enqueue_scripts()
        {
            if ( ! is_admin() ) {
                return false;
            }

            require_once( ABSPATH . 'wp-admin/includes/screen.php' );

            if ( ! function_exists( 'get_current_screen' ) ) {
                return false;
            }

            $screen = get_current_screen();

            if ( ! $screen || ! is_object( $screen ) ) {
                return false;
            }
            if ( strpos( $screen->id, 'smart-image-resize' ) !== false
                 || in_array( $screen->id, [ 'media', 'upload' ] )
            ) {
                return true;
            }

            return false;
        }

        /**
         * Load plugin dependencies.
         *
         * @return void
         */
        public function load_dependencies()
        {
            require_once WP_SIR_DIR . 'src/vendor/autoload.php';
            require_once WP_SIR_DIR . 'src/Exceptions/Invalid_Image_Meta_Exception.php';
            require_once WP_SIR_DIR . 'src/Helper.php';
            require_once WP_SIR_DIR . 'src/Image_Filters/Trim_Filter.php';
            require_once WP_SIR_DIR . 'src/Image_Filters/CreateWebP_Filter.php';
            require_once WP_SIR_DIR . 'src/Image_Filters/Thumbnail_Filter.php';
            require_once WP_SIR_DIR . 'src/Image_Filters/Recanvas_Filter.php';
            require_once WP_SIR_DIR . 'src/Image_Manager.php';
            require_once WP_SIR_DIR . 'src/Utilities/Request.php';
            require_once WP_SIR_DIR . 'src/Utilities/Env.php';
            require_once WP_SIR_DIR . 'src/Utilities/File.php';
            require_once WP_SIR_DIR . 'src/functions.php';
            require_once WP_SIR_DIR . 'src/Image_Meta.php';
            require_once WP_SIR_DIR . 'src/Processable_Trait.php';
            require_once WP_SIR_DIR . 'src/Singleton_Trait.php';
            require_once WP_SIR_DIR . 'src/Runtime_Config_Trait.php';
            require_once WP_SIR_DIR . 'src/Quota.php';
            require_once WP_SIR_DIR . 'src/Events/Event_Subscriber.php';
            require_once WP_SIR_DIR . 'src/Filters/Filter_Subscriber.php';
            require_once WP_SIR_DIR . 'src/Image_Editor.php';
            require_once WP_SIR_DIR . 'src/Process_Media_Library_Upload.php';
            require_once WP_SIR_DIR . 'src/Utilities/Backup.php';
            require_once WP_SIR_DIR . 'src/Admin.php';
            require_once WP_SIR_DIR . 'src/Media_Library.php';

            if ( extension_loaded( 'fileinfo' ) ) {
                Image_Editor::get_instance()->run();
            }

            Admin::get_instance()->init();
        }

        /**
         * Load plugin text domain.
         *
         * @return void
         */
        public function set_locale()
        {
            load_plugin_textdomain( 'wp-smart-image-resize', false, WP_SIR_DIR . '/languages/' );
        }

        /**
         * Define run hooks.
         *
         * @return void
         */
        public function define_hooks()
        {
            add_action( 'plugins_loaded', [ $this, 'set_locale' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
            add_action('admin_head-tools_page_regenerate-thumbnails', function(){
                if(! apply_filters('wp_sir_enable_rt_custom_page', true)){
                    return;
                }
                ?>
                <style type="text/css">
                    #regenerate-thumbnails-app > div h2[class="title"],
                    #regenerate-thumbnails-app > div h2[class="title"] + p,
                    #regenerate-thumbnails-app > div h2[class="title"] + p + ul,
                    #regenerate-thumbnails-app > div p[class="submit"] + p,
                    #regenerate-thumbnails-app > div p[class="submit"] + p + ul,
                    #regenerate-thumbnails-app > div p[class="submit"] + div > p,
                    #regenerate-thumbnails-app > div p[class="submit"] + div > p + ul,
                    #regenerate-thumbnails-app > div p[class="submit"] + p + ul + div > p,
                    #regenerate-thumbnails-app > div p[class="submit"] + p + ul + div > p + ul
                    {
                        display: none;
                    }
                </style>
                <?php
            });
        }


    }
endif;
