
export class NewProjectModal extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this.render();
    }

    async render() {
        this.shadowRoot.innerHTML = "";
        const div = document.createElement("p");
        div.textContent = "This is the new project modal component.";
        this.shadowRoot.appendChild(div);
    }
}

customElements.define("app-new-project-modal", NewProjectModal);
