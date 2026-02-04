"use strict";

import {Api} from "../api.js";
import cytoscape from "/javascript/libs/cytoscape.esm.min.mjs";
import {InfoPanel} from "./info-panel.js";

export class Project extends HTMLElement
{
    static observedAttributes = ["project", "graph", "node-status"];
    
    constructor()
    {
        super();
        this.api = new Api();
        this.cy = null;
        this.selectedNodes = [];
        this.selectedEdge = null;
        
        // AbortController for automatic event listener cleanup
        this.abortController = new AbortController();
        this.render();
    }

    connectedCallback() {
        this.projectTitle     = this.shadowRoot.getElementById('project-title');
        this.importNodeButton = this.shadowRoot.getElementById('import-node-btn');
        this.addNodeButton    = this.shadowRoot.getElementById('add-node-btn');
        this.addEdgeButton    = this.shadowRoot.getElementById('add-edge-btn');
        this.infoPanel        = this.shadowRoot.querySelector('app-info-panel');

        this.importNodeModal    = this.shadowRoot.getElementById('import-node-modal');
        this.importNodeForm     = this.shadowRoot.getElementById('import-node-form');
        this.importNodeCategory = this.shadowRoot.getElementById('import-node-category');
        this.importNodeType     = this.shadowRoot.getElementById('import-node-type');
        this.importNodeNode     = this.shadowRoot.getElementById('import-node-node');
        this.importNodeFormCancelButton = this.shadowRoot.getElementById('cancel-import-node');
        
        this.addNodeModal = this.shadowRoot.getElementById('add-node-modal');
        this.addNodeForm  = this.shadowRoot.getElementById('add-node-form');
        this.addNodeFormCancelButton = this.shadowRoot.getElementById('cancel-add-node');

        this.removeNodeModal = this.shadowRoot.getElementById('remove-node-modal');
        this.removeEdgeModal = this.shadowRoot.getElementById('remove-edge-modal');

        this.removeNodeForm = this.shadowRoot.getElementById('remove-node-form');
        this.removeEdgeForm = this.shadowRoot.getElementById('remove-edge-form');

        this.removeNodeButton = this.shadowRoot.getElementById('remove-node-btn');
        this.removeEdgeButton = this.shadowRoot.getElementById('remove-edge-btn');

        this.cyContainer   = this.shadowRoot.getElementById('cy');

        this.importNodeButton.addEventListener('click', () => {
            this.importNodeModal.style.display = 'block';

            this.api.fetchCategories().then(categories => {
                console.log("categories", categories);
                this.importNodeCategory.innerHTML = '<option value="" disabled selected>Selecione uma categoria</option>';
                categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    this.importNodeCategory.appendChild(option);
                });
                this.importNodeType.innerHTML = '<option value="" disabled selected>Selecione um tipo</option>';
                this.importNodeNode.innerHTML = '<option value="" disabled selected>Selecione um item</option>';
            }).catch(error => {
                console.error("Error fetching categories:", error);
            });

        }, this.abortController.signal);

        this.importNodeCategory.addEventListener('change', () => {
            const categoryId = this.importNodeCategory.value;
            this.api.fetchCategoryTypes(categoryId).then(types => {
                console.log("types", types);
                this.importNodeType.innerHTML = '<option value="" disabled selected>Selecione um tipo</option>';
                types.forEach(type => {
                    const option = document.createElement('option');
                    option.value = type.id;
                    option.textContent = type.name;
                    this.importNodeType.appendChild(option);
                });
                this.importNodeNode.innerHTML = '<option value="" disabled selected>Selecione um item</option>';
            }).catch(error => {
                console.error("Error fetching types:", error);
            });
        }, this.abortController.signal);

        this.importNodeType.addEventListener('change', () => {
            const typeId = this.importNodeType.value;
            this.api.fetchTypeNodes(typeId).then(nodes => {
                console.log("nodes", nodes);
                this.importNodeNode.innerHTML = '<option value="" disabled selected>Selecione um item</option>';
                nodes.forEach(node => {
                    const option = document.createElement('option');
                    option.value = node.id;
                    option.textContent = node.label;
                    this.importNodeNode.appendChild(option);
                });
            }).catch(error => {
                console.error("Error fetching nodes:", error);
            });
        }, this.abortController.signal);

        this.importNodeFormCancelButton.addEventListener('click', () => {
            this.importNodeForm.reset();
            this.importNodeModal.style.display = 'none';
        }, this.abortController.signal);

        this.addNodeButton.addEventListener('click', () => {
            this.addNodeModal.style.display = 'block';
        }, this.abortController.signal);

        this.addEdgeButton.addEventListener('click', () => {
            
            const edge = {
                project: this.project.id,
                label: 'connects_to',
                source: this.selectedNodes[0],
                target: this.selectedNodes[1],
                data: {}
            };

            this.api.insertEdge(edge).then((newEdge) => {
                alert(`Ligação "${newEdge.id}" criada com sucesso!`);
            }).catch(error => {
                alert(`Erro ao criar ligação: ${error.message}`);
            });

            this.selectedNodes = [];
            this.addEdgeButton.style.display = "none";
            this.cy.elements().unselect();
        }, this.abortController.signal);

        this.importNodeForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const formData = new FormData(this.importNodeForm);
            
            const nodeData = {
                project_id: this.project.id,
                node_id: formData.get('import-node-node'),
            };

            console.log("Importing node with data:", JSON.stringify(nodeData));

            this.api.insertProjectNode(nodeData).then((node) => {
                alert(`Item importado com sucesso!`);
            }).catch(error => {
                alert(`Erro ao importar item: ${error.message}`);
            });
            
            this.importNodeModal.style.display = 'none';
            this.importNodeForm.reset();
        }, this.abortController.signal);

        this.addNodeForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const formData = new FormData(this.addNodeForm);

            const nodeData = {
                id: formData.get('node-id'),
                label: formData.get('node-label'),
                category: formData.get('node-category'),
                type: formData.get('node-type'),
                data: {}
            };

            this.api.insertNode(nodeData).then((newNode) => {
                alert(`Item "${newNode.label}" criado com sucesso!`);
            }).catch(error => {
                alert(`Erro ao criar item: ${error.message}`);
            });
            
            this.addNodeModal.style.display = 'none';
            this.addNodeForm.reset();
        }, this.abortController.signal);

        this.addNodeFormCancelButton.addEventListener('click', () => {
            this.addNodeForm.reset();
            this.addNodeModal.style.display = 'none';
        }, this.abortController.signal);

        this.removeNodeButton.addEventListener('click', () => {
            this.removeNodeModal.style.display = 'block';
            if (this.selectedNodes.length === 1) {
                this.removeNodeForm.querySelector('#remove-node-id').value = this.selectedNodes[0];
            }
        }, this.abortController.signal);

        this.removeEdgeButton.addEventListener('click', () => {
            this.removeEdgeModal.style.display = 'block';
            if (this.selectedEdge) {
                this.removeEdgeForm.querySelector('#remove-edge-id').value = this.selectedEdge;
            }
        }, this.abortController.signal);

        this.removeNodeForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(this.removeNodeForm);
            const nodeId = formData.get('remove-node-id');
            console.log("Removing node:", nodeId);
            this.removeNodeModal.style.display = 'none';
            this.removeNodeForm.reset();
            this.dispatchEvent(new CustomEvent('node-removed', {
                detail: {id: nodeId},
                bubbles: true,
                composed: true,
            }));
        }, this.abortController.signal);

        this.removeEdgeForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(this.removeEdgeForm);
            const edgeId = formData.get('remove-edge-id');
            console.log("Removing edge:", edgeId);
            this.removeEdgeModal.style.display = 'none';
            this.removeEdgeForm.reset();
            this.dispatchEvent(new CustomEvent('edge-removed', {
                detail: {id: edgeId},
                bubbles: true,
                composed: true,
            }));
        }, this.abortController.signal);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.cy) {
                this.cy.elements().unselect();
                this.selectedNodes = [];
                this.selectedEdge = null;
                this.infoPanel.node = null;
            }
        }, this.abortController.signal);
    }

    disconnectedCallback() {
        this.abortController.abort();
    }

    set project(value)
    {
        this.setAttribute("project", JSON.stringify(value));
        if (value === null) {
            this.selectedNodes = [];
            this.selectedEdge = null;
            this.cy.destroy();
            this.cy = null;
            this.infoPanel.node = null;
            this.projectTitle.textContent = "";
            this.importNodeButton.style.display = "none";
            this.addNodeButton.style.display = "none";
            this.addEdgeButton.style.display = "none";
            return;
        }
        this.projectTitle.textContent = value.name;
        this.importNodeButton.style.display = "inline-block";
        this.addNodeButton.style.display = "inline-block";
    }

    get project()
    {
        const data = JSON.parse(this.getAttribute("project"));
        if (data === null || data === undefined || data === "") {
            return null;
        }
        return data;
    }

    set graph(value)
    {
        this.setAttribute("graph", JSON.stringify(value));

        // Destroy existing instance if it exists
        if (this.cy) {
            this.cy.destroy();
        }

        // Initialize Cytoscape

        value.container = this.cyContainer;
        this.cy = cytoscape(value);

        // Setup Cytoscape event listeners after initialization
        this.setupCytoscapeEvents();
    }

    get graph()
    {
        const data = JSON.parse(this.getAttribute("graph"));
        if (data === null || data === undefined || data === "") {
            return null;
        }
        
        return data;
    }

    set nodeStatus(statusUpdates)
    {
        this.setAttribute("node-status", JSON.stringify(statusUpdates));

        statusUpdates.forEach(update => {
            const node = this.cy.$('#' + update.node_id);
            
            if (node.length > 0) {
                // Remove classes de status anteriores
                let classes = node.classes();
                classes.forEach(cls => {
                    if (cls.startsWith("node-status")) {
                        node.removeClass(cls);
                    }
                });

                // Adiciona nova classe de status
                node.addClass(`node-status-${update.status}`);
            }
        });
    }
    
    get nodeStatus()
    {
        const data = JSON.parse(this.getAttribute("node-status"));
        if (data === null || data === undefined || data === "") {
            return null;
        }
        return data;
    }

    render()
    {
        this.attachShadow({ mode: "open" });
        this.shadowRoot.innerHTML = `
            <style>
                #cy {
                    position: absolute;

                    left: 250px;
                    top: 0;
                    bottom: 0;
                    right: 0;

                    width: 100%;
                    height: 100%;
                    
                    z-index: 100;
                }

                #buttons-container {
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    width: 500px;
                    z-index: 101;
                    text-align: right;
                }

                #buttons-container button {
                    margin: 0 10px;
                    display: none;
                }

                #project-container h2 {
                    position: absolute;
                    left: 320px;
                    top: 10px;
                    z-index: 101;
                }

                #import-node-modal,
                #add-node-modal,
                #remove-node-modal,
                #remove-edge-modal {
                    position: absolute;

                    border: 2px solid #CCC;
                    background-color: #fff;

                    left: 25%;
                    top: 8%;
                    width: 50%;
                    height: 70%;

                    padding: 10px;

                    display: none;
                    z-index: 200;
                }
            </style>
            <div id="project-container">
                <h2 id="project-title"></h2>

                <div id="buttons-container">
                    <button id="import-node-btn">Importar Item</button>
                    <button id="add-node-btn">Novo Item</button>
                    <button id="remove-node-btn">Remover Item</button>
                    <button id="add-edge-btn">Nova Ligação</button>
                    <button id="remove-edge-btn">Remover Ligação</button>
                </div>

                <div id="cy"></div>
                <app-info-panel></app-info-panel>

                <div id="import-node-modal">
                    <form id="import-node-form">
                        <label for="import-node-category">Categoria do Item:</label>
                        <select id="import-node-category" name="import-node-category">
                            <!-- Options will be populated dynamically -->
                        </select>
                        <br>

                        <label for="import-node-type">Tipo do Item:</label>
                        <select id="import-node-type" name="import-node-type">
                            <!-- Options will be populated dynamically -->
                        </select>

                        <label for="import-node-node">Node ID:</label>
                        <select id="import-node-node" name="import-node-node">
                            <!-- Options will be populated dynamically -->
                        </select>
                        <br>

                        <button type="submit">Importar Item</button>
                        <button type="button" id="cancel-import-node">Cancelar</button>
                    </form>
                </div>

                <div id="add-node-modal">
                    <form id="add-node-form">
                        
                        <label for="node-id">Node ID:</label>
                        <input type="text" id="node-id" name="node-id" required>
                        <br>

                        <label for="node-label">Node Label:</label>
                        <input type="text" id="node-label" name="node-label" required>
                        <br>

                        <label for="node-category">Node Category:</label>
                        <input type="text" id="node-category" name="node-category">
                        <br>

                        <label for="node-type">Node Type:</label>
                        <input type="text" id="node-type" name="node-type">
                        <br>
                        
                        <button type="submit">Add Node</button>
                        <button type="button" id="cancel-add-node">Cancel</button>
                    </form>
                </div>

                <div id="remove-node-modal">
                    <form id="remove-node-form">
                        <label for="remove-node-id">Node ID:</label>
                        <input type="text" id="remove-node-id" name="remove-node-id" required>
                        <br>
                        
                        <button type="submit">Remove Node</button>
                        <button type="button" id="cancel-remove-node">Cancel</button>
                    </form>
                </div>

                <div id="remove-edge-modal">
                    <form id="remove-edge-form">
                        <label for="remove-edge-id">Edge ID:</label>
                        <input type="text" id="remove-edge-id" name="remove-edge-id" required>
                        <br>

                        <button type="submit">Remove Node</button>
                        <button type="button" id="cancel-remove-node">Cancel</button>
                    </form>
                </div>
            </div>
        `;
    }

    /**
     * Configura todos os event listeners do Cytoscape.
     * Deve ser chamado após a inicialização do cy.
     */
    setupCytoscapeEvents() {
        if (!this.cy) {
            console.warn("Cytoscape not initialized");
            return;
        }

        // Evento: Seleção de nó
        this.cy.on('select', 'node', (e) => {
            const n = e.target;
            this.selectedNodes.push(n.id());

            console.log("Selected nodes:", this.selectedNodes);

            if (this.selectedNodes.length === 1) {
                this.removeNodeButton.style.display = 'inline-block';
            } else {
                this.removeNodeButton.style.display = 'none';
            }

            if (this.selectedNodes.length === 2) {
                // Dois nós selecionados, pronto para adicionar aresta
                console.log("Ready to add edge between:", this.selectedNodes[0], "and", this.selectedNodes[1]);
                this.addEdgeButton.style.display = "inline-block";
            }
            
            if (this.selectedNodes.length > 2) {
                this.addEdgeButton.style.display = "none";
                this.selectedNodes = [];
                this.cy.elements().unselect();
            }
        });

        // Evento: Seleção de aresta
        this.cy.on('select', 'edge', (e) => {
            const edge = e.target;
            this.selectedEdge = edge.id();
            this.removeEdgeButton.style.display = 'inline-block';
            console.log("Selected edge:", this.selectedEdge);
        });

        // Evento: Deseleção de nó
        this.cy.on('unselect', 'node', () => {
            this.selectedNodes = [];
            this.removeNodeButton.style.display = 'none';
        });

        // Evento: Deseleção de aresta
        this.cy.on('unselect', 'edge', () => {
            this.selectedEdge = null;
            this.addEdgeButton.style.display = "none";
            this.removeEdgeButton.style.display = 'none';
        });

        // Evento: Duplo clique em nó (para mostrar info)
        this.cy.on('dbltap', 'node', (e) => {
            const node = e.target;
            this.infoPanel.node = node.data();
            this.removeNodeButton.style.display = 'none';
        });

        // Evento: Clique no background (deseleciona tudo)
        this.cy.on('tap', (e) => {
            if (e.target === this.cy) {
                this.cy.elements().unselect();
                this.infoPanel.node = null;
                this.addEdgeButton.style.display = "none";
                this.removeNodeButton.style.display = 'none';
                this.removeEdgeButton.style.display = 'none';
            }
        });
    }

    export() {
        if (!this.cy) return;

        let pngData = this.cy.png({
            full: true,
        });
        
        let link = document.createElement('a');
        link.href = pngData;
        link.download = 'graph.png';
        link.click();
    }

    fit() {
        if (this.cy) {
            this.cy.fit();
        }
    }
}

customElements.define('app-project', Project);
