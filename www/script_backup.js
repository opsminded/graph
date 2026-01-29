"use strict";
"v2";

var COSELAYOUT = {
    'name': 'cose',
    'animate': false,
    'fit': true,
    'padding': 30,
    'componentSpacing': 40
};

// API Endpoints
const API = {
    GET_PROJECTS:   '#BASE_PATH#/getProjects',
    GET_PROJECT:    '#BASE_PATH#/getProject',
    UPDATE_PROJECT: '#BASE_PATH#/updateProject',
    INSERT_PROJECT: '#BASE_PATH#/insertProject',
    GET_STATUS:     '#BASE_PATH#/getStatus',
    GET_CATEGORIES: '#BASE_PATH#/getCategories',
    GET_TYPES:      '#BASE_PATH#/getTypes',
    INSERT_EDGE:    '#BASE_PATH#/insertEdge',
    DELETE_EDGE:    '#BASE_PATH#/deleteEdge'
};

// Utility Functions
function createOptionElement(value, text) {
    console.log('Creating option element with value:', value, 'and text:', text);
    const option = document.createElement('option');
    option.value = value;
    option.text = text;
    return option;
}

// Notification System
class Notification {
    static show(message, type = 'error', duration = 5000) {
        console.log(`Showing ${type} notification:`, message);
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'error' ? '#f44336' : type === 'success' ? '#4caf50' : '#2196f3'};
            color: white;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 10000;
            max-width: 400px;
            word-wrap: break-word;
            animation: slideIn 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }
    
    static error(message, duration) {
        console.error('Error notification:', message);
        this.show(message, 'error', duration);
    }
    
    static success(message, duration) {
        console.log('Success notification:', message);
        this.show(message, 'success', duration);
    }
    
    static info(message, duration) {
        console.info('Info notification:', message);
        this.show(message, 'info', duration);
    }
}

// State Management Store
class Store {
    constructor() {
        console.log('Initializing Store');

        this.state = {
            projects: [],
            currentProject: null,
            selection: [],
            selectedEdge: null,
            cy: null,
            currentNodeSelectionForInfo: null
        };

        this.subscribers = [];
        this.isInitializing = true;
    }

    // Subscribe to state changes
    subscribe(callback) {
        console.log('New subscriber added to Store');
        this.subscribers.push(callback);
        return () => {
            this.subscribers = this.subscribers.filter(sub => sub !== callback);
        };
    }

    // Notify all subscribers of state change
    notify(changedKeys = []) {
        if (this.isInitializing) return;
        console.log('Notifying subscribers of state change:', changedKeys);
        this.subscribers.forEach(callback => callback(this.state, changedKeys));
    }

    // Update state and notify subscribers
    setState(updates) {
        console.log('Updating Store state with:', updates);
        const changedKeys = Object.keys(updates);
        this.state = { ...this.state, ...updates };
        this.notify(changedKeys);
    }

    // Getters
    getCurrentProject() {
        console.log('Getting current project from Store');
        return this.state.currentProject;
    }

    getSelection() {
        console.log('getting selection from Store');
        return this.state.selection;
    }

    getCy() {
        console.log('Getting Cytoscape instance from Store');
        return this.state.cy;
    }

    // Actions
    setGraph(graph) {
        console.log('Setting graph in Store');
        this.setState({ graph, nodes: graph?.elements?.nodes || [] });
    }

    setProjects(projects) {
        console.log('Setting projects in Store');
        this.setState({ projects });
    }

    setCurrentProject(project) {
        console.log('Setting current project in Store');
        this.setState({ currentProject: project });
    }

    setCy(cy) {
        console.log('Setting Cytoscape instance in Store');
        this.setState({ cy });
    }

    setSelection(selection) {
        console.log('Setting selection in Store:', selection);
        this.setState({ selection });
    }

    getSelectedEdge() {
        console.log('Getting selected edge from Store');
        return this.state.selectedEdge;
    }

    addToSelection(nodeId) {
        console.log('Adding to selection in Store:', nodeId);
        const selection = [...this.state.selection, nodeId];
        this.setState({ selection });
    }

    clearSelection() {
        console.log('Clearing selection in Store');
        this.setState({ selection: [] });
    }

    setSelectedEdge(edgeId) {
        console.log('Setting selected edge in Store:', edgeId);
        this.setState({ selectedEdge: edgeId });
    }

    addNodeToProject(nodeId) {
        console.log('Adding node to current project in Store:', nodeId);
        if (!this.state.currentProject) return;
        if (this.state.currentProject.nodes.includes(nodeId)) return;
        
        const currentProject = {
            ...this.state.currentProject,
            nodes: [...this.state.currentProject.nodes, nodeId]
        };
        this.setState({ currentProject });
    }

    finishInitialization() {
        console.log('Finishing Store initialization');
        this.isInitializing = false;
    }
}

class Graph
{
    constructor(store) {
        console.log('Initializing Graph');

        this.store                        = store;
        this.cydiv                        = document.getElementById('cy');
        this.htmlTitleElement             = document.getElementById('graph-title');
        this.htmlAuthorElement            = document.getElementById('graph-author');
        this.htmlCreatedElement           = document.getElementById('graph-created');
        this.htmlExportBtnElement         = document.getElementById('export-btn');
        this.htmlFitBtnElement            = document.getElementById('fit-btn');
        this.htmlOpenProjectFormIdElement = document.getElementById('open-doc-form-id');

        this.menu      = new Menu(store, this);
        this.modals    = new Modals(store);
        this.infoPanel = new InfoPanel(store);

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

    async fetchProjects() {
        try {
            const response = await fetch(API.GET_PROJECTS);
            if (!response.ok) {
                throw new Error(`Erro ao carregar projetos: ${response.status}`);
            }
            const { data } = await response.json();
            console.log('projects', data);
            this.store.setProjects(data);
            
            data.forEach((project) => {
                this.htmlOpenProjectFormIdElement.appendChild(
                    createOptionElement(project.id, project.name)
                );
            });
        } catch (error) {
            console.error('[fetchProjects] Fetch error:', error);
            Notification.error('Falha ao carregar a lista de projetos.');
        }
    }

    async fetchProject() {
        const urlParams = new URLSearchParams(window.location.search);
        const projectID = urlParams.get('project');
        
        if (!projectID) {
            console.log('No project ID provided in URL parameters.');
            return;
        }

        try {
            const response = await fetch(`${API.GET_PROJECT}?id=${encodeURIComponent(projectID)}`);
            if (!response.ok) {
                throw new Error(`Erro ao carregar projeto: ${response.status}`);
            }
            const { data } = await response.json();
            this.store.setCurrentProject(data);
        } catch (error) {
            console.error('[fetchProject] Fetch error:', error);
            Notification.error(`Falha ao carregar o projeto. ID: ${projectID}`);
        }
    }

    async fetchStatus() {
        try {
            const response = await fetch(API.GET_STATUS);
            if (!response.ok) {
                console.error('[fetchStatus] Response not ok:', response.status);
                throw new Error(`Erro ao obter status: ${response.status}`);
            }
            
            const {data} = await response.json();
            
            const cy = this.store.getCy();
            if (!cy) {
                return;
            }

            data.forEach((status) => {
                cy.getElementById(status['node_id']).addClass('node-status-' + status['status']);
            });

        } catch (error) {
            console.error('[fetchStatus] Fetch error:', error);
            Notification.error('Falha ao obter status do servidor.');
        }
    }

    async updateProject() {
        const currentProject = this.store.getCurrentProject();
        if (!currentProject) return;

        try {
            const response = await fetch(API.UPDATE_PROJECT, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(currentProject)
            });
            
            if (!response.ok) {
                throw new Error(`Erro ao salvar: ${response.status}`);
            }

            Notification.success('Projeto salvo com sucesso!', 2000);
        } catch (error) {
            console.error('[updateProject] Fetch error:', error);
            Notification.error('Falha ao salvar o projeto. Tente novamente.');
        }
    }

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

        cy.on('select', 'node', (e) => {
            const selectedNodes = cy.$('node:selected');
            if (selectedNodes.length > 2) {
                this.menu.AddEdgeForm.htmlAddEdgeFormSubmit.disabled = true;
                e.target.unselect();
                return;
            }

            const node = e.target;
            this.store.addToSelection(node.id());
        });

        cy.on('select', 'edge', (e) => {
            const selectedEdges = cy.$('edge:selected');
            if (selectedEdges.length > 1) {
                e.target.unselect();
                return;
            }
            
            const edge = e.target;
            this.store.setSelectedEdge(edge);
        });

        cy.on('unselect', 'node', () => {
            cy.elements().unselect();
            this.store.clearSelection();
            this.infoPanel.hide();
        });

        cy.on('unselect', 'edge', () => {
            this.store.setSelectedEdge(null);
        });

        cy.on('dbltap', 'node', (e) => {
            const node = e.target;
            this.store.setState({ currentNodeSelectionForInfo: node.id() });
            this.infoPanel.show();
        });
        
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

class AddNodeForm {
    constructor(store) {
        this.store = store;
        this.htmlElement = document.getElementById('add-node-form');
        this.htmlAddNodeFormCategory = document.getElementById('add-node-form-category');
        this.htmlAddNodeFormType = document.getElementById('add-node-form-type');
        this.htmlAddNodeFormNode = document.getElementById('add-node-form-node');
    }

    init() {
        this.htmlAddNodeFormCategory.addEventListener('change', () => {
            this.updateNodeList();
        });

        this.htmlAddNodeFormType.addEventListener('change', () => {
            this.updateNodeList();
        });

        this.htmlElement.addEventListener('submit', (e) => {
            this.onSubmit(e);
        });

        this.fetchCategories();
        this.fetchTypes();
    }

    async fetchCategories() {
        try {
            const response = await fetch(API.GET_CATEGORIES);
            if (!response.ok) {
                throw new Error(`Erro ao carregar categorias: ${response.status}`);
            }
            const { data: categories } = await response.json();
            categories.forEach((category) => {
                this.htmlAddNodeFormCategory.appendChild(
                    createOptionElement(category.id, category.name)
                );
            });
        } catch (error) {
            console.error('[fetchCategories] Fetch error:', error);
            Notification.error('Falha ao carregar categorias.');
        }
    }

    async fetchTypes() {
        try {
            const response = await fetch(API.GET_TYPES);
            if (!response.ok) {
                throw new Error(`Erro ao carregar tipos: ${response.status}`);
            }
            const { data: types } = await response.json();
            types.forEach((type) => {
                this.htmlAddNodeFormType.appendChild(
                    createOptionElement(type.id, type.name)
                );
            });
        } catch (error) {
            console.error('[fetchTypes] Fetch error:', error);
            Notification.error('Falha ao carregar tipos.');
        }
    }

    updateNodeList() {
        const categorySelect = this.htmlAddNodeFormCategory.value;
        const typeSelect = this.htmlAddNodeFormType.value;
        const nodeListSelect = this.htmlAddNodeFormNode;
        const nodes = this.store.getNodes();

        nodeListSelect.innerHTML = '';

        nodes.forEach((node) => {
            if (node.data.category === categorySelect && node.data.type === typeSelect) {
                nodeListSelect.appendChild(
                    createOptionElement(node.data.id, node.data.label)
                );
            }
        });
    }
    
    show() {
        this.htmlElement.classList.remove('hide');
    }

    hide() {
        this.htmlElement.classList.add('hide');
    }

    async onSubmit(e) {
        e.preventDefault();

        const id = this.htmlAddNodeFormNode.value;
        const currentProject = this.store.getCurrentProject();
        
        if (!id) {
            alert('Por favor, selecione um nó para adicionar.');
            return;
        }
        
        if (currentProject.nodes.includes(id)) {
            console.log('Node already in project, not adding:', id);
            return;
        }

        this.store.addNodeToProject(id);
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

class NewProjectModal {
    constructor(store) {
        this.store = store;
        this.htmlElement = document.getElementById('modal-new-doc');
        this.htmlNewProjectFormElement = document.getElementById('new-doc-form');
        this.htmlNewDocFormNameElement = document.getElementById('new-doc-form-name');
        this.htmlOpenProjectFormElement = document.getElementById('open-doc-form');
        this.htmlOpenProjectFormIdElement = document.getElementById('open-doc-form-id');
    }

    init() {
        this.htmlNewProjectFormElement.addEventListener('submit', (e) => {
            this.insertProject(e);
        });

        this.htmlOpenProjectFormElement.addEventListener('submit', (e) => {
            this.openProject(e);
        });
    }

    show() {
        this.htmlElement.classList.add('show');
    }

    hide() {
        this.htmlElement.classList.remove('show');
    }

    async insertProject(e) {
        e.preventDefault();
    
        const name = this.htmlNewDocFormNameElement.value.trim();
        
        if (!name) {
            Notification.error('Por favor, insira um nome para o projeto.');
            return;
        }
        
        const formData = {
            name: name,
            nodes: []
        };
        
        try {
            const response = await fetch(API.INSERT_PROJECT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }
            
            const result = await response.json();
            if (!result?.data?.id) {
                throw new Error('Resposta inválida do servidor.');
            }
            
            Notification.success('Projeto criado com sucesso!');
            setTimeout(() => {
                window.location.href = `#BASE_PATH#/?project=${result.data.id}`;
            }, 500);
            
        } catch (error) {
            console.error('Error:', error);
            Notification.error('Erro ao criar projeto. Tente novamente.');
        }
    }

    async openProject(e) {
        e.preventDefault();
        const id = this.htmlOpenProjectFormIdElement.value;
        window.location.href = `#BASE_PATH#/?project=${id}`;
    }
}

class OpenProjectModal {
    constructor(store) {
        this.store = store;
        this.htmlElement = document.getElementById('modal-open-doc');
    }

    show() {
        this.htmlElement.classList.add('show');
    }

    hide() {
        this.htmlElement.classList.remove('show');
    }
}

class Modals {
    constructor(store) {
        this.store = store;
        this.newProjectModal = new NewProjectModal(store);
        this.openProjectModal = new OpenProjectModal(store);

        this.htmlElement = document.getElementById('modal');
        this.htmlNewProjectBtnElement = document.getElementById('new-doc-btn');
        this.htmlOpenProjectBtnElement = document.getElementById('open-doc-btn');
        this.htmlCloseBtnElement = document.getElementById('close-modal-btn');
    }

    init() {
        this.htmlNewProjectBtnElement.addEventListener('click', () => {
            this.displayNewProjectModal();
        });

        this.htmlOpenProjectBtnElement.addEventListener('click', () => {
            this.displayOpenProjectModal();
        });

        this.htmlCloseBtnElement.addEventListener('click', () => {
            this.hide();
        });
    }

    show() {
        this.htmlElement.classList.add('show');
    }
    
    hide() {
        this.htmlElement.classList.remove('show');
        this.closeNewProjectModal();
        this.closeOpenProjectModal();
    }

    displayNewProjectModal() {
        this.show();
        this.newProjectModal.show();
        this.openProjectModal.hide();
    }
    
    displayOpenProjectModal() {
        this.show();
        this.openProjectModal.show();
        this.newProjectModal.hide();
    }

    closeNewProjectModal() {
        this.newProjectModal.hide();
    }
    
    closeOpenProjectModal() {
        this.openProjectModal.hide();
    }

    onKeydown(e) {
        if (e.key === 'Escape') {
            this.hide();
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

document.addEventListener("DOMContentLoaded", async function() {
    const store = new Store();
    const graph = new Graph(store);
});
