"use strict";

export class InfoPanel extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this.render();
    }

    render() {
        this.shadowRoot.innerHTML = `
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

                #info-panel.show {
                    display: block;
                }
            </style>
            <div id="info-panel">
                <h2>Propriedades do Nó</h2>
                <div id="info-panel-content">
                    <p><strong>ID:</strong> <span id="info-node-id"></span></p>
                    <p><strong>Rótulo:</strong> <span id="info-node-label"></span></p>
                    <p><strong>Categoria:</strong> <span id="info-node-category"></span></p>
                    <p><strong>Tipo:</strong> <span id="info-node-type"></span></p>
                </div>
            </div>
        `;
    }
}

customElements.define("app-info-panel", InfoPanel);
