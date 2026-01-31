"use strict";

export class NewProjectModal extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this.render();
    }

    render() {
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

            #new-project-modal.show {
                display: block;
            }
        </style>
        <div id="new-project-modal">
            <h2>Novo Projeto</h2>
            <form id="new-prj-form" method="post">
                <p><label for="new-prj-form-name">Nome:<br>
                    <input type="text" id="new-prj-form-name" name="name"></label>
                </p>
                <p><button type="submit">Criar</button></p>
            </form>
        </div>
        `;

        const form = this.shadowRoot.getElementById('new-prj-form');
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const nameInput = this.shadowRoot.getElementById('new-prj-form-name');
            const projectData = { name: nameInput.value };
            this.dispatchEvent(new CustomEvent('new-project', {
                detail: projectData,
                bubbles: true,
                composed: true
            }));
        });
    }

    show() {
        const modal = this.shadowRoot.getElementById('new-project-modal');
        modal.classList.add('show');
    }

    hide() {
        const modal = this.shadowRoot.getElementById('new-project-modal');
        modal.classList.remove('show');
    }
}

customElements.define("app-new-project-modal", NewProjectModal);
