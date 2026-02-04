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

        const params = new URLSearchParams(window.location.search);
        const projectId = params.get("project");
        if (projectId) {
            this.openProject(projectId);
        }

        window.addEventListener("popstate", () => {
            const params = new URLSearchParams(window.location.search);
            const projectId = params.get("project"); // update component accordingly
            this.openProject(projectId);
        }, this.abortController.signal);
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
            this.project.project = null;
        }, this.abortController.signal);

        this.addEventListener('open-prj-btn-clicked', () => {
            this.api.fetchProjects().then(projects => {
                this.openProjectModal.show(projects);
                this.newProjectModal.hide();
            }).catch(error => {
                this.notification.error(`Erro ao carregar projetos: ${error.message}`);
            });
            this.project.project = null;
        }, this.abortController.signal);

        this.addEventListener('new-project', (event) => {
            const projectData = event.detail;
            this.api.insertProject(projectData).then((project) => {
                this.notification.success(`Projeto "${project.name}" criado com sucesso!`);
                this.openProject(project.id);
            }).catch(error => {
                this.notification.error(`Erro ao criar projeto: ${error.message}`);
            });
        }, this.abortController.signal);

        this.addEventListener('open-project', (event) => {
            const projectId = event.detail.id;
            this.openProject(projectId);
        });

        this.addEventListener('reload-project-requested', () => {
            if (this.project.project) {
                this.openProject(this.project.project.id);
            }
        }, this.abortController.signal);
    }

    openProject(projectId) {
        Promise.all([
                this.api.fetchProject(projectId),
                this.api.fetchProjectGraph(projectId),
                this.api.fetchProjectStatus(projectId)
            ]).then(([project, graph, status]) => {
                this.project.openProject(project, graph, status);
                this.openProjectModal.hide();
                history.pushState({ project: projectId }, '', `?project=${projectId}`);
            }).catch(error => {
                alert(`Error fetching project data: ${error.message}`);
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
