"use strict";

import { EVENTS } from '../events.js';

export class OpenProjectModal extends HTMLElement {
    
    static observedAttributes = ["projects"];

    constructor()
    {
        super();

        // AbortController for automatic event listener cleanup
        this.abortController = new AbortController();
        this.render();
    }

    connectedCallback()
    {
        this.openProjectModal = this.shadowRoot.getElementById('open-project-modal');

        this.select = this.shadowRoot.getElementById('open-prj-form-id');

        this.form = this.shadowRoot.getElementById('open-prj-form');
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            const select = this.shadowRoot.getElementById('open-prj-form-id');
            const id = select.value;
            this.dispatchEvent(new CustomEvent(EVENTS.OPEN_PROJECT, {
                detail: { id:id },
                bubbles: true,
                composed: true
            }));
            this.hide();
        }, { signal: this.abortController.signal });

        this.cancelButton = this.shadowRoot.getElementById('cancel-open-project');
        this.cancelButton.addEventListener('click', (e) => {
            e.preventDefault();
            this.hide();
        }, { signal: this.abortController.signal });
    }

    disconnectedCallback()
    {
        this.abortController.abort();
    }

    set projects(values)
    {
        this.setAttribute("projects", JSON.stringify(values));
        this.select.innerHTML = '';
        values.forEach(prj => {
            const option = document.createElement('option');
            option.value = prj.id;
            option.textContent = prj.name;
            this.select.appendChild(option);
        });
        
    }

    get projects()
    {
        const attr = this.getAttribute("projects");
        return attr ? JSON.parse(attr) : [];
    }

    render()
    {
        this.attachShadow({ mode: "open" });
        this.shadowRoot.innerHTML = `
        <style>
            #open-project-modal {
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
        <div id="open-project-modal">
            <h2>Abrir Projeto</h2>
            <form id="open-prj-form">
                <p>
                    <label for="open-prj-form-id">Projeto:<br>
                        <select id="open-prj-form-id" name="id"></select>
                    </label>
                </p>
                <p><button type="submit">Abrir</button> <button type="button" id="cancel-open-project">Cancelar</button></p>
            </form>
        </div>
        `;
    }

    show(projects) {
        this.openProjectModal.style.display = 'block';
        this.projects = projects;
    }

    hide() {
        this.openProjectModal.style.display = 'none';
    }
}

customElements.define("app-open-project-modal", OpenProjectModal);
