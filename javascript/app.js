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

    async openProject(id) {
        const project = await this.api.fetchProject(id);
        const projectGraph = await this.api.fetchProjectGraph(id);
        console.log('Opened project:', project);
        this.project.populateProject(project, projectGraph);
        this.modalOpenProject.hide();
    }
}

customElements.define("app-root", App);
