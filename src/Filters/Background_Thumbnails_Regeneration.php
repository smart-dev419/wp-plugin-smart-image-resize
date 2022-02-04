<?php

namespace WP_Smart_Image_Resize\Filters;

class Background_Thumbnails_Regeneration extends Base_Filter
{
    public function listen()
    {
        add_filter( 'woocommerce_regenerate_images_intermediate_image_sizes', [ $this, 'add_regeneratable_sizes' ] );
        add_filter('woocommerce_background_image_regeneration', '__return_false');
    }

    /**
     * Hook into WC regenerate tool to add user selected sizes.
     *
     * @param $wooSizes
     *
     * @return array
     */
    public function add_regeneratable_sizes( $wooSizes )
    {
        $selectedSizes = apply_filters( 'wp_sir_sizes', wp_sir_get_settings()[ 'sizes' ] );

        return array_merge( $wooSizes, $selectedSizes );
    }

}
