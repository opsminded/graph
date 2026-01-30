
export class Graph extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this.render();
    }

    async render() {
        this.shadowRoot.innerHTML = "";
        const nav = document.createElement("nav");
        const link = document.createElement("a");
        link.textContent = "Go to Graph Page";
        link.addEventListener("click", () => {
            this.dispatchEvent(new CustomEvent("navigate", {
                detail: { page },
                bubbles: true 
            }));
        });
        nav.appendChild(link);
        this.shadowRoot.appendChild(nav);
    }
}

customElements.define("Graph", Graph);
