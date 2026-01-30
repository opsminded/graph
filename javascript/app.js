
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
    }
}

customElements.define("app-root", App);
