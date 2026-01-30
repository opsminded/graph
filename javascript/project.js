import cytoscape from "./cytoscape.esm.min.mjs";

export class Project extends HTMLElement
{
    constructor()
    {
        super();
        this.attachShadow({ mode: "open" });
        this.render();
    }

    render()
    {
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

        this.projectTitle  = this.shadowRoot.getElementById('project-title');
        this.cyContainer = this.shadowRoot.getElementById('cy');
    }

    populateProject(project, graph)
    {
        this.projectTitle.textContent = project.id;

        graph.container = this.cyContainer;
        this.cy = cytoscape(graph);

        console.log('Current graphhhhhhhh:', graph);
        this.cy.layout(graph.layout).run();
        this.cy.fit();
    }

    clear()
    {
        if (this.cy) {
            this.cy.destroy();
            this.cy = null;
        }
        this.projectTitle.textContent = '';
    }

    updateNodeStatuses(statusUpdates)
    {
        console.log('updateNodeStatuses called with:', statusUpdates);
        statusUpdates.forEach(update => {
            const node = this.cy.$(`#${update.node_id}`);
            
            let classes = node.classes();

            classes.forEach(cls => { if (cls.startsWith("node-status")) { node.removeClass(cls); } });

            if (node) {
                node.addClass(`node-status-${update.status}`);
            }
        });
    }

    export()
    {
        let pngData = this.cy.png({
            full: true,
        });
        
        let link = document.createElement('a');
        link.href = pngData;
        link.download = 'graph.png';
        link.click();
    }
}

customElements.define('app-project', Project);