"use strict";

import {Api} from './api.js';
import './components/menu.js';
import './components/notification.js';
import './components/project.js';

export class App extends HTMLElement {
    constructor() {
        super();
        this.api = new Api();

        // AbortController for automatic event listener cleanup
        this.abortController = new AbortController();
        this.render();
    }

    connectedCallback() {
        console.log("App connected");

        this.menu         = this.shadowRoot.querySelector('app-menu');
        this.project      = this.shadowRoot.querySelector('app-project');
        this.notification = this.shadowRoot.querySelector('app-notification');

        this.setupEventListeners();
    }

    disconnectedCallback() {
        console.log("App disconnected");
        this.abortController.abort();
    }

    render() {
        this.attachShadow({ mode: "open" });
        this.shadowRoot.innerHTML = `
            <app-menu keep-open></app-menu>
            <app-project></app-project>
            <app-notification></app-notification>
        `;
    }

    setupEventListeners()
    {
        this.addEventListener('new-prj-btn-clicked', () => {
            alert('new project');
        }, this.abortController.signal);

        this.addEventListener('open-prj-btn-clicked', () => {
            const project = {
                "id": "p1",
                "name": "Projeto 1",
                "author": "admin",
                "created_at": "2026-02-02T20:22:30+00:00",
                "updated_at": "2026-02-02T20:22:30+00:00",
                "data": []
            }
            const graph = {
                elements: {
                    nodes: [
                        { data: { id: 'a', label: 'Node A' } },
                        { data: { id: 'b', label: 'Node B' } },
                        { data: { id: 'c', label: 'Node C' } }
                    ],
                    edges: [
                        { data: { id: 'ab', source: 'a', target: 'b' } },
                        { data: { id: 'bc', source: 'b', target: 'c' } }
                    ]
                },
                layout: {
                    name: 'cose'
                },
                style: [
                    {
                        selector: 'node.node-status-healthy',
                        style: {
                            "background-color": "green"
                        }
                    },
                    {
                        selector: 'node.node-status-unhealthy',
                        style: {
                            "background-color": "red"
                        }
                    }
                ]
            }

            const status = [
                {
                    "node_id": "a",
                    "status": "healthy"
                },
                {
                    "node_id": "b",
                    "status": "healthy"
                },
                {
                    "node_id": "c",
                    "status": "unhealthy"
                }
            ]
            this.project.project = project;
            this.project.graph = graph;
            this.project.nodeStatus = status;

            history.pushState({ project: 1 }, '', '?project=1');
        }, this.abortController.signal);

        this.addEventListener('add-node-btn-clicked', (event) => {
            alert('add node');
        }, this.abortController.signal);

        this.addEventListener('import-node-btn-clicked', (event) => {
            alert('import node');
        }, this.abortController.signal);

        this.addEventListener('add-edge-btn-clicked', (event) => {
            alert('add edge');
        }, this.abortController.signal);

        this.addEventListener('export-btn-clicked', () => {
            this.project.export();
        }, this.abortController.signal);

        this.addEventListener('fit-btn-clicked', () => {
            this.project.fit();
        }, this.abortController.signal);
    }

    // async newProject(projectData) {
    //     console.log('Creating new project with data:', projectData);
    //     const project = await this.api.insertProject(projectData);
    //     this.modalNewProject.hide();
    //     this.notification.success(`Projeto "${project.name}" criado com sucesso!`);
    //     this.openProject(project.id);
    // }

    // async openProject(projectId) {
    //     this.modalOpenProject.hide();
    //     this.menu.showAddNodeForm();
    //     const project = await this.api.fetchProject(projectId);
    //     const projectGraph = await this.api.fetchProjectGraph(projectId);
    //     console.log('Opened project:', project);
    //     console.log('Project graph:', projectGraph);
    //     this.project.populateProject(project, projectGraph);
    //     this.startStatusUpdates(projectId);
    //     this.notification.success(`Projeto "${project.id}" aberto com sucesso!`);
    // }

    // startStatusUpdates(projectId) {
    //     this.updateNodeStatuses(projectId);
    //     if (this.statusUpdateTimer) {
    //         clearInterval(this.statusUpdateTimer);
    //     }

    //     this.statusUpdateTimer = setInterval(() => {
    //         this.updateNodeStatuses(projectId);
    //     }, 5000);
    // }

    // async updateNodeStatuses(projectId) {
    //     console.log('Updating node statuses...');
    //     const statuses = await this.api.fetchProjectStatus(projectId);
    //     console.log('Fetched node statuses:', statuses);
    //     this.project.updateNodeStatuses(statuses);
    // }

    // handleKeyPress(e) {
    //     this.menu.handleKeyPress(e);
    // }

    // handleMouseMove(e) {
    //     this.menu.handleMouseMove(e);
    // }
}

customElements.define("app-root", App);



// this.menu.addEventListener('new-prj-btn-clicked', () => {
            
//             if (this.statusUpdateTimer !== null) {
//                 clearInterval(this.statusUpdateTimer);
//                 this.statusUpdateTimer = null;
//                 this.project.clear();
//             }

//             this.modalNewProject.show();
//             this.modalOpenProject.hide();
//         });


// async fetchData()
//     {
//         this.api.fetchCategories().then(categories => {
//             this.menu.populateCategories(categories);
//         });

//         this.api.fetchTypes().then(types => {
//             this.menu.populateTypes(types);
//         });

//         this.api.fetchProjects().then(projects => {
//             this.modalOpenProject.populateProjects(projects);
//         });
//     }


// this.menu.addEventListener('open-prj-btn-clicked', () => {

//             if (this.statusUpdateTimer !== null) {
//                 clearInterval(this.statusUpdateTimer);
//                 this.statusUpdateTimer = null;
//                 this.project.clear();
//             }

//             this.modalNewProject.hide();
//             this.modalOpenProject.show();
//         });

// this.addEventListener('new-project', (event) => {
//             const projectData = event.detail;
//             this.newProject(projectData);
//         });





// // Handle opening projects
//         this.addEventListener('open-project', (event) => {
//             this.openProject(event.detail.id);
//         });

//         this.addEventListener('category-changed', async (event) => {
//             const categoryId = event.detail.categoryId;
//             const newTypes = await this.api.fetchCategoryTypes(categoryId);
//             this.menu.populateTypes(newTypes);
//             this.menu.populateNodes([]);
//         });

//         this.addEventListener('type-changed', async (event) => {
//             const typeId = event.detail.typeId;
//             const newNodes = await this.api.fetchTypeNodes(typeId);
//             this.menu.populateNodes(newNodes);
//         });

//         this.addEventListener('add-node-form-submitted', async (event) => {
//             const {nodeId} = event.detail;
            
//             if (this.project.projectId === null) {
//                 return;
//             }

//             const formData = {
//                 project_id: this.project.projectId,
//                 node_id: nodeId
//             };

//             await this.api.insertProjectNode(formData);
//             this.openProject(this.project.projectId);
//         });

//         this.addEventListener('add-edge-form-submitted', async (event) => {
//             const formData = {
//                 source : this.selectedNodes[0],
//                 target : this.selectedNodes[1],
//                 label: "connects",
//                 data: {}
//             }

//             let resp = await this.api.insertEdge(formData);
//             console.log('Edge insertion response:', resp);
//         });

//         this.addEventListener('node-selected', (event) => {
//             this.selectedNodes = event.detail.selectedNodes;

//             if (this.selectedNodes.length === 2) {
//                 this.menu.showAddEdgeForm();
//             }
//         });