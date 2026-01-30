
export class SideMenu extends HTMLElement {
    constructor() {
        super();
        const shadow = this.attachShadow({ mode: "open" });
        this.button = document.createElement("button");
        this.button.textContent = "Click Me!";
        this.shadowRoot.appendChild(this.button);
    }
}

customElements.define("SideMenu", SideMenu);
