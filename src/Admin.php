<?php

namespace WP_Smart_Image_Resize;

use WP_Smart_Image_Resize\Quota;
use WP_Smart_Image_Resize\Utilities\Env;

/**
 * Class WP_Smart_Image_Resize\Settings
 *
 * @package WP_Smart_Image_Resize\Inc
 */

if (!defined('ABSPATH')) {
    exit();
}

if (!class_exists('\WP_Smart_Image_Resize\Settings')) :
    class Admin
    {

        protected static $instance = null;

        /**
         * @return Admin
         */
        public static function get_instance()
        {
            if (is_null(static::$instance)) {
                static::$instance = new Admin;
            }

            return static::$instance;
        }

        public function init()
        {
            // Add plugin to WooCommerce menu.
            add_action('admin_menu', [$this, 'add_admin_menu']);
            add_filter('pre_update_option_wp_sir_settings', [$this, 'pre_update_settings']);
            // Show Woocommerce not installed notice.
            add_action('admin_notices', [$this, 'fileinfo_not_enabled']);
            add_action('admin_notices', [$this, 'phpversion_not_supported']);

            
            add_action('admin_notices', [$this, 'quota_exceeding_soon']);
            add_action('admin_notices', [$this, 'quota_exceeded_notice']);

            
            // Initialise settings form.
            add_action('admin_init', [$this, 'init_settings']);

            // Add settings help tab.
            add_action('load-woocommerce_smart-image-resize', [$this, 'settings_help'], 5, 3);

            add_filter('plugin_action_links_' . WP_SIR_BASENAME, [$this, 'plugin_links']);

            add_filter('admin_footer_text', [$this, 'admin_footer_text']);
        }

        function quota_exceeding_soon()
        {
            if (Quota::is_exceeding_soon()) { ?>
                <div class="notice notice-warning is-dismissible">
                    <p><?php _e(
                            'Smart Image Resize: Your are reaching your limit for re-sizing images.',
                            WP_SIR_NAME
                        ); ?>
                        <a target="_blank" href="https:/sirplugin.com/#pro?utm_source=plugin&utm_campaign=notice_limit" class="button button-default"><?php _e(
                                                                                                                                                            'Upgrade to Pro'
                                                                                                                                                        ); ?></a> for
                        unlimited images.
                    </p>
                </div>
            <?php }
        }

        function quota_exceeded_notice()
        {
            if (Quota::isExceeded()) { ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php _e(
                            'Smart Image Resize: Your have reached your limit for re-sizing images.',
                            WP_SIR_NAME
                        ); ?>
                        <a target="_blank" href="https:/sirplugin.com/#pro?utm_source=plugin&utm_campaign=notice_limit" class="button button-default"><?php _e(
                                                                                                                                                            'Upgrade to Pro'
                                                                                                                                                        ); ?></a> for
                        unlimited images.
                    </p>
                </div>
            <?php }
        }

        function admin_footer_text()
        {
            $screen = get_current_screen();

            if (!function_exists('get_current_screen')) {
                return;
            }
            if ($screen->id === 'woocommerce_page_wp-smart-image-resize') { ?>
                
                Please leave us a <a href="https://wordpress.org/support/plugin/smart-image-resize/reviews/">★★★★★
                    rating</a>. We appreciate your support!
                
                
            <?php }
        }

        function plugin_links($links)
        {

            $settings_url    = admin_url('admin.php?page=wp-smart-image-resize');
            $settings_anchor = '<a href="' . $settings_url . '">' . __('Settings') . '</a>';
            array_unshift($links, $settings_anchor);


            
            $links[] = '<a href="https://sirplugin.com/?utm_source=plugin&utm_medium=installed_plugins&utm_campaign=go_pro" target="_blank" style="font-weight:bold;color:#38b2ac">Go Pro</a>';
            

            return $links;
        }

        function pre_update_settings($newval)
        {

            $defaults = [
                'enable'      => 0,
                'jpg_convert' => 0,
                'enable_webp' => 0,
                'enable_trim' => 0,
            ];

            if (isset($newval['processable_images']['taxonomies'])) {
                $newval['processable_images']['taxonomies'] = (array)$newval['processable_images']['taxonomies'];
            } else {
                $newval['processable_images']['taxonomies'] = [];
            }
            if (isset($newval['processable_images']['post_types'])) {
                $newval['processable_images']['post_types'] = (array)$newval['processable_images']['post_types'];
            } else {
                $newval['processable_images']['post_types'] = [];
            }

            return wp_parse_args($newval, $defaults);
        }


        public function fileinfo_not_enabled()
        {
            if (!extension_loaded('fileinfo')) : ?>
                <div class="notice notice-error  is-dismissible">
                    <p><?php _e(
                            'Smart Image Resize: PHP Fileinfo extension is not enabled, contact your hosting provider to enable it.',
                            WP_SIR_NAME
                        ); ?></p>
                </div>
            <?php endif;
        }

        public function phpversion_not_supported()
        {
            if (!version_compare(PHP_VERSION, '5.6.0', '>=')) : ?>
                <div class="notice notice-error  is-dismissible">
                    <p><?php _e(
                            'Smart Image Resize requires PHP 5.6.0 or greater to work correctly.',
                            WP_SIR_NAME
                        ); ?></p>
                </div>
            <?php endif;
        }

        /**
         * Add plugin submenu to WooCommerce menu.
         *
         * @return void
         */
        public function add_admin_menu()
        {

            $parent_slug = 'woocommerce';
            $cap         = 'manage_woocommerce';
            if (!is_plugin_active('woocommerce/woocommerce.php')) {
                $parent_slug = 'options-general.php';
                $cap         = 'manage_options';
            }

            $page_slug = add_submenu_page(
                $parent_slug,
                'Smart Image Resize',
                'Smart Image Resize',
                $cap,
                WP_SIR_NAME,
                [$this, 'settings_page']
            );

            add_action('load-' . $page_slug, [$this, 'add_settings_help']);
        }

        /**
         * Initialize settings form.
         *
         * @return void
         */
        public function init_settings()
        {

            register_setting(WP_SIR_NAME, 'wp_sir_settings');

            // General section.
            add_settings_section('wp_sir_settings_general', 'General', null, WP_SIR_NAME);
            add_settings_section('wp_sir_settings_optimization', 'Optimization', null, WP_SIR_NAME);
            add_settings_section('wp_sir_settings_advanced', 'Advanced', null, WP_SIR_NAME);

            // Register `Enable/Disable` plugin resize field.
            add_settings_field(
                'wp_sir_settings_enable',
                'Enable Resizing',
                [$this, 'settings_field_enable'],
                WP_SIR_NAME,
                'wp_sir_settings_general'
            );
            add_settings_field(
                'wp_sir_settings_processable_images',
                'Images',
                [$this, 'settings_field_processable_images'],
                WP_SIR_NAME,
                'wp_sir_settings_general'
            );

            // Register `Sizes` field.
            add_settings_field(
                'wp_sir_settings_sizes',
                'Sizes',
                [$this, 'settings_field_sizes'],
                WP_SIR_NAME,
                'wp_sir_settings_advanced'
            );

            // Register `Enable WebP format` field.
            add_settings_field(
                'wp_sir_settings_enable_trim',
                'Trim Whitespace',
                [$this, 'settings_field_enable_trim'],
                WP_SIR_NAME,
                'wp_sir_settings_general'
            );

            // Register `Background Color` field.
            add_settings_field(
                'wp_sir_settings_bg_color',
                'Background Color',
                [$this, 'settings_field_bg_color'],
                WP_SIR_NAME,
                'wp_sir_settings_general'
            );

            // Register `Image Compression` field.
            add_settings_field(
                'wp_sir_settings_image_quality',
                'Image Compression',
                [$this, 'settings_field_image_quality'],
                WP_SIR_NAME,
                'wp_sir_settings_optimization'
            );

            // Register `Convert to JPG format` field.
            add_settings_field(
                'wp_sir_settings_jpg_convert',
                'Convert to JPEG',
                [$this, 'settings_field_jpg_convert'],
                WP_SIR_NAME,
                'wp_sir_settings_optimization'
            );

            // Register `Enable WebP format` field.
            add_settings_field(
                'wp_sir_settings_enable_webp',
                'Enable WebP',
                [$this, 'settings_field_enable_webp'],
                WP_SIR_NAME,
                'wp_sir_settings_optimization'
            );
        }

        function settings_field_enable_trim()
        {
            $settings = \wp_sir_get_settings(); ?>
            <label for="wp-sir-enable-trim">
                <input type="checkbox" name="wp_sir_settings[enable_trim]" <?php checked($settings['enable_trim'], 1); ?> id="wp-sir-enable-trim" class="wp-sir-as-toggle" value="1" />
            </label>
            <p class="description">
                <?php _e('Remove unwanted whitespace around image.', 'wp-smart-image-resize'); ?>
            </p>
            <div class="hidden" id="wp-sir-trim-feather-wrap" style="margin-top:10px">
                Border Size (px) <input type="number" min="0" name="wp_sir_settings[trim_feather]" style="width:70px" value="<?php echo $settings['trim_feather'] ?>">
                <p class="description">This will leave a untouched "border" around image while trimming.</p>
            </div>
            <div class="hidden" id="wp-sir-trim-tolerance-wrap" style="margin-top:10px">
                Tolerance Level (%) <input type="number" min="0" max="100" name="wp_sir_settings[trim_tolerance]" style="width:70px" value="<?php echo $settings['trim_tolerance'] ?>">
                <p class="description">Increase the tolerance level to trim away colors that differ slightly from pure white.
                    <br>
                    Default: 3 (Max: 100)
                </p>
            </div>


        <?php
        }

        function settings_field_processable_images()
        {
            $settings = \wp_sir_get_settings();

        ?>
            <div>
                <label for="wp-sir-processable-images-product" style="display: flex; align-items: center; margin-bottom: 10px">
                    <input type="checkbox" name="wp_sir_settings[processable_images][post_types][]" <?php
                                                                                                    echo in_array(
                                                                                                        'product',
                                                                                                        $settings['processable_images']['post_types'],
                                                                                                        true
                                                                                                    ) ? 'checked' : '';
                                                                                                    ?> id="wp-sir-processable-images-product" class="wp-sir-as-toggle" value="product" /> <span style="display:inline-block">Product images</span>
                </label>
                <label for="wp-sir-processable-images-product-cat" style="display: flex; align-items: center">
                    <input type="checkbox" name="wp_sir_settings[processable_images][taxonomies][]" <?php echo in_array(
                                                                                                        'product_cat',
                                                                                                        $settings['processable_images']['taxonomies'],
                                                                                                        true
                                                                                                    ) ? 'checked' : ''; ?> id="wp-sir-processable-images-product-cat" class="wp-sir-as-toggle" value="product_cat" /> <span style="display:inline-block">Category images</span>
                </label>
            </div>
            <p class="description">
                <?php _e('Select which images to resize.', 'wp-smart-image-resize'); ?>
            </p>
        <?php
        }

        function settings_field_jpg_convert()
        {
            $settings = \wp_sir_get_settings(); ?>
            <label for="wp-sir-jpg-convert">
                <input type="checkbox" name="wp_sir_settings[jpg_convert]" <?php checked($settings['jpg_convert'], 1); ?> id="wp-sir-jpg-convert" class="wp-sir-as-toggle"  disabled  value="1" />
                
                <a target="_blank" href="https://sirplugin.com?utm_source=plugin&utm_medium=upgrade&utm_campaign=jpg_convert"><?php _e(
                                                                                                                                    'Upgrade to PRO',
                                                                                                                                    WP_SIR_NAME
                                                                                                                                ); ?></a>
                
            </label>
            <p class="description">
                <?php _e(
                    "Converting PNG images to JPG is highly recommended to boost page load time.",
                    WP_SIR_NAME
                ); ?>
            </p>
        <?php
        }

        function settings_field_enable_webp()
        {
            $settings = \wp_sir_get_settings(); ?>
            <label for="wp-sir-enable-webp">
                <input type="checkbox" name="wp_sir_settings[enable_webp]" <?php checked($settings['enable_webp'], 1); ?> id="wp-sir-enable-webp" class="wp-sir-as-toggle"  disabled  value="1" />
                
                <a target="_blank" href="https://sirplugin.com?utm_source=plugin&utm_medium=upgrade&utm_campaign=enabled_webp"><?php _e(
                                                                                                                                    'Upgrade to PRO',
                                                                                                                                    WP_SIR_NAME
                                                                                                                                ); ?></a>
                

            </label>
            <p class="description">
                <?php _e(
                    "WebP reduces image file size by up to 90% comparing to PNG images without losing quality.<br>NOTE: The plugin will gracefully fall back on JPEGs and PNGs for browsers that cannot display WebP images.",
                    WP_SIR_NAME
                ); ?>
            </p>
            
        <?php
        }

        public function settings_field_image_quality($args)
        {
            $settings = \wp_sir_get_settings(); ?>
            <input name="wp_sir_settings[jpg_quality]" type="hidden" class="wpSirImageQuality" value="<?php echo absint($settings['jpg_quality']); ?>" />
            <div class="wpSirSlider" style="width:300px" data-input="wpSirImageQuality">
                <div class="wpSirSliderHandler ui-slider-handle ppsir-slider-handle"></div>
            </div>
        <?php
        }

        function settings_field_sizes()
        {
            $settings = \wp_sir_get_settings('view'); ?>
            <select multiple="multiple" id="wpSirResizeSizes" name="wp_sir_settings[sizes][]" data-defaults="<?php echo implode(',', _wp_sir_get_default_sizes()) ?>">
                <?php foreach (wp_sir_get_additional_sizes('view') as $key => $size) :
                ?>
                    <option value="<?php echo $key; ?>" <?php echo in_array(
                                                            $key,
                                                            $settings['sizes']
                                                        )
                                                            ? 'selected'
                                                            : ''; ?>><?php echo "$key({$size['width']}x{$size['height']})"; ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description">
                Use the dropdown list above to select which image sizes to generate.
                <br>
                NOTE: To save some disk space, only needed sizes are pre-selected. <button id="wpsirResetDefaultSizes" type="button" class="button-link">Reset to pre-selected sizes</button>
            </p>
        <?php
        }



        public function settings_field_bg_color($args)
        {
            $settings = \wp_sir_get_settings(); ?>
            <input name="wp_sir_settings[bg_color]" value="<?php echo $settings['bg_color']; ?>" type="text" id="wpSirColorPicker" />
            <button type="button" class="button button-default button-small" id="wp-sir-clear-bg-color" style="min-height:30px">Clear</button>
            <p class="description">
                NOTE: A white background is used by default. To preserve image transpancy remove the selected color above by clicking the button "Clear".</p>
        <?php
        }

        public function settings_field_enable($args)
        {
            $settings = \wp_sir_get_settings(); ?>
            <label for="wp-sir-enable">
                <input type="checkbox" class="wp-sir-as-toggle wp-sir-as-toggle--large" name="wp_sir_settings[enable]" id="wp-sir-enable" value="1" <?php checked($settings['enable'], 1); ?> />
            </label>
            <?php
            
            Quota::show_quota_status();
            
            ?>
<?php
        }

        public function settings_page()
        {
            include_once WP_SIR_DIR . 'templates/settings.php';
        }

        function add_settings_help()
        {

            if (!function_exists('get_current_screen')) {
                return;
            }

            $screen = get_current_screen();

            // Add one help tab
            $screen->add_help_tab(array(
                'id'      => 'wp-sir-help-tab1',
                'title'   => esc_html__('Overview', WP_SIR_NAME),
                'content' =>
                '<p><strong>Images:</strong> Select which images to generate.</p>' .
                    '<p><strong>Sizes:</strong> Select which sizes to generate.</p>' .
                    '<p><strong>Background Color:</strong> set the color of the emerging (empty) area of the generated thumbnail. Leave it empty for transparent background.</p>' .
                    '<p><strong>Image Compression:</strong> Compress images to reduce image file size to improve  page load time.</p>' .
                    '<p><strong>Trim whitespace:</strong> Remove unwanted whitespace around image to make all images look uniform.</p>' .
                    '<p><strong>Convert to JPEG:</strong> If transparent images aren\'t required, it\'s recommanded to convert images to JPG to boost page load speed.</p>' .
                    '<p><strong>Enable WebP:</strong> WebP is the rockstart of image formats. Using WebP can dramatically reduce image file size without losing the quality of the image. WebP is widely supported by all modern browsers, otherwise, it fall backs automatically to standard image.</p>'
            ));


            
            $help_sidebar = '<p><a href="https://sirplugin.com?utm_source=plugin&utm_medium=upgrade&utm_campaign=help_sidebar">Upgrade to PRO</a></p>' .
                '<p><a href="https://wordpress.org/support/plugin/smart-image-resize/" target="_blank">Report an issue</a></p>';
            
            $screen->set_help_sidebar(
                '<p><strong>' .
                    esc_html__('For more information:', WP_SIR_NAME) .
                    '</strong></p>' . $help_sidebar
            );
        }
    }
endif;
