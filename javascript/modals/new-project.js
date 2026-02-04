"use strict";

export class NewProjectModal extends HTMLElement {

    constructor()
    {
        super();
        
        // AbortController for automatic event listener cleanup
        this.abortController = new AbortController();
        this.render();
    }

    connectedCallback()
    {
        this.modal = this.shadowRoot.getElementById('new-project-modal');
        this.form = this.shadowRoot.getElementById('new-prj-form');
        
        this.newProjectFormNameInput = this.shadowRoot.getElementById('new-prj-form-name');

        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            const projectData = { name: this.newProjectFormNameInput.value };
            this.dispatchEvent(new CustomEvent('new-project', {
                detail: projectData,
                bubbles: true,
                composed: true
            }));
            this.hide();
        }, { signal: this.abortController.signal });

        this.cancelButton = this.shadowRoot.getElementById('cancel-new-project');
        this.cancelButton.addEventListener('click', () => {
            this.hide();
        }, { signal: this.abortController.signal });
    }

    disconnectedCallback()
    {
        this.abortController.abort();
    }

    render()
    {
        this.attachShadow({ mode: "open" });

        this.shadowRoot.innerHTML = `
        <style>
            #new-project-modal {
                position: absolute;

                border: 2px solid #CCC;
                background-color: #fff;

                left: 25%;
                top: 8%;
                width: 50%;
                height: 70%;

                padding: 10px;

                display: none;
                z-index: 200;
            }
        </style>
        <div id="new-project-modal">
            <h2>Novo Projeto</h2>
            <form id="new-prj-form" method="post">
                <p><label for="new-prj-form-name">Nome:<br>
                    <input type="text" id="new-prj-form-name" name="name" required></label>
                </p>
                <p><button type="submit">Criar</button> <button type="button" id="cancel-new-project">Cancelar</button></p>
            </form>
        </div>
        `;

        
    }

    show() {
        this.modal.style.display = 'block';
    }

    hide() {
        this.modal.style.display = 'none';
    }
}

customElements.define("app-new-project-modal", NewProjectModal);
