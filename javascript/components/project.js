"use strict";

import cytoscape from "/javascript/libs/cytoscape.esm.min.mjs";
import {InfoPanel} from "./info-panel.js";

export class Project extends HTMLElement
{
    static observedAttributes = ["project", "graph", "node-status"];
    
    constructor()
    {
        super();
        this.cy = null;
        this.selectedNodes = [];
        this.selectedEdge = null;
        
        // AbortController for automatic event listener cleanup
        this.abortController = new AbortController();
        this.render();
    }

    connectedCallback() {
        console.log("Project connected");
        this.projectTitle     = this.shadowRoot.getElementById('project-title');
        this.importNodeButton = this.shadowRoot.getElementById('import-node-btn');
        this.addNodeButton    = this.shadowRoot.getElementById('add-node-btn');
        this.addEdgeButton    = this.shadowRoot.getElementById('add-edge-btn');
        this.infoPanel        = this.shadowRoot.querySelector('app-info-panel');

        this.importNodeModal = this.shadowRoot.getElementById('import-node-modal');
        this.importNodeForm  = this.shadowRoot.getElementById('import-node-form');
        this.importNodeFormCancelButton = this.shadowRoot.getElementById('cancel-import-node');
        
        this.addNodeModal = this.shadowRoot.getElementById('add-node-modal');
        this.addNodeForm  = this.shadowRoot.getElementById('add-node-form');
        this.addNodeFormCancelButton = this.shadowRoot.getElementById('cancel-add-node');

        this.cyContainer   = this.shadowRoot.getElementById('cy');

        this.importNodeButton.addEventListener('click', () => {
            this.importNodeModal.style.display = 'block';
        }, this.abortController.signal);

        this.importNodeFormCancelButton.addEventListener('click', () => {
            this.importNodeForm.reset();
            this.importNodeModal.style.display = 'none';
        }, this.abortController.signal);

        this.addNodeButton.addEventListener('click', () => {
            this.addNodeModal.style.display = 'block';
        }, this.abortController.signal);

        this.addEdgeButton.addEventListener('click', () => {
            this.dispatchEvent(new CustomEvent('add-edge-btn-clicked', {
                bubbles: true,
                composed: true,
            }));
        }, this.abortController.signal);

        this.addNodeForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const formData = new FormData(this.addNodeForm);
            const nodeData = {
                id: formData.get('node-id'),
                label: formData.get('node-label'),
                category: formData.get('node-category'),
                type: formData.get('node-type'),
            };
            
            this.dispatchEvent(new CustomEvent('node-added', {
                detail: nodeData,
                bubbles: true,
                composed: true,
            }));
            
            this.addNodeModal.style.display = 'none';
            this.addNodeForm.reset();
        }, this.abortController.signal);

        this.addNodeFormCancelButton.addEventListener('click', () => {
            this.addNodeForm.reset();
            this.addNodeModal.style.display = 'none';
        }, this.abortController.signal);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.cy) {
                this.cy.elements().unselect();
                this.selectedNodes = [];
                this.selectedEdge = null;
                this.infoPanel.node = null;
            }

            if ((e.key === 'p' || e.key === 'P')) {
                e.preventDefault();
                this.export();
            }

            if ((e.key === 'f' || e.key === 'F')) {
                e.preventDefault();
                this.fit();
            }
        }, this.abortController.signal);
    }

    disconnectedCallback() {
        console.log("Project disconnected");
        this.abortController.abort();
    }

    set project(value)
    {
        console.log("Setting project:", value);
        this.setAttribute("project", value);
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
        this.setAttribute("project", value);
        this.projectTitle.textContent = value.name;
        this.importNodeButton.style.display = "inline-block";
        this.addNodeButton.style.display = "inline-block";
        this.addEdgeButton.style.display = "inline-block";
    }

    get project()
    {
        console.log("Getting project:", this.getAttribute("project"));
        return this.getAttribute("project");
    }

    set graph(value)
    {
        console.log("Setting graph:", value);
        this.setAttribute("graph", value);

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
        console.log("Getting graph:", this.getAttribute("graph"));
        return this.getAttribute("graph");
    }

    set nodeStatus(statusUpdates)
    {
        console.log("Setting status:", statusUpdates);
        this.setAttribute("node-status", statusUpdates);

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
        console.log("Getting status:", this.getAttribute("node-status"));
        return this.getAttribute("node-status");
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

                #import-node-modal {
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

                #add-node-modal {
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
                    <button id="add-edge-btn">Nova Ligação</button>
                </div>

                <div id="cy"></div>
                <app-info-panel></app-info-panel>

                <div id="import-node-modal">
                    <form id="import-node-form">
                        <label for="import-node-category">Categoria do Item:</label>
                        <select id="import-node-category" name="import-node-category">
                            <option value="gene">Gene</option>
                            <option value="protein">Proteína</option>
                            <option value="compound">Composto</option>
                        </select>
                        <br>

                        <label for="import-node-type">Tipo do Item:</label>
                        <select id="import-node-type" name="import-node-type">
                            <option value="enzyme">Enzima</option>
                            <option value="receptor">Receptor</option>
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
            
            if (this.selectedNodes.length > 2) {
                this.selectedNodes = [];
                this.cy.elements().unselect();
            }
        });

        // Evento: Seleção de aresta
        this.cy.on('select', 'edge', (e) => {
            const edge = e.target;
            this.selectedEdge = edge.id();
            console.log("Selected edge:", this.selectedEdge);
        });

        // Evento: Deseleção de nó
        this.cy.on('unselect', 'node', () => {
            this.selectedNodes = [];
        });

        // Evento: Deseleção de aresta
        this.cy.on('unselect', 'edge', () => {
            this.selectedEdge = null;
        });

        // Evento: Duplo clique em nó (para mostrar info)
        this.cy.on('dbltap', 'node', (e) => {
            const node = e.target;
            this.infoPanel.node = node.data();
        });

        // Evento: Clique no background (deseleciona tudo)
        this.cy.on('tap', (e) => {
            if (e.target === this.cy) {
                this.cy.elements().unselect();
                this.infoPanel.node = null;
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
