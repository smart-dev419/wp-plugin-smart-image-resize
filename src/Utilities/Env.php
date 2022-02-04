<?php

namespace WP_Smart_Image_Resize\Utilities;

use Imagick;

class Env
{
    public static function imagick_loaded()
    {
        return extension_loaded('imagick') && class_exists(Imagick::class);
    }
    
    public static function gd_loaded()
    {
        return extension_loaded('gd') && function_exists('gd_info');
    }

    /**
     * Check whether Imagick extension is loaded and support WebP.
     * @return bool
     */
    public static function imagick_supports_webp()
    {
        return self::imagick_loaded() && Imagick::queryFormats('WEBP');
    }

    /**
     * Check whether GD extension is loaded and support WebP.
     * @return bool
     */

    public static function gd_supports_webp()
    {
        return function_exists('imagewebp');
    }

    public static function browser_supposts_webp()
    {
        // TODO: Ajax requests don't include the 'image/webp' in the Accept header.
        return isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;
    }

    /**
     * Returns true if the active image processing library can handle WebP images.
     * 
     * @return bool
     */
    public static function supports_webp()
    {
        $image_processor = static::active_image_processor();

        if ($image_processor === 'imagick' && static::imagick_supports_webp()) {
            return true;
        }

        if ($image_processor === 'gd' && static::gd_supports_webp()) {
            return true;
        }

        return false;
    }

    /**
     * Returns the filtered image processor.
     * @return string
     */
    public static function active_image_processor($filtered = true)
    {
        $default = Env::imagick_loaded() ? 'imagick' : 'gd';

        // Handle an exception where imagick was compiled without WebP support.
        $imagick_missing_webp = (bool)wp_sir_get_settings()['enable_webp'] && !static::imagick_supports_webp();

        if ($default === 'imagick' && $imagick_missing_webp) {
            $default = 'gd';
        }

        if( ! $filtered ){
            return $default;
        }
        
        $filtered = apply_filters('wp_sir_driver', $default);
        $filtered  = strtolower($filtered);
    
        // Return the default processor if the filtered one isn't supported.
        if($filtered === 'imagick' || $filtered === 'gd'){
            return $filtered;
        }

        return $default;
    }
}
