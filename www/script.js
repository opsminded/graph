"use strict";

// API Endpoints
const API = {
    GET_GRAPH: '/getCytoscapeGraph',
    GET_SAVES: '/getSaves',
    GET_SAVE: '/getSave',
    UPDATE_SAVE: '/updateSave',
    INSERT_SAVE: '/insertSave',
    GET_CATEGORIES: '/getCategories',
    GET_TYPES: '/getTypes',
    INSERT_EDGE: '/insertEdge'
};

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
            isLoading: false
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

    setLoading(isLoading) {
        this.setState({ isLoading });
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
        this.htmlExportBtnElement = document.getElementById('export-btn');
        this.htmlOpenProjectFormIdElement = document.getElementById('open-doc-form-id');

        this.menu = new Menu(store);
        this.modals = new Modals(store);

        // Subscribe to state changes
        this.store.subscribe((state, changedKeys) => {
            // Auto-save when currentSave nodes change (but not on initial load)
            if (changedKeys.includes('currentSave') && state.currentSave) {
                this.updateSave();
            }
            
            // Update view when graph or currentSave changes
            if (changedKeys.includes('currentSave') || changedKeys.includes('graph')) {
                this.updateView();
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
        this.updateView();
    }

    async fetchGraph() {
        try {
            this.store.setLoading(true);
            const response = await fetch(API.GET_GRAPH);
            if (!response.ok) {
                console.log('[fetchGraph] response:', response);
                throw new Error(`[fetchGraph] HTTP error! status: ${response.status}`);
            }
            const { data } = await response.json();
            this.store.setGraph(data);
        } catch (error) {
            console.error('[fetchGraph] Fetch error:', error);
        } finally {
            this.store.setLoading(false);
        }
    }

    async fetchSaves() {
        try {
            this.store.setLoading(true);
            const response = await fetch(API.GET_SAVES);
            if (!response.ok) {
                console.log('[fetchSaves] response:', response);
                throw new Error(`[fetchSaves] HTTP error! status: ${response.status}`);
            }
            const { data } = await response.json();
            this.store.setSaves(data);
            
            data.forEach((save) => {
                const option = document.createElement('option');
                option.value = save.id;
                option.text = save.name;
                this.htmlOpenProjectFormIdElement.appendChild(option);
            });
        } catch (error) {
            console.error('[fetchSaves] Fetch error:', error);
        } finally {
            this.store.setLoading(false);
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
            this.store.setLoading(true);
            const response = await fetch(`${API.GET_SAVE}?id=${encodeURIComponent(saveID)}`);
            if (!response.ok) {
                console.log('[fetchSave] response:', response);
                throw new Error(`[fetchSave] HTTP error! status: ${response.status}`);
            }
            const { data } = await response.json();
            this.store.setCurrentSave(data);
        } catch (error) {
            console.error('[fetchSave] Fetch error:', error);
        } finally {
            this.store.setLoading(false);
        }
    }

    async updateSave() {
        const currentSave = this.store.getCurrentSave();
        if (!currentSave) return;

        try {
            this.store.setLoading(true);
            const response = await fetch(API.UPDATE_SAVE, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(currentSave)
            });
            
            if (!response.ok) {
                console.log('[updateSave] response:', response);
                throw new Error(`[updateSave] HTTP error! status: ${response.status}`);
            }
        } catch (error) {
            console.error('[updateSave] Fetch error:', error);
        } finally {
            this.store.setLoading(false);
        }
    }

    updateView() {
        const currentSave = this.store.getCurrentSave();
        const graphData = this.store.getState().graph;

        if (!currentSave) {
            console.log('No save loaded, cannot update view.');
            this.modals.displayOpenProjectModal();
            return;
        }

        this.htmlTitleElement.textContent = currentSave?.name ?? 'Untitled';

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
        });
        
        const startNodes = cy.nodes().filter(node => 
            currentSave.nodes.includes(node.id())
        );
        
        const descendants = startNodes.successors();
        const allNodes = startNodes.union(descendants);
        cy.elements().not(allNodes).remove();
        cy.layout(graphData.layout).run();
    }
}

class Menu {
    constructor(store) {
        this.store = store;
        this.MENU_WIDTH_THRESHOLD = 300;
        this.keepClosed = false;
        this.htmlElement = document.getElementById('menu');
        this.htmlCloseBtnElement = document.getElementById('close-menu-btn');
        
        this.AddNodeForm = new AddNodeForm(store);
        this.AddEdgeForm = new AddEdgeForm(store);

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
        if(e.clientX > this.MENU_WIDTH_THRESHOLD) {
            if (this.keepClosed) {
                this.hide();
            }
        }

        if(e.clientX <= this.MENU_WIDTH_THRESHOLD) {
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
                console.log('[fetchCategories] response:', response);
                throw new Error(`[fetchCategories] HTTP error! status: ${response.status}`);
            }
            const { data: categories } = await response.json();
            categories.forEach((category) => {
                const option = document.createElement('option');
                option.value = category.id;
                option.text = category.name;
                this.htmlAddNodeFormCategory.appendChild(option);
            });
        } catch (error) {
            console.error('[fetchCategories] Fetch error:', error);
        }
    }

    async fetchTypes() {
        try {
            const response = await fetch(API.GET_TYPES);
            if (!response.ok) {
                console.log('[fetchTypes] response:', response);
                throw new Error(`[fetchTypes] HTTP error! status: ${response.status}`);
            }
            const { data: types } = await response.json();
            types.forEach((type) => {
                const option = document.createElement('option');
                option.value = type.id;
                option.text = type.name;
                this.htmlAddNodeFormType.appendChild(option);
            });
        }
        catch (error) {
            console.error('[fetchTypes] Fetch error:', error);
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
                const option = document.createElement('option');
                option.value = node.data.id;
                option.text = node.data.label;
                nodeListSelect.appendChild(option);
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
    constructor(store) {
        this.store = store;
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
                console.log('[insertEdge] response:', response);
                alert(`Failed to create edge: HTTP ${response.status}`);
                throw new Error(`[insertEdge] HTTP error! status: ${response.status}`);
            }
        } catch (error) {
            alert(`Failed to create edge: ${error.message}`);
            console.error('[insertEdge] Fetch error:', error);
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
            alert('Por favor, selecione exatamente dois nós para criar uma conexão entre eles.');
            this.store.clearSelection();
            if (cy) cy.elements().unselect();
            return;
        }
        
        const sourceNode = selection[0];
        const targetNode = selection[1];

        await this.insertEdge(sourceNode, targetNode);
        
        // Reload page to fetch updated graph with new edge
        window.location.reload();
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
            alert('Por favor, insira um nome para o projeto.');
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
                console.log('response:', response);
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            window.location.href = `/?save=${result.data.id}`;
            
        } catch (error) {
            console.error('Error:', error);
        }
    }

    async openProject(e) {
        e.preventDefault();
        console.log('Open project form submitted.');
        console.log(this.htmlOpenProjectFormIdElement.value);
        const id = this.htmlOpenProjectFormIdElement.value;
        window.location.href = `/?save=${id}`;
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

document.addEventListener("DOMContentLoaded", async function() {
    const store = new Store();
    const graph = new Graph(store);
    await graph.init();
});
