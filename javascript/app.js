"use strict";

import {Api} from './api.js';
import {EVENTS} from './events.js';
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
        this.addEventListener(EVENTS.NEW_PROJECT_BUTTON_CLICKED, () => {
            this.newProjectModal.show();
            this.openProjectModal.hide();
            this.project.closeProject();
        }, this.abortController.signal);

        this.addEventListener(EVENTS.OPEN_PROJECT_BUTTON_CLICKED, async () => {
            try {
                const projects = await this.api.fetchProjects();
                this.openProjectModal.show(projects);
                this.newProjectModal.hide();
            } catch (error) {
                this.notification.error(`Erro ao carregar projetos: ${error.message}`);
            }
            this.project.closeProject();
        }, this.abortController.signal);

        this.addEventListener(EVENTS.NEW_PROJECT, async (event) => {
            const projectData = event.detail;
            try {
                const project = await this.api.insertProject(projectData);
                this.notification.success(`Projeto "${project.name}" criado com sucesso!`);
                this.openProject(project.id);
            } catch (error) {
                this.notification.error(`Erro ao criar projeto: ${error.message}`);
            }
        }, this.abortController.signal);

        this.addEventListener(EVENTS.OPEN_PROJECT, async (event) => {
            const projectId = event.detail.id;
            this.openProject(projectId);
        }, this.abortController.signal);

        this.addEventListener(EVENTS.RELOAD_PROJECT, () => {
            if (this.project.project) {
                this.openProject(this.project.project.id);
            }
        }, this.abortController.signal);

        this.addEventListener(EVENTS.MENU_HIDDEN, () => {
            this.project.cyContainer.style.left = "0px";
            this.project.cyContainer.style.width = "100%";
            this.project.cy.resize();
            this.project.cy.fit(this.project.cy.elements(), 100);
        }, this.abortController.signal);

        this.addEventListener('show-notification', (event) => {
            const { message, type } = event.detail;
            this.notification[type](message);
        }, this.abortController.signal);

        document.addEventListener(
            "keydown",
            (e) => {
                if (e.key === "Escape") {
                    this.newProjectModal.hide();
                    this.openProjectModal.hide();
                }
            },
            this.abortController.signal,
        );
    }

    async openProject(projectId) {
        console.log('Opening project:', projectId);
        try {
            const [project, graph, status] = await Promise.all([
                this.api.fetchProject(projectId),
                this.api.fetchProjectGraph(projectId),
                this.api.fetchProjectStatus(projectId)
            ]);
            console.log('Fetched project data:', { project, graph, status });
            this.project.openProject(project, graph, status);
            this.openProjectModal.hide();

            if (this.menu.hasAttribute("keep-open")) {
                this.project.cyContainer.style.left = "340px";
                this.project.cyContainer.style.width = "calc(100% - 340px)";
                this.project.cy.resize();
                this.project.cy.fit(this.project.cy.elements(), 100);
            }

            history.pushState({ project: projectId }, '', `?project=${projectId}`);
        } catch (error) {
            console.error('Error opening project:', error);
            this.notification.error(`Erro ao carregar dados do projeto: ${error.message}`);
        }
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
