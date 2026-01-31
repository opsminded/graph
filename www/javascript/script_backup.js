
class Graph
{
    constructor(store) {
        console.log('Initializing Graph');

        // Subscribe to state changes
        this.store.subscribe(async (state, changedKeys) => {
            console.log('Graph detected Store state change:', changedKeys);
            // Auto when currentProject nodes change (but not on initial load)
            if (changedKeys.includes('currentProject') && state.currentProject) {
                console.log('Current project changed, updating project on server');
                //await this.updateProject();
            }
            
            // Update view when graph or currentProject changes
            if (changedKeys.includes('currentProject') || changedKeys.includes('graph')) {
                console.log('Graph or current project changed, updating view');
                //await this.updateView();
            }
        });

        this.store.finishInitialization();
    }

    // async init() {
    //     console.log('Init');

    //     this.htmlFitBtnElement.addEventListener('click', () => {
    //         console.log('Fit button clicked');

    //         const cy = this.store.getCy();
    //         cy.layout(COSELAYOUT).run();
    //         cy.fit();
    //     });

    //     this.htmlExportBtnElement.addEventListener('click', () => {
    //         console.log('Export button clicked');
    //         const currentProject = this.store.getCurrentProject();
    //         if (!currentProject) {
    //             alert('Não há projeto carregado para exportar.');
    //             return;
    //         }

    //         const cy = this.store.getCy();
    //         const base64Image = cy.png({'bg': '#ffffff'});
            
    //         const downloadAnchorNode = document.createElement('a');
    //         downloadAnchorNode.setAttribute("href", base64Image);
    //         downloadAnchorNode.setAttribute("download", `${currentProject.name ?? 'project'}.png`);
    //         document.body.appendChild(downloadAnchorNode);
    //         downloadAnchorNode.click();
    //         downloadAnchorNode.remove();
    //     });

    //     document.addEventListener('mousemove', (e) => {
    //         console.log('Mouse move event detected');
    //         this.menu.onMouseMove(e);
    //     });

    //     document.addEventListener('keydown', async (e) => {
    //         console.log('Key down event detected:', e.key);
    //         await this.removeNode(e);
    //         await this.removeEdge(e);
    //         this.modals.onKeydown(e);
    //     });

    //     // Finish initialization and enable reactive updates
    //     this.store.finishInitialization();
        
    //     // Initial view update
    //     await this.updateView();

    //     const cy = this.store.getCy();
    //     cy.layout(COSELAYOUT).run();
    //     cy.fit();
    // }



    async removeNode(e) {

        const edgeSelected = this.store.getSelectedEdge();
        if (edgeSelected) {
            return;
        }

        if (e.key === 'Delete' || e.key === 'Backspace') {
            const selection = this.store.getSelection();
            if (selection.length > 1) {
                Notification.error('Por favor, selecione somente um nó para remover.');
                return;
            }
            const node = selection[0];
            const currentProject = this.store.getCurrentProject();
            
            if (!currentProject) {
                Notification.error('Nenhum projeto carregado.');
                return;
            }
            
            if (!currentProject.nodes.includes(node)) {
                Notification.error('O nó selecionado é uma dependência obrigatória.');
                return;
            }
            
            const updatedNodes = currentProject.nodes.filter(nodeId => nodeId !== node);

            currentProject.nodes = updatedNodes;
            this.store.clearSelection();
            this.store.setCurrentProject(currentProject);
            return;
        }
    }

    async removeEdge(e) {
        if (e.key === 'Delete' || e.key === 'Backspace') {
            const selectedEdge = this.store.getSelectedEdge();
            if (!selectedEdge) {
                return;
            }

            const payload = {
                'source': selectedEdge.data('source'),
                'target': selectedEdge.data('target')
            };

            try {
                const response = await fetch(API.DELETE_EDGE, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                if (!response.ok) {
                    throw new Error(`Erro ao remover aresta: ${response.status}`);
                }

                this.store.setSelectedEdge(null);
                Notification.success('Aresta removida com sucesso!', 2000);
            } catch (error) {
                console.error('[removeEdge] Fetch error:', error);
                Notification.error('Falha ao remover a aresta. Tente novamente.');
            }
        }
    }

    async updateView() {
        const currentProject = this.store.getCurrentProject();
        
        if (!currentProject) {
            this.modals.displayOpenProjectModal();
            return;
        }

        this.htmlTitleElement.textContent = currentProject?.name ?? 'Untitled';
        this.htmlAuthorElement.textContent = currentProject?.creator ?? 'Unknown';
        this.htmlCreatedElement.textContent = new Date(currentProject?.created_at).toLocaleString() ?? 'Unknown';

        if (!graphData) {
            console.log('No graph data available, cannot update view.');
            return;
        }

        // Destroy old Cytoscape instance to prevent memory leak
        const oldCy = this.store.getCy();
        if (oldCy) {
            oldCy.destroy();
        }

        const data = { ...graphData };
        data.container = this.cydiv;
        
        const cy = cytoscape(data);
        this.store.setCy(cy);

        
        
        const startNodes = cy.nodes().filter(node => 
            currentProject.nodes.includes(node.id())
        );
        
        const descendants = startNodes.successors();
        const allNodes = startNodes.union(descendants);
        cy.elements().not(allNodes).remove();
        
        // let offset = 0;
        // setInterval(() => {
        //     offset -= 9;
        //     cy.edges().animate({
        //         style: { 'line-dash-offset': offset }
        //     }, {
        //         duration: 1000,
        //         easing: 'linear'
        //     });
        // }, 1000);

        await this.fetchStatus();
        
        setInterval(async () => {
            await this.fetchStatus();
        }, 5000);
    }
}

class Menu {
    constructor(store, graph) {
        this.store = store;
        this.graph = graph;
        this.MENU_WIDTH_THRESHOLD = 300;
        this.keepClosed = false;
        this.mouseMoveDebounceTimer = null;
        this.htmlElement = document.getElementById('menu');
        this.htmlCloseBtnElement = document.getElementById('close-menu-btn');
        
        this.AddNodeForm = new AddNodeForm(store);
        this.AddEdgeForm = new AddEdgeForm(store, graph);

        this.setupSubscriptions();
    }

    setupSubscriptions() {
        // React to selection changes
        this.store.subscribe((state, changedKeys) => {
            if (changedKeys.includes('selection')) {
                this.handleSelectionChange(state.selection);
            }
        });
    }

    handleSelectionChange(selection) {
        if (selection.length === 0) {
            this.AddEdgeForm.hide();
            this.AddNodeForm.show();
            this.AddEdgeForm.htmlAddEdgeFormSubmit.disabled = true;
        } else if (selection.length === 1) {
            this.AddNodeForm.hide();
            this.AddEdgeForm.show();
            this.AddEdgeForm.htmlAddEdgeFormSubmit.disabled = true;
        } else if (selection.length === 2) {
            this.AddNodeForm.hide();
            this.AddEdgeForm.show();
            this.AddEdgeForm.htmlAddEdgeFormSubmit.disabled = false;
        }
    }

    init() {
        this.htmlCloseBtnElement.addEventListener('click', () => {
            this.onCloseBtnClick();
        });
    }

    show() {
        this.htmlElement.classList.remove('hide');
    }

    hide() {
        this.htmlElement.classList.add('hide');
    }

    onMouseMove(e) {
        // Debounce to prevent excessive calls (~60fps)
        if (this.mouseMoveDebounceTimer) return;
        
        this.mouseMoveDebounceTimer = setTimeout(() => {
            this.mouseMoveDebounceTimer = null;
        }, 16);
        
        if (e.clientX > this.MENU_WIDTH_THRESHOLD && this.keepClosed) {
            this.hide();
        } else if (e.clientX <= this.MENU_WIDTH_THRESHOLD) {
            this.show();
        }
    }

    onCloseBtnClick() {
        this.keepClosed = !this.keepClosed;

        if (this.keepClosed) {
            this.htmlCloseBtnElement.textContent = 'fixar';
        } else {
            this.htmlCloseBtnElement.textContent = 'X';
        }
    }
}


class AddEdgeForm {
    constructor(store, graph) {
        this.store = store;
        this.graph = graph;
        this.htmlElement = document.getElementById('add-edge-form');
        this.htmlAddEdgeFormSubmit = document.getElementById('add-edge-form-submit');
    }

    init() {
        this.htmlElement.addEventListener('submit', (e) => {
            this.onSubmit(e);
        });
    }

    async insertEdge(sourceNode, targetNode) {
        try {
            const data = {
                source: sourceNode,
                target: targetNode,
                label: '',
                data: {}
            }

            const response = await fetch(API.INSERT_EDGE, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }
            
            Notification.success('Conexão criada com sucesso!');
        } catch (error) {
            console.error('[insertEdge] Fetch error:', error);
            Notification.error(`Falha ao criar conexão: ${error.message}`);
            throw error;
        }
    }

    show() {
        this.htmlElement.classList.add('show');
    }

    hide() {
        this.htmlElement.classList.remove('show');
    }

    async onSubmit(e) {
        e.preventDefault();
    
        const selection = this.store.getSelection();
        const cy = this.store.getCy();

        if (selection.length !== 2) {
            Notification.error('Por favor, selecione exatamente dois nós para criar uma conexão entre eles.');
            this.store.clearSelection();
            if (cy) cy.elements().unselect();
            return;
        }
        
        const sourceNode = selection[0];
        const targetNode = selection[1];

        try {
            await this.insertEdge(sourceNode, targetNode);
            
            // Clear selection
            this.store.clearSelection();
            if (cy) cy.elements().unselect();
        } catch (error) {
            // Error already handled in insertEdge
        }
    }
}


class InfoPanel {
    constructor(store) {
        this.store = store;
        this.htmlElement = document.getElementById('info-panel');
        this.htmlInfoNodeId = document.getElementById('info-node-id');
        this.htmlInfoNodeLabel = document.getElementById('info-node-label');
        this.htmlInfoNodeCategory = document.getElementById('info-node-category');
        this.htmlInfoNodeType = document.getElementById('info-node-type');
        this.htmlInfoNodeProperties = document.getElementById('info-node-properties');
    }

    show() {

        const state = this.store.getState();
        const nodeId = state.currentNodeSelectionForInfo;
        const nodes = state.graph?.elements?.nodes || [];
        const nodeData = nodes.find(node => node.data.id === nodeId);

        if (nodeData) {
            this.htmlInfoNodeId.textContent = nodeData.data.id;
            this.htmlInfoNodeLabel.textContent = nodeData.data.label;
            this.htmlInfoNodeCategory.textContent = nodeData.data.category;
            this.htmlInfoNodeType.textContent = nodeData.data.type;

            // Clear previous properties
            this.htmlInfoNodeProperties.innerHTML = '';
            
            // Populate additional properties
            for (const [key, value] of Object.entries(nodeData.data)) {
                if (['id', 'label', 'category', 'type'].includes(key)) continue;
                
                const p = document.createElement('p');

                const strong = document.createElement('strong');
                strong.textContent = `${key}: `;
                p.appendChild(strong);

                if(value !== null && typeof value === 'string' && value.startsWith('http')) {
                    const a = document.createElement('a');
                    a.href = value;
                    a.textContent = value;
                    a.target = '_blank';
                    p.appendChild(a);
                } else {
                    const span = document.createElement('span');
                    span.textContent = `${value}`;
                    p.appendChild(span);
                }

                this.htmlInfoNodeProperties.appendChild(p);
            }

        } else {
            this.htmlInfoNodeId.textContent = 'N/A';
            this.htmlInfoNodeLabel.textContent = 'N/A';
            this.htmlInfoNodeCategory.textContent = 'N/A';
            this.htmlInfoNodeType.textContent = 'N/A';
        }

        this.htmlElement.classList.add('show');
    }

    hide() {
        this.htmlElement.classList.remove('show');
    }
}
