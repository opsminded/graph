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
                'nodes' => $this->getNodes($graph),
                'edges' => $this->getEdges($graph),
            ],

            'style' => $this->getStyle(),

            'layout' => $this->getLayout(),

            'zoom' => 1,

            'pan' => [
                'x' => 0,
                'y' => 0,
            ],
        ];
    }

    private function getNodes(ModelGraph $graph): array
    {
        $graphArr = $graph->toArray();
        $nodes = [];
        foreach ($graphArr['nodes'] as $index => $node) {
            $node = $node->toArray();
            $shape = $this->getNodeShape($index);
            $node = array_merge($node, $shape);
            $nodes[] = [
                'data' => array_merge([
                    'id' => $node['id'],
                    'label' => $node['label'],
                    'category' => $node['category'],
                    'type' => $node['type'],
                    'shape' => $node['shape'],
                    'width' => $node['width'],
                    'height' => $node['height'],
                ], $node['data']),
                'classes' => ["unknown-status-node"],
            ];
        }
        return $nodes;
    }

    private function getEdges(ModelGraph $graph): array
    {
        $edgesArr = $graph->toArray();
        $edges = [];
        foreach ($edgesArr['edges'] as $edge) {
            $edge = $edge->toArray();
            $edges[] = [
                'data' => [
                    'id' => $edge['id'],
                    'source' => $edge['source'],
                    'target' => $edge['target'],
                ]
            ];
        }
        return $edges;
    }

    private function getStyle(): array
    {
        $baseStyle = [
            [
                'selector' => 'node',
                'style' => [
                    'background-color' => '#61bffc',
                    'label' => 'data(label)',
                    'text-valign' => 'center',
                    'color' => '#000000',
                    'text-outline-width' => 0,
                    'width' => 'data(width)',
                    'height' => 'data(height)',
                    'shape' => 'data(shape)',
                ],
            ],
            [
                'selector' => 'edge',
                'style' => [
                    'width' => 2,
                    'line-color' => '#ccc',
                    'target-arrow-color' => '#ccc',
                    'target-arrow-shape' => 'triangle',
                    'curve-style' => 'bezier',
                ],
            ]
        ];

        $nodeStyles = $this->getNodeStyles();

        return array_merge($baseStyle, $nodeStyles);
    }

    private function getNodeStyles(): array
    {
        // possible status:
        // unknown-status-node
        // healthy-status-node
        // unhealthy-status-node
        // maintenance-status-node
        $style = [];

        $style[] = [
            'selector' => 'node.unknown-status-node',
            'style' => [
                'line-color' => '#ccc',
                'background-color' => '#f0f0f0',
                'color' => '#000000',
            ],
        ];
        
        $style[] = [
            'selector' => 'node.healthy-status-node',
            'style' => [
                'line-color' => '#4CAF50',
                'background-color' => '#A5D6A7',
                'color' => '#000000',
            ],
        ];

        $style[] = [
            'selector' => 'node.unhealthy-status-node',
            'style' => [
                'line-color' => '#F44336',
                'background-color' => '#EF9A9A',
                'color' => '#000000',
            ],
        ];
        
        $style[] = [
            'selector' => 'node.maintenance-status-node',
            'style' => [
                'line-color' => '#FF9800',
                'background-color' => '#FFCC80',
                'color' => '#000000',
            ],
        ];

        $style[] = [
            'selector' => 'node:selected',
            'style' => [
                'border-width' => 4,
                'border-color' => '#FFD700',
            ],
        ];

        return $style;
    }

    private function getLayout(): array
    {
        return [
            'name' => 'grid',
            'rows' => 5,
        ];
    }

    private function getNodeShape(int $index): array
    {
        return self::SHAPES[$index % count(self::SHAPES)];
    }
}