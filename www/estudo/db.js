var DB = {
    "elements": {
        "nodes": [
            {
                "data": {
                    "id": "Credito",
                    "label": "Cr\u00e9dito",
                    "category": "business",
                    "type": "business",
                    "host": "users.example.com"
                },
                "classes": [
                    "node-category-business",
                    "node-type-business",
                    "node-status-unknown"
                ]
            },
            {
                "data": {
                    "id": "Pagamento",
                    "label": "Pagamento",
                    "category": "business",
                    "type": "business_case",
                    "host": "payments.example.com"
                },
                "classes": [
                    "node-category-business",
                    "node-type-business_case",
                    "node-status-unknown"
                ]
            },
            {
                "data": {
                    "id": "UserService",
                    "label": "User Service",
                    "category": "application",
                    "type": "service",
                    "host": "users.example.com"
                },
                "classes": [
                    "node-category-application",
                    "node-type-service",
                    "node-status-unknown"
                ]
            },
            {
                "data": {
                    "id": "AuthService",
                    "label": "Authentication Service",
                    "category": "application",
                    "type": "service",
                    "host": "auth.example.com"
                },
                "classes": [
                    "node-category-application",
                    "node-type-service",
                    "node-status-unknown"
                ]
            },
            {
                "data": {
                    "id": "UserDatabase",
                    "label": "User Database",
                    "category": "infrastructure",
                    "type": "database",
                    "host": "users.example.com"
                },
                "classes": [
                    "node-category-infrastructure",
                    "node-type-database",
                    "node-status-unknown"
                ]
            },
            {
                "data": {
                    "id": "Credito2",
                    "label": "Cr\u00e9dito",
                    "category": "business",
                    "type": "business",
                    "host": "users.example.com"
                },
                "classes": [
                    "node-category-business",
                    "node-type-business",
                    "node-status-unknown"
                ]
            },
            {
                "data": {
                    "id": "Pagamento2",
                    "label": "Pagamento",
                    "category": "business",
                    "type": "business_case",
                    "host": "payments.example.com"
                },
                "classes": [
                    "node-category-business",
                    "node-type-business_case",
                    "node-status-unknown"
                ]
            },
            {
                "data": {
                    "id": "UserService2",
                    "label": "User Service",
                    "category": "application",
                    "type": "service",
                    "host": "users.example.com"
                },
                "classes": [
                    "node-category-application",
                    "node-type-service",
                    "node-status-unknown"
                ]
            },
            {
                "data": {
                    "id": "AuthService2",
                    "label": "Authentication Service",
                    "category": "application",
                    "type": "service",
                    "host": "auth.example.com"
                },
                "classes": [
                    "node-category-application",
                    "node-type-service",
                    "node-status-unknown"
                ]
            },
            {
                "data": {
                    "id": "UserDatabase2",
                    "label": "User Database",
                    "category": "infrastructure",
                    "type": "database",
                    "host": "users.example.com"
                },
                "classes": [
                    "node-category-infrastructure",
                    "node-type-database",
                    "node-status-unknown"
                ]
            },
            {
                "data": {
                    "id": "BaseServer",
                    "label": "Base Server",
                    "category": "infrastructure",
                    "type": "server",
                    "host": "users.example.com"
                },
                "classes": [
                    "node-category-infrastructure",
                    "node-type-server",
                    "node-status-unknown"
                ]
            }
        ],
        "edges": [
            {
                "data": {
                    "id": "Credito-Pagamento",
                    "source": "Credito",
                    "target": "Pagamento",
                    "label": "Solicita",
                    "data": []
                }
            },
            {
                "data": {
                    "id": "Pagamento-UserService",
                    "source": "Pagamento",
                    "target": "UserService",
                    "label": "Solicita",
                    "data": []
                }
            },
            {
                "data": {
                    "id": "UserService-AuthService",
                    "source": "UserService",
                    "target": "AuthService",
                    "label": "Solicita",
                    "data": {
                        "method": "OAuth2"
                    }
                }
            },
            {
                "data": {
                    "id": "UserService-UserDatabase",
                    "source": "UserService",
                    "target": "UserDatabase",
                    "label": "Solicita",
                    "data": {
                        "method": "SQL"
                    }
                }
            },
            {
                "data": {
                    "id": "Credito2-Pagamento2",
                    "source": "Credito2",
                    "target": "Pagamento2",
                    "label": "Solicita",
                    "data": []
                }
            },
            {
                "data": {
                    "id": "Pagamento2-UserService2",
                    "source": "Pagamento2",
                    "target": "UserService2",
                    "label": "Solicita",
                    "data": []
                }
            },
            {
                "data": {
                    "id": "UserService2-AuthService2",
                    "source": "UserService2",
                    "target": "AuthService2",
                    "label": "Solicita",
                    "data": {
                        "method": "OAuth2"
                    }
                }
            },
            {
                "data": {
                    "id": "UserService2-UserDatabase2",
                    "source": "UserService2",
                    "target": "UserDatabase2",
                    "label": "Solicita",
                    "data": {
                        "method": "SQL"
                    }
                }
            },
            {
                "data": {
                    "id": "UserDatabase-BaseServer",
                    "source": "UserDatabase",
                    "target": "BaseServer",
                    "label": "Conecta",
                    "data": {
                        "method": "SSH"
                    }
                }
            },
            {
                "data": {
                    "id": "UserDatabase2-BaseServer",
                    "source": "UserDatabase2",
                    "target": "BaseServer",
                    "label": "Conecta",
                    "data": {
                        "method": "SSH"
                    }
                }
            }
        ]
    },
    "style": [
        {
            "selector": "node",
            "style": {
                "background-clip": "none",
                "background-height": "32px",
                "background-width": "32px",
                "border-width": 2,
                "color": "#333",
                "font-family": "Tahoma, Geneva, Verdana, sans-serif",
                "font-size": 16,
                "label": "data(label)",
                "text-valign": "bottom",
                "text-halign": "center",
                "text-margin-y": 8
            }
        },
        {
            "selector": "edge",
            "style": {
                "color": "#333",
                "font-family": "Tahoma, Geneva, Verdana, sans-serif",
                "font-size": 14,
                "label": "data(label)",
                "line-color": "#bebebe",
                "source-arrow-color": "#7d7d7d",
                "source-arrow-shape": "triangle",
                "source-arrow-fill": "filled",
                "source-arrow-width": 6,
                "source-endpoint": "outside-to-node-or-label",
                "source-distance-from-node": 5,
                "text-valign": "bottom",
                "text-halign": "center",
                "text-margin-y": 10,
                "width": 3,
                "curve-style": "bezier",
                "line-style": "dashed",
                "line-dash-pattern": [
                    6,
                    3
                ]
            }
        },
        {
            "selector": "node.node-status-unknown",
            "style": {
                "border-color": "#939393",
                "background-color": "#cbcbcb",
                "color": "#000000"
            }
        },
        {
            "selector": "node.node-status-healthy",
            "style": {
                "border-color": "#4CAF50",
                "background-color": "#d0edd1",
                "color": "#000000"
            }
        },
        {
            "selector": "node.node-status-unhealthy",
            "style": {
                "border-color": "#ff8178",
                "background-color": "#ffe2e2",
                "color": "#000000"
            }
        },
        {
            "selector": "node.node-status-maintenance",
            "style": {
                "border-color": "#43aeff",
                "background-color": "#cde9ff",
                "color": "#000000"
            }
        },
        {
            "selector": "node.node-status-impacted",
            "style": {
                "border-color": "#ae6ec0",
                "background-color": "#ece5ee",
                "color": "#000000"
            }
        },
        {
            "selector": "node.node-type-api",
            "style": {
                "background-image": "\/image.php?img=api"
            }
        },
        {
            "selector": "node.node-type-business",
            "style": {
                "background-image": "\/image.php?img=business"
            }
        },
        {
            "selector": "node.node-type-business_case",
            "style": {
                "background-image": "\/image.php?img=business_case"
            }
        },
        {
            "selector": "node.node-type-database",
            "style": {
                "background-image": "\/image.php?img=database"
            }
        },
        {
            "selector": "node.node-type-server",
            "style": {
                "background-image": "\/image.php?img=server"
            }
        },
        {
            "selector": "node.node-type-service",
            "style": {
                "background-image": "\/image.php?img=service"
            }
        },
        {
            "selector": "node.node-category-business",
            "style": {
                "shape": "round-rectangle",
                "width": 50,
                "height": 50
            }
        },
        {
            "selector": "node.node-category-application",
            "style": {
                "shape": "ellipse",
                "width": 60,
                "height": 60
            }
        },
        {
            "selector": "node.node-category-infrastructure",
            "style": {
                "shape": "round-hexagon",
                "width": 60,
                "height": 53
            }
        },
        {
            "selector": "node:active",
            "style": {
                "border-width": 4,
                "border-color": "#ffec7f",
                "overlay-color": "#FFF",
                "overlay-opacity": 0,
                "outline-width": "5",
                "outline-style": "solid",
                "outline-color": "rgb(255, 255, 229)",
                "outline-opacity": "1",
                "outline-offset": "5"
            }
        }
    ],
    "layout": {
        "fit": false,
        "name": "breadthfirst",
        "directed": true,
        "direction": "downward",
        "padding": 100,
        "avoidOverlap": true,
        "animate": true,
        "animationDuration": 500
    },
    "zoom": 1,
    "pan": {
        "x": 0,
        "y": 0
    }
};