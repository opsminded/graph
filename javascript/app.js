import {Api} from './api.js';
import './info-panel.js';
import './menu.js';
import './modal-open-project.js';
import './modal-new-project.js';
import './notification.js';
import './project.js';

export class App extends HTMLElement {
    constructor() {
        super();
        this.api = new Api();
        this.statusUpdateTimer = null;
        this.attachShadow({ mode: "open" });
        this.render();
    }

    render() {
        this.shadowRoot.innerHTML = `
            <app-menu></app-menu>
            <app-open-project-modal></app-open-project-modal>
            <app-new-project-modal></app-new-project-modal>
            <app-info-panel></app-info-panel>
            <app-project></app-project>
            <app-notification></app-notification>
        `;

        this.initializeComponents();
        this.fetchData();
        this.setupEventListeners();
    }

    initializeComponents()
    {
        this.menu             = this.shadowRoot.querySelector('app-menu');
        this.modalNewProject  = this.shadowRoot.querySelector('app-new-project-modal');
        this.modalOpenProject = this.shadowRoot.querySelector('app-open-project-modal');
        this.infoPanel        = this.shadowRoot.querySelector('app-info-panel'); 
        this.project          = this.shadowRoot.querySelector('app-project');
        this.notification     = this.shadowRoot.querySelector('app-notification');
    }

    async fetchData()
    {
        this.api.fetchCategories().then(categories => {
            this.menu.populateCategories(categories);
        });

        this.api.fetchTypes().then(types => {
            this.menu.populateTypes(types);
        });

        this.menu.populateNodes([{ id: 1, label: 'Node X' }, { id: 2, label: 'Node Y' }]);
        
        this.api.fetchProjects().then(projects => {
            this.modalOpenProject.populateProjects(projects);
        });
    }

    setupEventListeners()
    {
        this.menu.addEventListener('login-btn-clicked', () => {
            alert('Não implementado ainda');
        });

        this.menu.addEventListener('new-prj-btn-clicked', () => {
            
            if (this.statusUpdateTimer !== null) {
                clearInterval(this.statusUpdateTimer);
                this.statusUpdateTimer = null;
                this.project.clear();
            }

            this.modalNewProject.show();
            this.modalOpenProject.hide();
        });

        this.menu.addEventListener('open-prj-btn-clicked', () => {

            if (this.statusUpdateTimer !== null) {
                clearInterval(this.statusUpdateTimer);
                this.statusUpdateTimer = null;
                this.project.clear();
            }

            this.modalNewProject.hide();
            this.modalOpenProject.show();
        });

        this.menu.addEventListener('export-btn-clicked', () => {
            this.project.export();
        });

        this.addEventListener('new-project', (event) => {
            const projectData = event.detail;
            this.newProject(projectData);
        });

        // Handle opening projects
        this.addEventListener('open-project', (event) => {
            this.openProject(event.detail.id);
        });

        this.addEventListener('category-changed', (event) => {
            alert(`Categoria alterada para: ${event.detail.categoryId}`);
        });

        this.boundKeyHandler = this.handleKeyPress.bind(this);
        document.addEventListener('keydown', this.boundKeyHandler);

        this.boundMouseHandler = this.handleMouseMove.bind(this);
        document.addEventListener('mousemove', this.boundMouseHandler);
    }

    async newProject(projectData) {
        console.log('Creating new project with data:', projectData);
        const project = await this.api.insertProject(projectData);
        this.modalNewProject.hide();
        this.notification.success(`Projeto "${project.name}" criado com sucesso!`);
        this.openProject(project.id);
    }

    async openProject(projectId) {
        this.modalOpenProject.hide();

        const project = await this.api.fetchProject(projectId);
        const projectGraph = await this.api.fetchProjectGraph(projectId);
        console.log('Opened project:', project);
        console.log('Project graph:', projectGraph);
        this.project.populateProject(project, projectGraph);
        this.startStatusUpdates(projectId);

        this.notification.success(`Projeto "${project.id}" aberto com sucesso!`);
    }

    startStatusUpdates(projectId) {
        this.updateNodeStatuses(projectId);
        if (this.statusUpdateTimer) {
            clearInterval(this.statusUpdateTimer);
        }

        this.statusUpdateTimer = setInterval(() => {
            this.updateNodeStatuses(projectId);
        }, 5000);
    }

    async updateNodeStatuses(projectId) {
        console.log('Updating node statuses...');
        const statuses = await this.api.fetchProjectStatus(projectId);
        console.log('Fetched node statuses:', statuses);
        this.project.updateNodeStatuses(statuses);
    }

    handleKeyPress(e) {
        this.menu.handleKeyPress(e);
    }

    handleMouseMove(e) {
        this.menu.handleMouseMove(e);
    }
}

customElements.define("app-root", App);
