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

                    background: #e4b7b7;

                    left: 400px;
                    top: 0;
                    bottom: 0;
                    right: 0;

                    width: 100%;
                    height: 100%;
                    
                    z-index: 100;
                }

                #project-container h2 {
                    position: absolute;
                    left: 420px;
                    top: 10px;
                    z-index: 101;
                }
            </style>
            <div id="project-container">
                <h2 id="project-title">Project</h2>
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

        this.cy.layout(graph.layout).run();
        this.cy.fit();
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

                console.log("final node classes", node.classes());
            }
        });
    }
}

customElements.define('app-project', Project);