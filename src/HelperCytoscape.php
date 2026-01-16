<?php

declare(strict_types=1);

final class HelperCytoscape
{
    private HelperImages $imagesHelper;

    private const KEYNAME_ELEMENTS = "elements";
    private const KEYNAME_STYLES = "style";
    private const KEYNAME_LAYOUT = "layout";
    private const KEYNAME_ZOOM = "zoom";
    private const KEYNAME_PAN = "pan";
    private const KEYNAME_PANX = "x";
    private const KEYNAME_PANY = "y";

    private const SHAPES = [
        ["shape" => "ellipse",   "width" => 80, "height" => 80],
        ["shape" => "rectangle", "width" => 80, "height" => 80],
        ["shape" => "diamond",   "width" => 80, "height" => 80],
        ["shape" => "pentagon",  "width" => 80, "height" => 80],
        ["shape" => "hexagon",   "width" => 80, "height" => 80],
        ["shape" => "heptagon",  "width" => 80, "height" => 80],
        ["shape" => "octagon",   "width" => 80, "height" => 80],
    ];

    public function __construct(HelperImages $imagesHelper)
    {
        $this->imagesHelper = $imagesHelper;
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
            $shape = $this->getNodeShape($index);
            $node = array_merge($node);
            $nodes[] = [
                "data" => array_merge([
                    ModelNode::NODE_KEYNAME_ID => $node[ModelNode::NODE_KEYNAME_ID],
                    ModelNode::NODE_KEYNAME_LABEL => $node[ModelNode::NODE_KEYNAME_LABEL],
                    ModelNode::NODE_KEYNAME_CATEGORY => $node[ModelNode::NODE_KEYNAME_CATEGORY],
                    ModelNode::NODE_KEYNAME_TYPE => $node[ModelNode::NODE_KEYNAME_TYPE],
                ], $node["data"]),
                "classes" => ["unknown-status-node"],
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
                    "background-color" => "#61bffc",
                    "label" => "data(label)",
                    "text-valign" => "center",
                    "color" => "#000000",
                    "text-outline-width" => 0,
                    "width" => "data(width)",
                    "height" => "data(height)",
                    "shape" => "data(shape)",
                ],
            ],
            [
                "selector" => "edge",
                "style" => [
                    "width" => 2,
                    "line-color" => "#ccc",
                    "target-arrow-color" => "#ccc",
                    "target-arrow-shape" => "triangle",
                    "curve-style" => "bezier",
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
                "line-color" => "#ccc",
                "background-color" => "#f0f0f0",
                "color" => "#000000",
            ],
        ];
        
        $style[] = [
            "selector" => "node.node-status-healthy",
            "style" => [
                "line-color" => "#4CAF50",
                "background-color" => "#A5D6A7",
                "color" => "#000000",
            ],
        ];

        $style[] = [
            "selector" => "node.node-status-unhealthy",
            "style" => [
                "line-color" => "#F44336",
                "background-color" => "#EF9A9A",
                "color" => "#000000",
            ],
        ];
        
        $style[] = [
            "selector" => "node.node-status-maintenance",
            "style" => [
                "line-color" => "#FF9800",
                "background-color" => "#FFCC80",
                "color" => "#000000",
            ],
        ];

        $types = $this->imagesHelper->getTypes();
        foreach($types as $type) {
            $style[] = [
                "selector" => "node.node-type-{$type}",
                "style" => [
                    "background-image" => $type,
                    "background-fit" => "contain",
                    "background-clip" => "none",
                ],
            ];
        }
        
        $style[] = [
            "selector" => "node:selected",
            "style" => [
                "border-width" => 4,
                "border-color" => "#FFD700",
            ],
        ];

        return $style;
    }

    private function getLayout(): array
    {
        return [
            "name" => "grid",
            "rows" => 5,
        ];
    }

    private function getNodeShape(int $index): array
    {
        return self::SHAPES[$index % count(self::SHAPES)];
    }
}