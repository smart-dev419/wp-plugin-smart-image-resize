<?php

namespace WP_Smart_Image_Resize\Image_Filters;

use Imagick;
use \Intervention\Image\Filters\FilterInterface;
use Exception;
use Intervention\Image\Image;
use WP_Smart_Image_Resize\Image_Meta;
use WP_Smart_Image_Resize\Utilities\Env;

class Trim_Filter implements FilterInterface
{

    /**
     * The image meta helper instance.
     * @var Image_Meta $imageMeta
     */
    protected $imageMeta;

    public function __construct( $imageMeta )
    {
        $this->imageMeta = $imageMeta;
    }

    /**
     * Set trimmed image dimensions.
     *
     * @param $image Image
     */
    private function set_new_dimensions( $image )
    {
        $this->imageMeta->setMetaItem( '_trimmed_width', $image->getWidth() );
        $this->imageMeta->setMetaItem( '_trimmed_height', $image->getHeight() );
    }

    /**
     * @param Image $image
     *
     * @return Image
     */
    public function applyFilter( Image $image )
    {
        $settings = wp_sir_get_settings();

        if ( ! $settings[ 'enable_trim' ] ) {

            // Chances the trim feature was re-disabled.
            // In this case, we need revert to original dimensions
            // to prevent zoomed image from being stretshed.
            $this->set_new_dimensions( $image );

            return $image;
        }

        try {
            /** @var Imagick $core */
            $core = is_object( $image->getCore() ) ? ( clone $image->getCore() ) : null;

            $feather = (int)apply_filters( 'wp_sir_trim_feather',  (int)$settings[ 'trim_feather' ] );

            $color = sanitize_hex_color( $settings[ 'bg_color' ] ) ?: null;

            $tolerance = (int)apply_filters( 'wp_sir_trim_tolerance', (int)$settings[ 'trim_tolerance' ] );

            $image->trim( null, null, $tolerance );

            if ( $this->is_blank_image( $image) ) {
                    $this->retry_trim_imagick($image, $core);
            }

            $this->border_image($image, $feather, $color);

            if( is_object( $core ) ){
                $core->destroy();
            }

        } catch ( Exception $e ) {
        }

        // Change to new dimensions
        // or revert to original ones. 
        $this->set_new_dimensions( $image );

        return $image;
    }

    function retry_trim_imagick(&$image, $core_origin){

        if( ! ( Env::imagick_loaded() && $core_origin instanceof Imagick ) ){
            return;
        }

        $core_origin->trimImage( 0 );
        $core_origin->setImagePage( 0, 0, 0, 0 );
        $image->setCore( $core_origin );
    }

  
    private function border_image( &$image, $feather, $color )
    {
 
        if ( $feather && ! $this->is_blank_image( $image ) ) {
            if( $color ){
                $image->resizeCanvas( $feather, $feather, 'center', true, $color);
            }else{
                $image->resizeCanvas( $feather, $feather, 'center', true);
            }
        }
    }

    private function is_blank_image( $image)
    {
        $trimed_width  = max( 1, $image->getWidth());
        $trimed_height = max( 1, $image->getHeight());
        return ( $trimed_width === 1 || $trimed_height === 1 );
    }

}