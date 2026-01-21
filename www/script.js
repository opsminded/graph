"use strict";
"v1";

// API Endpoints
const API = {
    GET_GRAPH:      '#BASE_PATH#/getCytoscapeGraph',
    GET_SAVES:      '#BASE_PATH#/getSaves',
    GET_SAVE:       '#BASE_PATH#/getSave',
    GET_STATUS:     '#BASE_PATH#/getStatus',
    UPDATE_SAVE:    '#BASE_PATH#/updateSave',
    INSERT_SAVE:    '#BASE_PATH#/insertSave',
    GET_CATEGORIES: '#BASE_PATH#/getCategories',
    GET_TYPES:      '#BASE_PATH#/getTypes',
    INSERT_EDGE:    '#BASE_PATH#/insertEdge'
};

// Utility Functions
function createOptionElement(value, text) {
    const option = document.createElement('option');
    option.value = value;
    option.text = text;
    return option;
}

// Notification System
class Notification {
    static show(message, type = 'error', duration = 5000) {
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
        this.show(message, 'error', duration);
    }
    
    static success(message, duration) {
        this.show(message, 'success', duration);
    }
    
    static info(message, duration) {
        this.show(message, 'info', duration);
    }
}

// State Management Store
class Store {
    constructor() {
        this.state = {
            graph: null,
            nodes: [],
            saves: [],
            currentSave: null,
            selection: [],
            cy: null,
            currentNodeSelectionForInfo: null
        };
        this.subscribers = [];
        this.isInitializing = true;
    }

    // Subscribe to state changes
    subscribe(callback) {
        this.subscribers.push(callback);
        return () => {
            this.subscribers = this.subscribers.filter(sub => sub !== callback);
        };
    }

    // Notify all subscribers of state change
    notify(changedKeys = []) {
        if (this.isInitializing) return;
        this.subscribers.forEach(callback => callback(this.state, changedKeys));
    }

    // Update state and notify subscribers
    setState(updates) {
        const changedKeys = Object.keys(updates);
        this.state = { ...this.state, ...updates };
        this.notify(changedKeys);
    }

    // Getters
    getState() {
        return this.state;
    }

    getCurrentSave() {
        return this.state.currentSave;
    }

    getSelection() {
        return this.state.selection;
    }

    getNodes() {
        return this.state.nodes;
    }

    getCy() {
        return this.state.cy;
    }

    // Actions
    setGraph(graph) {
        this.setState({ graph, nodes: graph?.elements?.nodes || [] });
    }

    setSaves(saves) {
        this.setState({ saves });
    }

    setCurrentSave(save) {
        this.setState({ currentSave: save });
    }

    setCy(cy) {
        this.setState({ cy });
    }

    setSelection(selection) {
        this.setState({ selection });
    }

    addToSelection(nodeId) {
        const selection = [...this.state.selection, nodeId];
        this.setState({ selection });
    }

    clearSelection() {
        this.setState({ selection: [] });
    }

    addNodeToSave(nodeId) {
        if (!this.state.currentSave) return;
        if (this.state.currentSave.nodes.includes(nodeId)) return;
        
        const currentSave = {
            ...this.state.currentSave,
            nodes: [...this.state.currentSave.nodes, nodeId]
        };
        this.setState({ currentSave });
    }

    finishInitialization() {
        this.isInitializing = false;
    }
}

class Graph
{
    constructor(store) {
        this.store = store;
        this.cydiv = document.getElementById('cy');
        
        this.htmlTitleElement = document.getElementById('graph-title');
        this.htmlAuthorElement = document.getElementById('graph-author');
        this.htmlCreatedElement = document.getElementById('graph-created');

        this.htmlExportBtnElement = document.getElementById('export-btn');
        this.htmlOpenProjectFormIdElement = document.getElementById('open-doc-form-id');

        this.menu = new Menu(store, this);
        this.modals = new Modals(store);
        this.infoPanel = new InfoPanel(store);

        // Subscribe to state changes
        this.store.subscribe(async (state, changedKeys) => {
            // Auto-save when currentSave nodes change (but not on initial load)
            if (changedKeys.includes('currentSave') && state.currentSave) {
                this.updateSave();
            }
            
            // Update view when graph or currentSave changes
            if (changedKeys.includes('currentSave') || changedKeys.includes('graph')) {
                await this.updateView();
            }
        });
    }

    async init() {
        this.htmlExportBtnElement.addEventListener('click', () => {
            const currentSave = this.store.getCurrentSave();
            if (!currentSave) {
                alert('Não há projeto carregado para exportar.');
                return;
            }

            const cy = this.store.getCy();
            const base64Image = cy.png({'bg': '#ffffff'});
            
            const downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href", base64Image);
            downloadAnchorNode.setAttribute("download", `${currentSave.name ?? 'save'}.png`);
            document.body.appendChild(downloadAnchorNode);
            downloadAnchorNode.click();
            downloadAnchorNode.remove();
        });

        document.addEventListener('mousemove', (e) => {
            this.menu.onMouseMove(e);
        });

        document.addEventListener('keydown', (e) => {
            this.modals.onKeydown(e);
        });

        await this.fetchGraph();
        await this.fetchSaves();
        await this.fetchSave();
        
        // Finish initialization and enable reactive updates
        this.store.finishInitialization();
        
        // Initial view update
        await this.updateView();
    }

    async fetchGraph() {
        try {
            const response = await fetch(API.GET_GRAPH);
            if (!response.ok) {
                throw new Error(`Erro ao carregar o grafo: ${response.status}`);
            }
            const { data } = await response.json();
            this.store.setGraph(data);
        } catch (error) {
            Notification.error('Falha ao carregar o grafo. Por favor, recarregue a página.');
        }
    }

    async fetchSaves() {
        try {
            const response = await fetch(API.GET_SAVES);
            if (!response.ok) {
                throw new Error(`Erro ao carregar projetos: ${response.status}`);
            }
            const { data } = await response.json();
            this.store.setSaves(data);
            
            data.forEach((save) => {
                this.htmlOpenProjectFormIdElement.appendChild(
                    createOptionElement(save.id, save.name)
                );
            });
        } catch (error) {
            console.error('[fetchSaves] Fetch error:', error);
            Notification.error('Falha ao carregar a lista de projetos.');
        }
    }

    async fetchSave() {
        const urlParams = new URLSearchParams(window.location.search);
        const saveID = urlParams.get('save');
        
        if (!saveID) {
            console.log('[fetchSave] No save ID provided in URL parameters.');
            return;
        }

        try {
            const response = await fetch(`${API.GET_SAVE}?id=${encodeURIComponent(saveID)}`);
            if (!response.ok) {
                throw new Error(`Erro ao carregar projeto: ${response.status}`);
            }
            const { data } = await response.json();
            this.store.setCurrentSave(data);
        } catch (error) {
            console.error('[fetchSave] Fetch error:', error);
            Notification.error(`Falha ao carregar o projeto. ID: ${saveID}`);
        }
    }

    async fetchStatus() {
        try {
            const response = await fetch(API.GET_STATUS);
            if (!response.ok) {
                console.log('[fetchStatus] Response not ok:', response.status);
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

    async updateSave() {
        const currentSave = this.store.getCurrentSave();
        if (!currentSave) return;

        try {
            const response = await fetch(API.UPDATE_SAVE, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(currentSave)
            });
            
            if (!response.ok) {
                throw new Error(`Erro ao salvar: ${response.status}`);
            }

            Notification.success('Projeto salvo com sucesso!', 2000);
        } catch (error) {
            console.error('[updateSave] Fetch error:', error);
            Notification.error('Falha ao salvar o projeto. Tente novamente.');
        }
    }

    async updateView() {
        const currentSave = this.store.getCurrentSave();
        const graphData = this.store.getState().graph;

        if (!currentSave) {
            this.modals.displayOpenProjectModal();
            return;
        }

        this.htmlTitleElement.textContent = currentSave?.name ?? 'Untitled';
        this.htmlAuthorElement.textContent = currentSave?.creator ?? 'Unknown';
        this.htmlCreatedElement.textContent = new Date(currentSave?.created_at).toLocaleString() ?? 'Unknown';

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

        cy.on('unselect', 'node', () => {
            cy.elements().unselect();
            this.store.clearSelection();
            this.infoPanel.hide();
        });

        cy.on('dbltap', 'node', (e) => {
            const node = e.target;
            this.store.setState({ currentNodeSelectionForInfo: node.id() });
            this.infoPanel.show();
        });
        
        const startNodes = cy.nodes().filter(node => 
            currentSave.nodes.includes(node.id())
        );
        
        const descendants = startNodes.successors();
        const allNodes = startNodes.union(descendants);
        cy.elements().not(allNodes).remove();
        cy.layout(graphData.layout).run();

        let offset = 0;
        setInterval(() => {
            offset -= 9;
            cy.edges().animate({
                style: { 'line-dash-offset': offset }
            }, {
                duration: 1000,
                easing: 'linear'
            });
        }, 1000);

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

        this.init();
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

        this.init();
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
        const currentSave = this.store.getCurrentSave();
        
        if (!id) {
            alert('Por favor, selecione um nó para adicionar.');
            return;
        }
        
        if (currentSave.nodes.includes(id)) {
            console.log('Node already in save, not adding:', id);
            return;
        }

        this.store.addNodeToSave(id);
    }
}

class AddEdgeForm {
    constructor(store, graph) {
        this.store = store;
        this.graph = graph;
        this.htmlElement = document.getElementById('add-edge-form');
        this.htmlAddEdgeFormSubmit = document.getElementById('add-edge-form-submit');

        this.init();
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
            
            // Re-fetch graph to get the new edge - will trigger reactive update
            await this.graph.fetchGraph();
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

        this.init();
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
            const response = await fetch(API.INSERT_SAVE, {
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
                window.location.href = `#BASE_PATH#/?save=${result.data.id}`;
            }, 500);
            
        } catch (error) {
            console.error('Error:', error);
            Notification.error('Erro ao criar projeto. Tente novamente.');
        }
    }

    async openProject(e) {
        e.preventDefault();
        const id = this.htmlOpenProjectFormIdElement.value;
        window.location.href = `#BASE_PATH#/?save=${id}`;
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

        this.init();
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

        console.log(nodeData);

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
    await graph.init();
});
