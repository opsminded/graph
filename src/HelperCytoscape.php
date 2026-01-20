<?php

declare(strict_types=1);

final class HelperCytoscape
{
    private DatabaseInterface $database;
    private HelperImages $imagesHelper;

    private const KEYNAME_ELEMENTS = "elements";
    private const KEYNAME_STYLES = "style";
    private const KEYNAME_LAYOUT = "layout";
    private const KEYNAME_ZOOM = "zoom";
    private const KEYNAME_PAN = "pan";
    private const KEYNAME_PANX = "x";
    private const KEYNAME_PANY = "y";

    private string $imageBaseUrl = "";

    private array $categories;

    public function __construct(DatabaseInterface $database, HelperImages $imagesHelper, string $imageBaseUrl)
    {
        $this->database = $database;
        $this->categories = $this->database->getCategories();
        $this->imagesHelper = $imagesHelper;
        $this->imageBaseUrl = $imageBaseUrl;
    }

    public function toArray(ModelGraph $graph): array
    {
        return [
            self::KEYNAME_ELEMENTS => [
                ModelGraph::KEYNAME_NODES => $this->getNodes($graph),
                ModelGraph::KEYNAME_EDGES => $this->getEdges($graph),
            ],

            self::KEYNAME_STYLES => $this->getStyle(),

            self::KEYNAME_LAYOUT => $this->getLayout(),

            self::KEYNAME_ZOOM => 1,

            self::KEYNAME_PAN => [
                self::KEYNAME_PANX => 0,
                self::KEYNAME_PANY => 0,
            ],
        ];
    }

    private function getNodes(ModelGraph $graph): array
    {
        $graphArr = $graph->toArray();
        $nodes = [];
        foreach ($graphArr[ModelGraph::KEYNAME_NODES] as $index => $node) {
            $node = $node->toArray();
            $nodes[] = [
                "data" => array_merge([
                    ModelNode::NODE_KEYNAME_ID => $node[ModelNode::NODE_KEYNAME_ID],
                    ModelNode::NODE_KEYNAME_LABEL => $node[ModelNode::NODE_KEYNAME_LABEL],
                    ModelNode::NODE_KEYNAME_CATEGORY => $node[ModelNode::NODE_KEYNAME_CATEGORY],
                    ModelNode::NODE_KEYNAME_TYPE => $node[ModelNode::NODE_KEYNAME_TYPE],
                ], $node["data"]),
                "classes" => [
                    "node-category-".$node[ModelNode::NODE_KEYNAME_CATEGORY],
                    "node-type-".$node[ModelNode::NODE_KEYNAME_TYPE],
                    "node-status-unknown",
                ],
            ];
        }

        return $nodes;
    }

    private function getEdges(ModelGraph $graph): array
    {
        $edgesArr = $graph->toArray();
        $edges = [];
        foreach ($edgesArr[ModelGraph::KEYNAME_EDGES] as $edge) {
            $edge = $edge->toArray();
            $edges[] = [
                "data" => [
                    ModelEdge::EDGE_KEYNAME_ID     => $edge[ModelEdge::EDGE_KEYNAME_ID],
                    ModelEdge::EDGE_KEYNAME_SOURCE => $edge[ModelEdge::EDGE_KEYNAME_SOURCE],
                    ModelEdge::EDGE_KEYNAME_TARGET => $edge[ModelEdge::EDGE_KEYNAME_TARGET],
                    ModelEdge::EDGE_KEYNAME_LABEL  => $edge[ModelEdge::EDGE_KEYNAME_LABEL],
                    ModelEdge::EDGE_KEYNAME_DATA   => $edge[ModelEdge::EDGE_KEYNAME_DATA],
                ]
            ];
        }
        return $edges;
    }

    private function getStyle(): array
    {
        $baseStyle = [
            [
                "selector" => "node",
                "style" => [
                    "background-clip" => "none",
                    "background-height" => "32px",
                    "background-width" => "32px",
                    "border-width" => 2,
                    "color" => "#333",
                    "font-family" => "Tahoma, Geneva, Verdana, sans-serif",
                    "font-size" => 16,
                    "label" => "data(label)",
                    "text-valign" => "bottom",
                    "text-halign" => "center",
                    "text-margin-y" => 8,
                ],
            ],
            [
                "selector" => "edge",
                "style" => [
                    "color" => "#333",
                    "font-family" => "Tahoma, Geneva, Verdana, sans-serif",
                    "font-size" => 14,
                    "label" => "data(label)",
                    "line-color" => "#bebebe",
                    "source-arrow-color" => "#7d7d7d",
                    "source-arrow-shape" => "triangle",
                    "source-arrow-fill" => "filled",
                    "source-arrow-width" => 6,
                    "source-endpoint" => "outside-to-node-or-label",
                    "source-distance-from-node" => 5,
                    "text-valign" => "bottom",
                    "text-halign" => "center",
                    "text-margin-y" => 10,
                    "width" => 3,
                    'curve-style' => 'bezier',

                    "line-style" => 'dashed',
                    'line-dash-pattern'  => [6, 3],
                    //'line-color' => '#00ff00',
                    //'animation' => 'pulse 1s infinite'
                ],
            ]
        ];

        $nodeStyles = $this->getNodeStyles();

        return array_merge($baseStyle, $nodeStyles);
    }

    private function getNodeStyles(): array
    {
        $style = [];

        $style[] = [
            "selector" => "node.node-status-unknown",
            "style" => [
                "border-color" => "#939393",
                "background-color" => "#cbcbcb",
                "color" => "#000000",
            ],
        ];
        
        $style[] = [
            "selector" => "node.node-status-healthy",
            "style" => [
                "border-color" => "#4CAF50",
                "background-color" => "#d0edd1",
                "color" => "#000000",
            ],
        ];

        $style[] = [
            "selector" => "node.node-status-unhealthy",
            "style" => [
                "border-color" => "#ff8178",
                "background-color" => "#ffe2e2",
                "color" => "#000000",
            ],
        ];
        
        $style[] = [
            "selector" => "node.node-status-maintenance",
            "style" => [
                "border-color" => "#43aeff",
                "background-color" => "#cde9ff",
                "color" => "#000000",
            ],
        ];

        $style[] = [
            "selector" => "node.node-status-impacted",
            "style" => [
                "border-color" => "#ae6ec0",
                "background-color" => "#ece5ee",
                "color" => "#000000",
            ],
        ];

        $types = $this->imagesHelper->getTypes();
        foreach($types as $type) {
            $style[] = [
                "selector" => "node.node-type-{$type}",
                "style" => [
                    "background-image" => "{$this->imageBaseUrl}?img={$type}",
                ],
            ];
        }
        
        foreach($this->categories as $category) {
            $style[] = [
                "selector" => "node.node-category-" . $category['id'],
                "style" => [
                    "shape" => $category['shape'],
                    "width" => $category['width'],
                    "height" => $category['height'],
                ],
            ];
        }

        $style[] = [
            "selector" => "node:active",
            "style" => [
                "border-width" => 4,
                "border-color" => "#ffec7f",

                "overlay-color" => "#FFF",
                "overlay-opacity" => 0,

                "outline-width"   => "5",
                "outline-style"   => "solid",
                "outline-color"   => "rgb(255, 255, 229)",
                "outline-opacity" => "1",
                "outline-offset"  => "5",
            ],
        ];
        
        $style[] = [
            "selector" => "node:selected",
            "style" => [
                "border-width" => 4,
                "border-color" => "#ffe658",
            ],
        ];

        return $style;
    }

    private function getLayout(): array
    {
        return [
            "fit"               => true,
            "name"              => "breadthfirst",
            "directed"          => true,
            "direction"         => "downward",
            "padding"           => 100,
            "avoidOverlap"      => true,
            "animate"           => true,
            "animationDuration" => 500,
        ];
    }
}