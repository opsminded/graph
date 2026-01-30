import {Api} from './api.js';
import './info-panel.js';
import './menu.js';
import './modal-open-project.js';
import './modal-new-project.js';
import './project.js';

export class App extends HTMLElement {
    constructor() {
        super();

        this.api = new Api();

        this.statusUpdateTimer = null;

        this.attachShadow({ mode: "open" });
        this.render();
    }

    async render() {
        this.shadowRoot.innerHTML = `
            <app-menu></app-menu>
            <app-open-project-modal></app-open-project-modal>
            <app-new-project-modal></app-new-project-modal>
            <app-info-panel></app-info-panel>
            <app-project></app-project>
        `;

        // Initialize components
        this.menu             = this.shadowRoot.querySelector('app-menu');
        this.modalNewProject  = this.shadowRoot.querySelector('app-new-project-modal');
        this.modalOpenProject = this.shadowRoot.querySelector('app-open-project-modal');
        this.infoPanel        = this.shadowRoot.querySelector('app-info-panel'); 
        this.project          = this.shadowRoot.querySelector('app-project');

        // Fetch data
        this.categories = await this.api.fetchCategories();
        this.types      = await this.api.fetchTypes();
        const projects  = await this.api.fetchProjects();
        this.nodes      = [{ id: 1, label: 'Node X' }, { id: 2, label: 'Node Y' }];

        // Populate menu and modals
        this.menu.populateCategories(this.categories);
        this.menu.populateTypes(this.types);
        this.menu.populateNodes(this.nodes);
        this.modalOpenProject.populateProjects(projects);

        this.menu.addEventListener('login-btn-clicked', () => {
            alert('Login to be implemented in App');
        });

        this.menu.addEventListener('new-prj-btn-clicked', () => {
            this.modalNewProject.show();
            this.modalOpenProject.hide();
        });

        this.menu.addEventListener('open-prj-btn-clicked', () => {
            this.modalNewProject.hide();
            this.modalOpenProject.show();
        });

        // Handle opening projects
        this.modalOpenProject.addEventListener('open-project', (event) => {
            this.openProject(event.detail.id);
        });
    }

    async openProject(projectId) {
        this.modalOpenProject.hide();

        const project = await this.api.fetchProject(projectId);
        const projectGraph = await this.api.fetchProjectGraph(projectId);

        console.log('Opened project:', project);
        console.log('Project graph:', projectGraph);
        this.project.populateProject(project, projectGraph);
        this.startStatusUpdates(projectId);
    }

    disconnectedCallback() {
        if (this.statusUpdateTimer) {
            clearInterval(this.statusUpdateTimer);
        }
    }

    async startStatusUpdates(projectId) {
        await this.updateNodeStatuses(projectId);
        if (this.statusUpdateTimer) {
            clearInterval(this.statusUpdateTimer);
        }

        this.statusUpdateTimer = setInterval(async () => {
            await this.updateNodeStatuses(projectId);
        }, 5000);
    }

    async updateNodeStatuses(projectId) {
        console.log('Updating node statuses...');
        const statuses = await this.api.fetchProjectStatus(projectId);
        console.log('Fetched node statuses:', statuses);
        this.project.updateNodeStatuses(statuses);
    }
}

customElements.define("app-root", App);
