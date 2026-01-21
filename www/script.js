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

var graph = null;

class Graph
{
    constructor() {
        this.cydiv = null;
        this.cy    = null;

        this.menu  = new Menu();
        this.modals = new Modals();

        this.graph = null;
        this.nodes = [];
        this.saves = [];
        this.save  = null;
        this.selection = [];

        this.cydiv = document.getElementById('cy');
        this.htmlTitleElement = document.getElementById('graph-title');
        this.htmlExportBtnElement = document.getElementById('export-btn');
        this.htmlOpenProjectFormIdElement = document.getElementById('open-doc-form-id')
    }

    async init() {
        this.htmlExportBtnElement.addEventListener('click', () => {
            if(! graph.save) {
                alert('Não há projeto carregado para exportar.');
                return;
            }

            const base64Image = graph.cy.png({'bg' : '#ffffff'});
            
            const downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href",     base64Image);
            downloadAnchorNode.setAttribute("download", `${graph.save.name ?? 'save'}.png`);
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
        await this.updateView();
    }

    async fetchGraph() {
        try {
            const response = await fetch(API.GET_GRAPH);
            if (!response.ok) {
                console.log('[fetchGraph] response:', response);
                throw new Error(`[fetchGraph] HTTP error! status: ${response.status}`);
            }
            const { data } = await response.json();
            this.graph = data;
            this.nodes = this.graph.elements.nodes;
        } catch (error) {
            console.error('[fetchGraph] Fetch error:', error);
        }
    }

    async fetchSaves() {
        try {
            const response = await fetch(API.GET_SAVES);
            if (!response.ok) {
                console.log('[fetchSaves] response:', response);
                throw new Error(`[fetchSaves] HTTP error! status: ${response.status}`);
            }
            const { data } = await response.json();
            this.saves = data;
            
            this.saves.forEach((save) => {
                const option = document.createElement('option');
                option.value = save.id;
                option.text = save.name;
                this.htmlOpenProjectFormIdElement.appendChild(option);
            });
        }
        catch (error) {
            console.error('[fetchSaves] Fetch error:', error);
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
                console.log('[fetchSave] response:', response);
                throw new Error(`[fetchSave] HTTP error! status: ${response.status}`);
            }
            const { data } = await response.json();
            this.save = data;
        } catch (error) {
            console.error('[fetchSave] Fetch error:', error);
        }
    }

    async updateSave() {
        try {
            const response = await fetch(API.UPDATE_SAVE, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(this.save)
            });
            
            if (!response.ok) {
                console.log('[updateSave] response:', response);
                throw new Error(`[updateSave] HTTP error! status: ${response.status}`);
            }
        } catch (error) {
            console.error('[updateSave] Fetch error:', error);
        }
    }

    updateView() {
        if(!graph.save) {
            console.log('No save loaded, cannot update view.');
            graph.modals.displayOpenProjectModal();
            return;
        }

        this.htmlTitleElement.textContent = `${graph.save?.name ?? 'Untitled'}`;

        const data = structuredClone(graph.graph);
        data.container = this.cydiv;
        
        graph.cy = cytoscape(data);

        graph.cy.on('select', 'node', (e) => {
            const selectedNodes = graph.cy.$('node:selected');
            if (selectedNodes.length > 2) {
                graph.menu.AddEdgeForm.htmlAddEdgeFormSubmit.disabled = true;
                e.target.unselect();
                return;
            }

            const node = e.target;
            graph.selection.push(node.id());

            if(graph.selection.length < 2) {
                graph.menu.AddNodeForm.hide();
                graph.menu.AddEdgeForm.show();
            } else if(graph.selection.length == 2) {
                graph.menu.AddNodeForm.hide();
                graph.menu.AddEdgeForm.show();
                graph.menu.AddEdgeForm.htmlAddEdgeFormSubmit.disabled = false;
            } else {
                graph.menu.AddNodeForm.show();
                graph.menu.AddEdgeForm.hide();
                graph.menu.AddEdgeForm.htmlAddEdgeFormSubmit.disabled = true;
            }
        });

        graph.cy.on('unselect', 'node', () => {
            graph.cy.elements().unselect();
            graph.selection = [];
            graph.menu.AddEdgeForm.hide();
            graph.menu.AddNodeForm.show();
            graph.menu.AddEdgeForm.htmlAddEdgeFormSubmit.disabled = true;
        });
        
        const startNodes = graph.cy.nodes().filter(node => 
            graph.save.nodes.includes(node.id())
        );
        
        const descendants = startNodes.successors();
        const allNodes = startNodes.union(descendants);
        graph.cy.elements().not(allNodes).remove();
        graph.cy.layout(graph.graph.layout).run();
    }
}

class Menu {
    constructor() {
        this.MENU_WIDTH_THRESHOLD = 300;
        this.keepClosed = false;
        this.htmlElement = document.getElementById('menu');
        this.htmlCloseBtnElement = document.getElementById('close-menu-btn');
        
        this.AddNodeForm = new AddNodeForm();
        this.AddEdgeForm = new AddEdgeForm();

        this.init();
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
    constructor() {
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
        const categorySelect = graph.menu.AddNodeForm.htmlAddNodeFormCategory.value;
        const typeSelect = graph.menu.AddNodeForm.htmlAddNodeFormType.value;

        const nodeListSelect = graph.menu.AddNodeForm.htmlAddNodeFormNode;
        nodeListSelect.innerHTML = '';

        graph.nodes.forEach((node) => {
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
        
        if(graph.save.nodes.includes(id)) {
            console.log('Node already in save, not adding:', id);
            return;
        }
        graph.save.nodes.push(id);
        await graph.updateSave();
        await graph.updateView();
    }
}

class AddEdgeForm {
    constructor() {
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
    
        if (graph.selection.length !== 2) {
            alert('Por favor, selecione exatamente dois nós para criar uma conexão entre eles.');
            graph.selection = [];
            graph.cy.elements().unselect();
            return;
        }
        
        const sourceNode = graph.selection[0];
        const targetNode = graph.selection[1];

        await this.insertEdge(sourceNode, targetNode);
        window.location.reload();
    }
}

class NewProjectModal {
    constructor() {
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
    
        const formData = {
            name: this.htmlNewDocFormNameElement.value,
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
            
            e.target.reset();
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
    constructor() {
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
    constructor() {
        this.newProjectModal = new NewProjectModal();
        this.openProjectModal = new OpenProjectModal();

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
    graph = new Graph();
    await graph.init();
});
