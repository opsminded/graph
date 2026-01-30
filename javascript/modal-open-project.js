
export class OpenProjectModal extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this.render();
    }

    async render() {
        this.shadowRoot.innerHTML = "";
        const div = document.createElement("p");
        div.textContent = "This is the open project modal component.";
        this.shadowRoot.appendChild(div);
    }
}

customElements.define("app-open-project-modal", OpenProjectModal);
