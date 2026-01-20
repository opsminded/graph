
// contains the global graph data
window.graph = {};

// contains the list of categories
window.categories = [];

// contains the list of types
window.types = [];

// contains the list of nodes
window.nodes = [];

// contains the list of saves for the user to select and open
window.saves = [];

// contains the currently opened save
window.save = null;

window.keepClosed = false;

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
        window.graph = graphData['data'];
        window.nodes = window.graph.elements.nodes;
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
        window.categories = categoriesData['data'];
    } catch (error) {
        console.error('[fetchCategories] Fetch error:', error);
    }

    // fill the form select with categories
    var categorySelect = document.getElementById('add-node-form-category');
    window.categories.forEach(function(category) {
        var option = document.createElement('option');
        option.value = category.id;
        option.text = category.name;
        categorySelect.appendChild(option);
    });
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
        window.types = typesData['data'];
    }
    catch (error) {
        console.error('[fetchTypes] Fetch error:', error);
    }

    // fill the form select with types
    var typeSelect = document.getElementById('add-node-form-type');
    window.types.forEach(function(type) {
        var option = document.createElement('option');
        option.value = type.id;
        option.text = type.name;
        typeSelect.appendChild(option);
    });
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
        window.saves = savesData['data'];
        
        // fill the form select with saves
        var saveSelect = document.getElementById('open-doc-form-id');
        window.saves.forEach(function(save) {
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
        window.save = saveData['data'];
        // console.log('[fetchSave] Loaded save:', window.save);
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
            body: JSON.stringify(window.save)
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

////////////////////////////////////////////////////////////////////////////////

function displayModal() {
    var modal = document.getElementById('modal');
    modal.classList.add('show');
}

function closeModal() {
    var modal = document.getElementById('modal');
    modal.classList.remove('show');
}

function displayNewDocModal() {
    displayModal();
    closeOpenDocModal();

    var newDocModal = document.getElementById('modal-new-doc');
    newDocModal.classList.add('show');
}

function closeNewDocModal() {
    var newDocModal = document.getElementById('modal-new-doc');
    newDocModal.classList.remove('show');
}

function displayOpenDocModal() {
    displayModal();
    closeNewDocModal();
    
    var openDocModal = document.getElementById('modal-open-doc');
    openDocModal.classList.add('show');
}

function closeOpenDocModal() {
    var openDocModal = document.getElementById('modal-open-doc');
    openDocModal.classList.remove('show');
}

function updateNodeList()
{
    const categorySelect = document.getElementById('add-node-form-category').value;
    const typeSelect = document.getElementById('add-node-form-type').value;

    const nodeListSelect = document.getElementById('add-node-form-node');
    nodeListSelect.innerHTML = '';

    // console.log('Selected category ID:', categorySelect);
    // console.log('Selected type ID:', typeSelect);

    window.nodes.forEach(function(node) {
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
    if(! window.save) {
        console.log('No save loaded, cannot update view.');
        displayOpenDocModal();
        return;
    }

    var cydiv = document.getElementById('cy');

    var data = structuredClone(window.graph);
    data.container = cydiv;
    
    // console.log('Update view with data:', data);
    window.cy = cytoscape(data);
    
    const startNodes = window.cy.nodes().filter(node => 
        window.save.nodes.includes(node.id())
    );
    
    // console.log('Start nodes found:', startNodes.length, startNodes.map(n => n.id()));
    
    // Get all successor nodes (all descendants)
    const descendants = startNodes.successors();
    // console.log('Descendants found:', descendants.length);
    
    const allNodes = startNodes.union(descendants);
    // console.log('Total nodes to keep:', allNodes.length);
    
    window.cy.elements().not(allNodes).remove();
    // console.log('Remaining elements:', window.cy.elements().length);
    
    // Check layout config
    // console.log('Layout config:', window.graph.layout);

    window.cy.layout(window.graph.layout).run();
}

//////////////////////////////////////////////////////////////////////////////

document.addEventListener('mousemove', function(e) {
    const menu = document.getElementById('menu');
    
    if(e.clientX > 300) {
        if (window.keepClosed) {
            menu.classList.add('hide');
        }
    }

    if(e.clientX <= 300) {
        menu.classList.remove('hide');
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var modal = document.getElementById('modal');
        modal.classList.remove('show');

        var newDocModal = document.getElementById('modal-new-doc');
        var openDocModal = document.getElementById('modal-open-doc');

        newDocModal.classList.remove('show');
        openDocModal.classList.remove('show');
    }
});

document.getElementById('close-menu-btn').addEventListener('click', function(e){
    window.keepClosed = !window.keepClosed;


    if (window.keepClosed) {
        document.getElementById('close-menu-btn').textContent = 'fixar';
    } else {
        document.getElementById('close-menu-btn').textContent = 'fechar';
    }

    console.log('Menu will stay closed:', window.keepClosed);
});

document.getElementById('new-doc-btn').addEventListener('click', function(){
    displayNewDocModal();
});

document.getElementById('open-doc-btn').addEventListener('click', function(){
    displayOpenDocModal();
});

document.getElementById('add-node-form-category').addEventListener('change', function(e) {
    const selectedCategoryID = e.target.value;
    updateNodeList();
});

document.getElementById('add-node-form-type').addEventListener('change', function(e) {
    const selectedTypeID = e.target.value;
    updateNodeList();
});

document.getElementById('add-node-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const id = document.getElementById('add-node-form-node').value;
    console.log('Adding node with ID:', id);

    if(window.save.nodes.includes(id)) {
        console.log('Node already in save, not adding:', id);
        return;
    }
    window.save.nodes.push(id);
    await updateSave();
    await updateView();
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
        console.log('new document Success:', result);
        
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

await fetchGraph();
await fetchCategories();
await fetchTypes();

await fetchSaves();
await fetchSave();
await updateView();

// console.log('Final graph:', window.graph);
// console.log('Final saves:', window.saves);
// console.log('Final save:', window.save);
