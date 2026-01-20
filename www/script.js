
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
        console.log('[fetchGraph] Graph data:', graphData.data);
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
        console.log('[fetchSaves] Saves:', saves);
    }
    catch (error) {
        console.error('[fetchSaves] Fetch error:', error);
    }
}

async function fetchSave()
{
    const urlParams = new URLSearchParams(window.location.search);
    const saveID = urlParams.get('save');
    console.log('[fetchSave] Fetching save with ID:', saveID);

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
        console.log('[fetchSave] Save data:', saveData);
        window.save = saveData['data'];
    } catch (error) {
        console.error('[fetchSave] Fetch error:', error);
    }
}

function updateNodeList()
{
    const categorySelect = document.getElementById('add-node-form-category').value;
    const typeSelect = document.getElementById('add-node-form-type').value;

    const nodeListSelect = document.getElementById('add-node-form-node');
    nodeListSelect.innerHTML = '';

    console.log('Selected category ID:', categorySelect);
    console.log('Selected type ID:', typeSelect);

    window.nodes.forEach(function(node) {
        console.log('Checking node:', node);
        if (node.data.category === categorySelect && node.data.type === typeSelect) {
            console.log('Node data:', node.data);
            const option = document.createElement('option');
            option.value = node.data.id;
            option.text = node.data.label;
            nodeListSelect.appendChild(option);
        }
    });
}

async function updateView()
{
    var cydiv = document.getElementById('cy');

    var data = structuredClone(window.graph);
    data.container = cydiv;
    console.log('Update view with data:', data);
    window.cy = cytoscape(data);

    const startNodes = window.cy.nodes().filter(node => 
        ['UserService2'].includes(node.id())
    );

    // Get all successor nodes (all descendants)
    const descendants = startNodes.successors();
    
    const nodes = startNodes.union(descendants);
    nodes.select();

    window.cy.layout(window.graph.layout).stop();
    window.cy.elements().not(nodes).remove();
    window.cy.layout(window.graph.layout).start();
    console.log(window.graph.layout);

    // const outgoingEdges = startNodes.outgoers('edge');
    // const descendants = startNodes.outgoers('node');
    // startNodes.union(descendants).union(outgoingEdges).select();

    // var selected = cy.nodes().filter(function( ele ){
    //     if (ele.isNode()) {
    //         if (ele.data('id') === 'UserService') {
    //             console.log('Node ele:', ele);
    //             return true;
    //         }
    //     }
    //     return false;
    // });

    // // selected.select();

    // var neighborhood = selected.neighborhood();
    // console.log('neighborhood:', neighborhood);
    // // neighborhood.select();

    // var components = selected.components();
    // console.log('components:', components);
    // // components.select();

    // var predecessors = selected.incomers();
    // console.log('incomers:', predecessors);
    // // predecessors.select();

    // var connectedNodes = selected.connectedNodes();
    // console.log('connectedNodes:', connectedNodes);
    // // connectedNodes.select();

    // var roots = selected.roots();
    // console.log('roots:', roots);
    // // roots.select();

    // var leaves = selected.leaves();
    // console.log('leaves:', leaves);
    // leaves.select();


    //window.cy.fit();
}

//////////////////////////////////////////////////////////////////////////////

document.addEventListener('mousemove', function(e) {
    const menu = document.getElementById('menu');
    const infoPanel = document.getElementById('info-panel');

    if (e.clientX <= 300) {
        menu.classList.add('show');
    } else {
        menu.classList.remove('show');
    }
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

    const formData = {
        category: document.getElementById('add-node-form-category').value,
        type: document.getElementById('add-node-form-type').value,
        node: document.getElementById('add-node-form-node').value,
    }
});

document.getElementById('new-doc-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = {
        id: document.getElementById('id').value,
        name: document.getElementById('name').value,
        data: {
            'nodes': []
        }
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

        window.saveID = formData.id;
        
    } catch (error) {
        console.error('Error:', error);
    }
});

await fetchGraph();
await fetchCategories();
await fetchTypes();

await fetchSaves();
await fetchSave();
await updateView();

console.log('Final graph:', window.graph);
console.log('Final saves:', window.saves);
console.log('Final save:', window.save);
