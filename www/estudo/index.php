<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8">
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1">
    <title>Visualização</title>

    <link rel="stylesheet" href="style.css">

    <script src="cytoscape.min.js"></script>
    <script src="db.js"></script>
  </head>
  <body>
    <div id="cy"></div>
    <div id="topMenu" class="show">Abrir Novo</div>
    <div id="sideMenu" class="show">Menu Lateral</div>
    <div id="infoPanel" class="show">Painel de Informações</div>
    <div id="modalk">
        <div id="modalHeader"><button id="modalClose">×</button></div>
        <div id="modalContent">
            <h2 id="modalTitle">Título do Modal</h2>
            <p>Conteúdo do modal vai aqui.</p>
        </div>
    </div>
    
    <script>
        (async function() {
            let data = {};
            try {
                var response = await fetch('http://192.168.7.5:8090/demo.php');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                var jsonData = await response.json();
                graphData = jsonData;
                graphData['container'] = document.getElementById('cy');

                /////////////////////////////////////////////////////////////////////////

                var response = await fetch('http://192.168.7.5:8090/status.php/getStatus');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                var jsonData = await response.json();
                statusData = jsonData['data'];

                for (const k in statusData) {
                    for(const i in graphData['elements']['nodes']) {
                        if (graphData['elements']['nodes'][i]['data']['id'] == statusData[k]['node_id']) {
                            
                            graphData['elements']['nodes'][i]['classes'] = graphData['elements']['nodes'][i]['classes'].filter(function(item) {
                                return item.indexOf('unknown') === -1
                            });

                            graphData['elements']['nodes'][i]['classes'].push('node-status-' + statusData[k]['status']);
                        }
                    }
                }

                console.log(graphData)

                var cy = cytoscape(graphData);
                // cy.add({
                //     group: 'nodes',
                //     data: { id: 'node-new', label: "Este item exemplifica uma \nobservação longa. um Post-it para comentários" },
                //     position: { x: 200, y: 100 },
                //     style: {
                //         "background-clip" : "none",
                //         "background-height" : "32px",
                //         "background-width" : "32px",
                //         "border-color" : "#e8e543",
                //         "background-color" : "#fffdcf",
                //         "color": "#000000",
                //         "border-width" : 2,
                //         "font-family" : "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
                //         "font-size" : 16,
                //         "label" : "data(label)",
                //         "text-valign" : "center",
                //         "text-halign" : "center",
                //         "text-margin-y" : 8,
                //         "text-wrap": "wrap",
                //         "shape" : "round-rectangle",
                //         "width" : "250",
                //         "height" : "120",
                //         "text-max-width": 200
                //     },
                //     selected: false,
                //     selectable: false,
                //     locked: false,
                //     grabbable: false,
                //     pannable: false,
                // });

                var modal = document.getElementById('modal');
                
                cy.on('tap', 'node', function(evt){
                    var node = evt.target;
                    modal.classList.add('show');
                    document.getElementById('modalTitle').innerText = node.data('label');
                });

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        modal.classList.remove('show');
                    }
                });

                document.addEventListener('mousemove', function(e) {
                    if (e.clientX <= 50) {
                        document.getElementById('modal').classList.add('show');
                    } else {
                        modal.classList.remove('show');
                    }
                });

                let offset = 0;
                setInterval(() => {
                    offset = (offset + 1) % 10;
                    cy.edges().style('line-dash-offset', offset);
                }, 100);
            } catch (error) {
                console.error('Error:', error);
            }
        })();

    </script>
  </body>
</html>