
import './menu.js';
import './modal-open-project.js';
export class App extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this.render();
    }

    render() {
        this.shadowRoot.innerHTML = `
            <app-menu></app-menu>
            <app-open-project-modal></app-open-project-modal>
        `;

        const menu = this.shadowRoot.querySelector('app-menu');
        menu.addEventListener('close-menu-btn-clicked', () => { 
            alert('Close menu to be implemented in App');
         });
        menu.addEventListener('login-btn-clicked', () => {
            alert('Login to be implemented in App');
         });

        const categories = [{ id: 1, name: 'Category 1' }, { id: 2, name: 'Category 2' }];
        menu.populateCategories(categories);

        const types = [{ id: 1, name: 'Type A' }, { id: 2, name: 'Type B' }];
        menu.populateTypes(types);

        const nodes = [{ id: 1, label: 'Node X' }, { id: 2, label: 'Node Y' }];
        menu.populateNodes(nodes);

        menu.addEventListener('open-prj-btn-clicked', () => {
            const modal = this.shadowRoot.querySelector('app-open-project-modal');
            modal.show();
        });

        const modal = this.shadowRoot.querySelector('app-open-project-modal');
        const projects = [{ id: 1, name: 'Project A' }, { id: 2, name: 'Project B' }];
        modal.populateProjects(projects);
    }
}

customElements.define("app-root", App);
