"use strict";

import {Api} from './api.js';
import './components/menu.js';
import './components/notification.js';
import './components/project.js';
import './modals/new-project.js';
import './modals/open-project.js';
export class App extends HTMLElement {

    constructor()
    {
        super();
        this.api = new Api();

        this.statusUpdateTimer = null;

        // AbortController for automatic event listener cleanup
        this.abortController = new AbortController();
        this.render();
    }

    connectedCallback()
    {
        this.menu             = this.shadowRoot.querySelector('app-menu');
        this.project          = this.shadowRoot.querySelector('app-project');
        this.notification     = this.shadowRoot.querySelector('app-notification');
        this.newProjectModal  = this.shadowRoot.querySelector('app-new-project-modal');
        this.openProjectModal = this.shadowRoot.querySelector('app-open-project-modal');
        this.setupEventListeners();
    }

    disconnectedCallback()
    {
        this.abortController.abort();
    }

    setupEventListeners()
    {
        this.addEventListener('new-prj-btn-clicked', () => {
            this.newProjectModal.show();
            this.openProjectModal.hide();

            if (this.statusUpdateTimer) {
                clearInterval(this.statusUpdateTimer);
            }
        }, this.abortController.signal);

        this.addEventListener('open-prj-btn-clicked', () => {
            this.api.fetchProjects().then(projects => {
                this.openProjectModal.show(projects);
                this.newProjectModal.hide();
            }).catch(error => {
                this.notification.error(`Erro ao carregar projetos: ${error.message}`);
            });
            if (this.statusUpdateTimer) {
                clearInterval(this.statusUpdateTimer);
            }
        }, this.abortController.signal);

        this.addEventListener('new-project', (event) => {
            const projectData = event.detail;
            this.api.insertProject(projectData).then((project) => {
                this.notification.success(`Projeto "${project.name}" criado com sucesso!`);
            }).catch(error => {
                this.notification.error(`Erro ao criar projeto: ${error.message}`);
            });
        }, this.abortController.signal);

        this.addEventListener('open-project', (event) => {
            const projectId = event.detail.id;
            
            Promise.all([
                this.api.fetchProject(projectId),
                this.api.fetchProjectGraph(projectId),
                this.api.fetchProjectStatus(projectId)
            ]).then(([project, graph, status]) => {
                this.project.project = project;
                this.project.graph = graph;
                this.project.nodeStatus = status;

                if (this.statusUpdateTimer) {
                    clearInterval(this.statusUpdateTimer);
                }

                this.statusUpdateTimer = setInterval(() => {
                    this.api.fetchProjectStatus(projectId).then(statuses => {
                        console.log('Atualizando status dos nós do projeto');
                        this.project.nodeStatus = statuses;
                    }).catch(error => {
                        console.error('Erro ao atualizar status dos nós:', error);
                    });
                }, 5000);

                this.openProjectModal.hide();
            }).catch(error => {
                alert(`Error fetching project data: ${error.message}`);
            });

            history.pushState({ project: projectId }, '', `?project=${projectId}`);
        });


    }

    render() {
        this.attachShadow({ mode: "open" });
        this.shadowRoot.innerHTML = `
            <app-menu keep-open></app-menu>
            <app-project></app-project>
            <app-notification></app-notification>
            <app-new-project-modal></app-new-project-modal>
            <app-open-project-modal></app-open-project-modal>
        `;
    }
}

customElements.define("app-root", App);
