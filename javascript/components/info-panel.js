"use strict";

export class InfoPanel extends HTMLElement {

    static observedAttributes = ["node"]

    constructor() {
        super();
        // AbortController for automatic event listener cleanup
        this.abortController = new AbortController();
        this.render();
    }

    connectedCallback() {
        console.log("InfoPanel connected");
        this.panel = this.shadowRoot.getElementById("info-panel");

        this.infoNodeId = this.shadowRoot.getElementById("info-node-id");
        this.infoNodeLabel = this.shadowRoot.getElementById("info-node-label");
        this.infoNodeCategory = this.shadowRoot.getElementById("info-node-category");
        this.infoNodeType = this.shadowRoot.getElementById("info-node-type");
        this.infoNodeOtherProperties = this.shadowRoot.getElementById("info-node-other-properties");
    }

    disconnectedCallback() {
        console.log("InfoPanel disconnected");
        this.abortController.abort();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name === "node") {
            console.log(`InfoPanel attribute changed: ${name} from ${oldValue} to ${newValue}`);
        }
    }

    set node(value) {
        if (value === null || value === "null" || value === "") {
            console.log("Clearing node info panel");
            this.setAttribute("node", "");
            this.panel.style.display = "none";
            return;
        }

        const data = JSON.parse(value);
        console.log("Setting node:", data);
        this.setAttribute("node", value);
        
        this.panel.style.display = "block";
        this.infoNodeId.textContent = data.id || "N/A";
        this.infoNodeLabel.textContent = data.label || "N/A";
        this.infoNodeCategory.textContent = data.category || "N/A";
        this.infoNodeType.textContent = data.type || "N/A";

        this.infoNodeOtherProperties.innerHTML = "";
        for (const [key, val] of Object.entries(data['data'])) {
            const p = document.createElement("p");
            p.innerHTML = `<strong>${val.key}:</strong> ${val.value}`;
            this.infoNodeOtherProperties.appendChild(p);
        }
    }

    get node() {
        console.log("Getting node:", this.getAttribute("node"));
        const data = JSON.parse(this.getAttribute("node"));
        return data;
    }

    getStyles() {
        return `
            <style>
                #info-panel {
                    position: absolute;

                    right: 0;
                    top: 0;

                    width: 40%;
                    height: 100%;
                    
                    background-color: #fff;
                    border-left: 1px solid #CCC;
                    padding: 10px;
                    
                    display: none;
                    z-index: 400;
                }
            </style>
        `;
    }

    getTemplate() {
        return `
            <div id="info-panel">
                <h2>Propriedades do Nó</h2>
                <div id="info-panel-content">
                    <p><strong>ID:</strong>        <span id="info-node-id"></span></p>
                    <p><strong>Rótulo:</strong>    <span id="info-node-label"></span></p>
                    <p><strong>Categoria:</strong> <span id="info-node-category"></span></p>
                    <p><strong>Tipo:</strong>      <span id="info-node-type"></span></p>

                    <h3>Outras Propriedades</h3>
                    <div id="info-node-other-properties">
                    </div>
                </div>
            </div>
        `;
    }
    
    render() {
        this.attachShadow({ mode: "open" });
        this.shadowRoot.innerHTML = this.getStyles() + this.getTemplate();
    }
}

customElements.define("app-info-panel", InfoPanel);
