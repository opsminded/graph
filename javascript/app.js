
import './menu.js';

export class App extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this.render();
    }

    async render() {
        this.shadowRoot.innerHTML = "";
        const menu = document.createElement("app-menu");
        const modalOpenProject = document.createElement("app-modal-open-project");
        const modalNewProject = document.createElement("app-modal-new-project");
        this.shadowRoot.append(menu, modalOpenProject, modalNewProject);
    }
}

customElements.define("app-root", App);
