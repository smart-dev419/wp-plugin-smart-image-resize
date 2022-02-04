<?php

namespace WP_Smart_Image_Resize\Image_Filters;

use Intervention\Image\Filters\FilterInterface;
use Intervention\Image\Image;

class Recanvas_Filter implements FilterInterface
{

    /**
     * Default image position.
     *
     * @var string DEFAULT_POSITION
     */
    protected $defaultPosition = 'center';

    /**
     * Available image positions.
     *
     * @var array POSITIONS
     */
    protected $supportedPositions = [
        'center',
        'top',
        'bottom',
        'left',
        'right',
        'bottom-right',
        'bottom-left',
        'top-right',
        'top-left'
    ];


    /**
     * Canvas width/height.
     *
     * @var array $size [width, height]
     */
    protected $size;

    /**
     * The image manager instance.
     *
     * @var \WP_Smart_Image_Resize\Image_Manager
     */
    protected $manager;

    public function __construct($manager, $size)
    {
        $this->size    = $size;
        $this->manager = $manager;
    }


    public function getCanvasColor()
    {
        return sanitize_hex_color(wp_sir_get_settings()['bg_color']) ?: null;
    }

    public function getImagePosition()
    {
        $position = strtolower(apply_filters('wp_sir_canvas_position', $this->defaultPosition));

        if (!in_array($position, $this->supportedPositions)) {
            $position = $this->defaultPosition;
        }

        return $position;
    }

    public function applyFilter(Image $image)
    {

        $canvas = $this->manager->canvas($this->size['width'], $this->size['height'], $this->getCanvasColor());

        return $canvas->insert($image, $this->getImagePosition());
    }
}
