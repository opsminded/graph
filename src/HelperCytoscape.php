<?php

declare(strict_types=1);

final class HelperCytoscape
{
    private HelperImages $imagesHelper;

    private const SHAPES = [
        ['shape' => 'ellipse',   'width' => 80, 'height' => 80],
        ['shape' => 'rectangle', 'width' => 80, 'height' => 80],
        ['shape' => 'diamond',   'width' => 80, 'height' => 80],
        ['shape' => 'pentagon',  'width' => 80, 'height' => 80],
        ['shape' => 'hexagon',   'width' => 80, 'height' => 80],
        ['shape' => 'heptagon',  'width' => 80, 'height' => 80],
        ['shape' => 'octagon',   'width' => 80, 'height' => 80],
    ];

    public function __construct(HelperImages $imagesHelper)
    {
        $this->imagesHelper = $imagesHelper;
    }

    public function toArray(ModelGraph $graph): array
    {
        return [
            'elements' => [
                'nodes' => [],
                'edges' => [],
            ],

            'style' => [],

            'layout' => [
                'name' => 'grid',
            ],

            'zoom' => 1,

            'pan' => [
                'x' => 0,
                'y' => 0,
            ],

            'userZoomingEnabled' => true,
            'userPanningEnabled' => true,
        ];
    }

    private function getNodeShape(int $index): array
    {
        return self::SHAPES[$index % count(self::SHAPES)];
    }
}