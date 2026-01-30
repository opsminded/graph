import {Api} from './api.js';
import './menu.js';
import './modal-open-project.js';
import './modal-new-project.js';
import './info-panel.js';

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
        `;

        const menu = this.shadowRoot.querySelector('app-menu');
        menu.addEventListener('close-menu-btn-clicked', () => { 
            // Handle menu close if needed
        });

        menu.addEventListener('login-btn-clicked', () => {
            alert('Login to be implemented in App');
        });

        const categories = await this.api.fetchCategories();
        menu.populateCategories(categories);

        const types = await this.api.fetchTypes();
        menu.populateTypes(types);

        const nodes = [{ id: 1, label: 'Node X' }, { id: 2, label: 'Node Y' }];
        menu.populateNodes(nodes);

        menu.addEventListener('new-prj-btn-clicked', () => {
            const modalNewProject = this.shadowRoot.querySelector('app-new-project-modal');
            const modalOpenProject = this.shadowRoot.querySelector('app-open-project-modal');
            modalNewProject.show();
            modalOpenProject.hide();
        });

        menu.addEventListener('open-prj-btn-clicked', () => {
            const modalNewProject = this.shadowRoot.querySelector('app-new-project-modal');
            const modalOpenProject = this.shadowRoot.querySelector('app-open-project-modal');
            modalNewProject.hide();
            modalOpenProject.show();
        });

        const modalOpenProject = this.shadowRoot.querySelector('app-open-project-modal');
        
        const projects = await this.api.fetchProjects();
        modalOpenProject.populateProjects(projects);
    }
}

customElements.define("app-root", App);
