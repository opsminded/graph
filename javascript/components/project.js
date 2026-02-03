"use strict";

import cytoscape from "/javascript/libs/cytoscape.esm.min.mjs";

export class Project extends HTMLElement
{
    static observedAttributes = ["project", "graph", "node-status"];
    
    constructor()
    {
        super();
        this.cy = null;
        this.selectedNodes = [];
        this.selectedEdge = null;
        this.render();
    }

    connectedCallback() {
        console.log("Project connected");
        this.projectTitle  = this.shadowRoot.getElementById('project-title');
        this.cyContainer = this.shadowRoot.getElementById('cy');
    }

    disconnectedCallback() {
        console.log("Project disconnected");
    }

    set project(value)
    {
        console.log("Setting project:", value);
        this.setAttribute("project", value);
        this.projectTitle.textContent = value.name;
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

    set nodeStatus(status)
    {
        console.log("Setting status:", status);
        this.setAttribute("node-status", status);

        // foreach status in value
        for(let update of status) {
            this.cy.$('#' + update.node_id).addClass(`${update.status}`);
        }
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

                #project-container h2 {
                    position: absolute;
                    left: 320px;
                    top: 10px;
                    z-index: 101;
                }
            </style>
            <div id="project-container">
                <h2 id="project-title"></h2>
                <div id="cy"></div>
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
            alert(`Node Info:\nID: ${node.id()}\nData: ${JSON.stringify(node.data())}`);
        });

        // Evento: Clique no background (deseleciona tudo)
        this.cy.on('tap', (e) => {
            if (e.target === this.cy) {
                this.cy.elements().unselect();
            }
        });
    }

    /**
     * Limpa o projeto e destrói a instância do Cytoscape.
     */
    clear() {
        this.projectTitle.textContent = '';
        
        if (this.cy) {
            this.cy.destroy();
            this.cy = null;
        }
    }

    /**
     * Atualiza os status visuais dos nós.
     * @param {Array} statusUpdates - Array de objetos {node_id, status}
     */
    updateNodeStatuses(statusUpdates) {
        if (!this.cy) return;

        console.log('updateNodeStatuses called with:', statusUpdates);
        
        statusUpdates.forEach(update => {
            const node = this.cy.$(`#${update.node_id}`);
            
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

    /**
     * Exporta o gráfico como imagem PNG.
     */
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

    /**
     * Ajusta o zoom para mostrar todo o gráfico.
     */
    fit() {
        if (this.cy) {
            this.cy.fit();
        }
    }
}

customElements.define('app-project', Project);