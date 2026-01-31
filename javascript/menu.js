"use strict";

export class Menu extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this.keepClosed = false;
        this.render();
    }

    render() {
        this.shadowRoot.innerHTML = `
            <style>
                #menu {
                    position: absolute;

                    left: 0;
                    top: 0;
                    width: 270px;
                    height: 100%;
                    padding: 10px 0;

                    text-align: center;

                    background-color: #FAF9F5;

                    border-right: 1px solid #CCC;
                    z-index: 500;
                }

                #menu h2 {
                    font-size: 24px;
                    line-height: 24px;
                    margin: 10px 0;
                    padding: 0;
                }

                #menu.hide {
                    display: none;
                }

                #menu > div:first-of-type {
                    text-align: right;
                    padding: 0 10px;
                }

                #menu h2 span {
                    font-weight: bold;
                    color: #B62B2B;
                }
                
                #user-panel {
                    background-color: #FFFFFF;
                    border-top: 1px solid #CCC;
                    border-bottom: 1px solid #CCC;
                    padding-bottom: 20px;
                }

                #user-panel button {
                    margin: 0 10px;
                }

                #add-node-form {
                    display: block;
                }

                #add-node-form.hide {
                    display: none;
                }

                #add-edge-form {
                    display: none;
                }

                #add-edge-form.show {
                    display: block;
                }
            </style>
            <div id="menu">
                <div><button id="close-menu-btn">X</button></div>
                <img src="/images/logo.png" alt="Logo" width="32" height="32">
                <h2><span>Brades</span>ketch</h2>

                <div id="user-panel">
                    <p><button id="login-btn" title="Entrar">Entrar</button></p>
                    <p>
                        <button id="new-prj-btn" title="Novo Projeto">Novo</button>
                        <button id="open-prj-btn" title="Abrir Projeto">Abrir</button>
                    </p>
                </div>

                <form id="add-node-form">
                    <h3>Adicionar Item</h3>
                    <p>
                        <label for="add-node-form-category">Categoria:<br>
                            <select id="add-node-form-category" name="category" required>
                                <option>Selecione</option>
                            </select>
                        </label>
                    </p>

                    <p>
                        <label for="add-node-form-type">Tipo:<br>
                            <select id="add-node-form-type" name="type" required>
                                <option>Selecione</option>
                            </select>
                        </label>
                    </p>

                    <p>
                        <label for="add-node-form-node">Item:<br>
                            <select id="add-node-form-node" name="node" required>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </label>
                    </p>

                    <p><button id="add-node-form-submit" type="submit">Adicionar Item</button></p>
                </form>

                <form id="add-edge-form">
                    <h3>Adicionar Conexão</h3>
                    <p><button id="add-edge-form-submit" type="submit" disabled>Adicionar Conexão</button></p>
                </form>

                <p>
                    <button id="export-btn" type="button">Exportar</button>
                </p>

                <p>
                    <button id="fit-btn" type="button">Ajustar</button>
                </p>
            </div>
        `;

        this.menu = this.shadowRoot.getElementById("menu");

        document.addEventListener("keydown", (event) => {
            if (event.key === "m" || event.key === "M") {
                this.show();
            }
        });

        const closeMenuBtn = this.shadowRoot.getElementById("close-menu-btn");
        closeMenuBtn.addEventListener("click", () => {
            this.hide();
            this.keepClosed = !this.keepClosed;
            closeMenuBtn.textContent = this.keepClosed ? "fixar" : "X";
            this.dispatchEvent(new CustomEvent("close-menu-btn-clicked", {bubbles: true, composed: true}));
        });

        const loginBtn = this.shadowRoot.getElementById("login-btn");
        loginBtn.addEventListener("click", () => {
            alert("Login - Em construção");
            this.dispatchEvent(new CustomEvent("login-btn-clicked", {bubbles: true, composed: true}));
        });

        const newPrjBtn = this.shadowRoot.getElementById("new-prj-btn");
        newPrjBtn.addEventListener("click", () => {
            this.dispatchEvent(new CustomEvent("new-prj-btn-clicked", {bubbles: true, composed: true}));
        });

        const openPrjBtn = this.shadowRoot.getElementById("open-prj-btn");
        openPrjBtn.addEventListener("click", () => {
            this.dispatchEvent(new CustomEvent("open-prj-btn-clicked", {bubbles: true, composed: true}));
        });

        const addNodeForm = this.shadowRoot.getElementById("add-node-form");
        addNodeForm.addEventListener("submit", (event) => {
            event.preventDefault();
            const nodeId = addNodeForm.elements['add-node-form-node'].value;
            this.dispatchEvent(new CustomEvent("add-node-form-submitted", {
                bubbles: true,
                composed: true,
                detail: {nodeId}
            }));
        });

        const addEdgeForm = this.shadowRoot.getElementById("add-edge-form");
        addEdgeForm.addEventListener("submit", (event) => {
            event.preventDefault();
            alert("Adicionar Conexão - Em construção");
        });

        const exportBtn = this.shadowRoot.getElementById("export-btn");
        exportBtn.addEventListener("click", (e) => {
            e.preventDefault();
            this.dispatchEvent(new CustomEvent("export-btn-clicked", {bubbles: true, composed: true}));
        });

        const fitBtn = this.shadowRoot.getElementById("fit-btn");
        fitBtn.addEventListener("click", () => {
            alert("Ajustar - Em construção");
        });

        //////////////////////////////////////////////

        this.categorySelect = this.shadowRoot.getElementById("add-node-form-category");
        this.categorySelect.addEventListener("change", () => {
            const selectedCategoryId = this.categorySelect.value;
            this.dispatchEvent(new CustomEvent("category-changed", {
                detail: { categoryId: selectedCategoryId },
                bubbles: true,
                composed: true
            }));
        });

        this.typeSelect = this.shadowRoot.getElementById("add-node-form-type");
        this.typeSelect.addEventListener("change", () => {
            const selectedTypeId = this.typeSelect.value;
            this.dispatchEvent(new CustomEvent("type-changed", {
                detail: { typeId: selectedTypeId },
                bubbles: true,
                composed: true
            }));
        });
    }

    show() {
        this.menu.classList.remove("hide");
    }

    hide() {
        this.menu.classList.add("hide");
    }

    populateCategories(categories) {
        const select = this.shadowRoot.getElementById("add-node-form-category");
        select.innerHTML = '<option value="" disabled selected>Selecione</option>';
        categories.forEach(cat => {
            select.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
        });
    }

    populateTypes(types) {
        const select = this.shadowRoot.getElementById("add-node-form-type");
        select.innerHTML = '<option value="" disabled selected>Selecione</option>';
        types.forEach(type => {
            select.innerHTML += `<option value="${type.id}">${type.name}</option>`;
        });
    }

    populateNodes(nodes) {
        const select = this.shadowRoot.getElementById("add-node-form-node");
        select.innerHTML = '<option value="" disabled selected>Selecione</option>';
        nodes.forEach(node => {
            select.innerHTML += `<option value="${node.id}">${node.label}</option>`;
        });
    }

    handleKeyPress(e) {
        if (e.key === "m" || e.key === "M") {
            this.show();
        }
    }

    handleMouseMove(e) {
        if (e.clientX <= 300) {
            return this.show();
        }
        if (!this.keepClosed) {
            return;
        }
        this.hide();
    }
}

customElements.define("app-menu", Menu);
