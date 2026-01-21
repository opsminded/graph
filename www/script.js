"use strict";

class Graph
{
    constructor() {
        this.cy    = null;

        this.menu  = new Menu();
        this.modals = new Modals();

        this.graph = null;
        this.nodes = [];
        this.saves = [];
        this.save  = null;
        this.selection = [];

        this.htmlExportBtnElement = document.getElementById('export-btn');

        this.init();
    }

    init() {
        this.htmlExportBtnElement.addEventListener('click', () => {
            if(! graph.save) {
                alert('No save loaded to export.');
                return;
            }

            const base64Image = graph.cy.png({'bg' : '#ffffff'});
            
            const downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href",     base64Image);
            downloadAnchorNode.setAttribute("download", `${graph.save.name || 'save'}.png`);
            document.body.appendChild(downloadAnchorNode);
            downloadAnchorNode.click();
            downloadAnchorNode.remove();
        });
    }
}

class Menu {
    constructor() {
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
        if(e.clientX > 300) {
            if (this.keepClosed) {
                this.hide();
            }
        }

        if(e.clientX <= 300) {
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
            updateNodeList();
        });

        this.htmlAddNodeFormType.addEventListener('change', () => {
            updateNodeList();
        });

        this.htmlElement.addEventListener('submit', (e) => {
            this.onSubmit(e);
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
        await updateSave();
        await updateView();
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

    show() {
        this.htmlElement.classList.add('show');
    }

    hide() {
        this.htmlElement.classList.remove('show');
    }

    async onSubmit(e) {
        e.preventDefault();
    
        if (graph.selection.length !== 2) {
            alert('Please select exactly two nodes to create an edge between them.');
            graph.selection = [];
            graph.cy.elements().unselect();
            return;
        }
        
        const sourceNode = graph.selection[0];
        const targetNode = graph.selection[1];

        // console.log('Creating edge between nodes:', sourceNode, 'and', targetNode);
        await insertEdge(sourceNode, targetNode);
        window.location.reload();
    }
}

class NewProjectModal {
    constructor() {
        this.htmlElement = document.getElementById('modal-new-doc');
    }

    show() {
        this.htmlElement.classList.add('show');
    }

    hide() {
        this.htmlElement.classList.remove('show');
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
        this.newProjectModal = null;
        this.openProjectModal = null;

        this.htmlElement = document.getElementById('modal');
        this.htmlNewProjectBtnElement = document.getElementById('new-doc-btn');
        this.htmlOpenProjectBtnElement = document.getElementById('open-doc-btn');
        this.htmlCloseBtnElement = document.getElementById('close-modal-btn');

        this.init();
    }

    init() {
        this.newProjectModal = new NewProjectModal();
        this.openProjectModal = new OpenProjectModal();

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

////////////////////////////////////////////////////////////////////////////////
var graph = new Graph();
////////////////////////////////////////////////////////////////////////////////

async function fetchGraph()
{
    try {
        var response = await fetch('/getCytoscapeGraph');
        if (!response.ok) {
            console.log('[fetchGraph] response:', response);
            throw new Error(`[fetchGraph] HTTP error! status: ${response.status}`);
        }
        var graphData = await response.json();
        // console.log('[fetchGraph] Graph data:', graphData.data);
        graph.graph = graphData['data'];
        graph.nodes = graph.graph.elements.nodes;
    } catch (error) {
        console.error('[fetchGraph] Fetch error:', error);
    }
}

async function fetchCategories()
{
    try {
        var response = await fetch('/getCategories');
        if (!response.ok) {
            console.log('[fetchCategories] response:', response);
            throw new Error(`[fetchCategories] HTTP error! status: ${response.status}`);
        }
        var categoriesData = await response.json();
        var categories = categoriesData['data'];
        categories.forEach(function(category) {
            var option = document.createElement('option');
            option.value = category.id;
            option.text = category.name;
            graph.menu.AddNodeForm.htmlAddNodeFormCategory.appendChild(option);
        });
    } catch (error) {
        console.error('[fetchCategories] Fetch error:', error);
    }
}

async function fetchTypes()
{
    try {
        var response = await fetch('/getTypes');
        if (!response.ok) {
            console.log('[fetchTypes] response:', response);
            throw new Error(`[fetchTypes] HTTP error! status: ${response.status}`);
        }
        var typesData = await response.json();
        var types = typesData['data'];
        types.forEach(function(type) {
            var option = document.createElement('option');
            option.value = type.id;
            option.text = type.name;
            graph.menu.AddNodeForm.htmlAddNodeFormType.appendChild(option);
        });
    }
    catch (error) {
        console.error('[fetchTypes] Fetch error:', error);
    }
}

async function fetchSaves()
{
    try {
        var response = await fetch('/getSaves');
        if (!response.ok) {
            console.log('[fetchSaves] response:', response);
            throw new Error(`[fetchSaves] HTTP error! status: ${response.status}`);
        }
        var savesData = await response.json();
        graph.saves = savesData['data'];
        
        // fill the form select with saves
        var saveSelect = document.getElementById('open-doc-form-id');
        graph.saves.forEach(function(save) {
            var option = document.createElement('option');
            option.value = save.id;
            option.text = save.name;
            saveSelect.appendChild(option);
        });
    }
    catch (error) {
        console.error('[fetchSaves] Fetch error:', error);
    }
}

async function fetchSave()
{
    const urlParams = new URLSearchParams(window.location.search);
    const saveID = urlParams.get('save');
    // console.log('[fetchSave] Fetching save with ID:', saveID);

    if (!saveID) {
        console.log('[fetchSave] No save ID provided in URL parameters.');
        return;
    }

    try {
        var response = await fetch(`/getSave?id=${encodeURIComponent(saveID)}`);
        if (!response.ok) {
            console.log('[fetchSave] response:', response);
            throw new Error(`[fetchSave] HTTP error! status: ${response.status}`);
        }
        var saveData = await response.json();
        // console.log('[fetchSave] Save data:', saveData);
        graph.save = saveData['data'];
        // console.log('[fetchSave] Loaded save:', graph.save);
    } catch (error) {
        console.error('[fetchSave] Fetch error:', error);
    }
}

async function updateSave()
{
    try {
        var response = await fetch('/updateSave', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(graph.save)
        });
        
        if (!response.ok) {
            console.log('[updateSave] response:', response);
            throw new Error(`[updateSave] HTTP error! status: ${response.status}`);
        }
        var result = await response.json();
    } catch (error) {
        console.error('[updateSave] Fetch error:', error);
    }
}

async function insertEdge(sourceNode, targetNode)
{
    try {

        var data = {
            source: sourceNode,
            target: targetNode,
            label: '',
            data: {}
        }

        var response = await fetch('/insertEdge', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            console.log('[insertEdge] response:', response);
            alert(error);
            throw new Error(`[insertEdge] HTTP error! status: ${response.status}`);
        }
        var result = await response.json();
    } catch (error) {
        alert(error);
        console.error('[insertEdge] Fetch error:', error);
    }
}

////////////////////////////////////////////////////////////////////////////////

function updateNodeList()
{
    const categorySelect = graph.menu.AddNodeForm.htmlAddNodeFormCategory.value;
    const typeSelect = graph.menu.AddNodeForm.htmlAddNodeFormType.value;

    const nodeListSelect = graph.menu.AddNodeForm.htmlAddNodeFormNode;
    nodeListSelect.innerHTML = '';

    graph.nodes.forEach(function(node) {
        // console.log('Checking node:', node);
        if (node.data.category === categorySelect && node.data.type === typeSelect) {
            // console.log('Node data:', node.data);
            const option = document.createElement('option');
            option.value = node.data.id;
            option.text = node.data.label;
            nodeListSelect.appendChild(option);
        }
    });
}

async function updateView()
{
    if(! graph.save) {
        console.log('No save loaded, cannot update view.');
        graph.modals.displayOpenProjectModal();
        return;
    }

    var cydiv = document.getElementById('cy');

    var data = structuredClone(graph.graph);
    data.container = cydiv;
    
    // console.log('Update view with data:', data);
    graph.cy = cytoscape(data);

    graph.cy.on('select', 'node', function(evt){
        const selectedNodes = cy.$('node:selected');
        if (selectedNodes.length > 2) {
            graph.menu.AddEdgeForm.htmlAddEdgeFormSubmit.disabled = true;
            evt.target.unselect();
            return;
        }

        var node = evt.target;
        graph.selection.push(node.id());

        // console.log('Selected node:', node.id());
        // console.log('Current selection:', graph.selection);

        if(graph.selection.length < 2) {
            graph.menu.AddNodeForm.hide();
            graph.menu.AddEdgeForm.show();
        } else if(graph.selection.length == 2) {
            // console.log('Two nodes selected, showing add-edge form.');
            graph.menu.AddNodeForm.hide();
            graph.menu.AddEdgeForm.show();
            graph.menu.AddEdgeForm.htmlAddEdgeFormSubmit.disabled = false;
        } else {
            // console.log('Not two nodes selected, hiding add-edge form.');
            graph.menu.AddNodeForm.show();
            graph.menu.AddEdgeForm.hide();
            graph.menu.AddEdgeForm.htmlAddEdgeFormSubmit.disabled = true;
        }
    });

    graph.cy.on('unselect', 'node', function(evt){
        graph.cy.elements().unselect();
        graph.selection = [];
        graph.menu.AddEdgeForm.hide();
        graph.menu.AddNodeForm.show();
        graph.menu.AddEdgeForm.htmlAddEdgeFormSubmit.disabled = true;
    });
    
    const startNodes = graph.cy.nodes().filter(node => 
        graph.save.nodes.includes(node.id())
    );
    
    // console.log('Start nodes found:', startNodes.length, startNodes.map(n => n.id()));
    
    // Get all successor nodes (all descendants)
    const descendants = startNodes.successors();
    // console.log('Descendants found:', descendants.length);
    
    const allNodes = startNodes.union(descendants);
    // console.log('Total nodes to keep:', allNodes.length);
    
    graph.cy.elements().not(allNodes).remove();
    // console.log('Remaining elements:', graph.cy.elements().length);
    
    // Check layout config
    // console.log('Layout config:', graph.graph.layout);

    graph.cy.layout(graph.graph.layout).run();
}

//////////////////////////////////////////////////////////////////////////////

document.addEventListener('mousemove', function(e) {
    graph.menu.onMouseMove(e);
});

document.addEventListener('keydown', function(e) {
    graph.modals.onKeydown(e);
});


document.getElementById('new-doc-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = {
        name: document.getElementById('new-doc-form-name').value,
        nodes: []
    };
    
    try {
        const response = await fetch('/insertSave', {
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
        // console.log('new document Success:', result);
        
        e.target.reset();
        window.location.href = `/?save=${result.data.id}`;
        
    } catch (error) {
        console.error('Error:', error);
    }
});

document.getElementById('open-doc-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const id = document.getElementById('open-doc-form-id').value;
    window.location.href = `/?save=${id}`;
});

document.addEventListener("DOMContentLoaded", async function() {
    await fetchGraph();
    await fetchCategories();
    await fetchTypes();

    await fetchSaves();
    await fetchSave();
    await updateView();
});
