
export class OpenProjectModal extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this.render();
    }

    render() {
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

            #open-project-modal.show {
                display: block;
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
                <p><button type="submit">Abrir</button></p>
            </form>
        </div>
        `;

        const form = this.shadowRoot.getElementById('open-prj-form');
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const select = this.shadowRoot.getElementById('open-prj-form-id');
            const id = select.value;
            this.dispatchEvent(new CustomEvent('open-project', {
                detail: { id:id },
                bubbles: true,
                composed: true
            }));
        });
    }

    populateProjects(projects) {
        const select = this.shadowRoot.getElementById('open-prj-form-id');
        select.innerHTML = '';
        projects.forEach(prj => {
            const option = document.createElement('option');
            option.value = prj.id;
            option.textContent = prj.name;
            select.appendChild(option);
        });
    }

    show() {
        const modal = this.shadowRoot.getElementById('open-project-modal');
        modal.classList.add('show');
    }

    hide() {
        const modal = this.shadowRoot.getElementById('open-project-modal');
        modal.classList.remove('show');
    }
}

customElements.define("app-open-project-modal", OpenProjectModal);
