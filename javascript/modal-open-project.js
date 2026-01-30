
export class OpenProjectModal extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this.render();
    }

    async render() {
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

                /* ****** display: none; */
                z-index: 200;
            }

            #open-project-modal.show {
                display: block;
            }
        </style>
        <div id="open-project-modal">
            <h2>Abrir Projeto</h2>
            <form id="open-prj-form" method="post">
                <p>
                    <label for="open-prj-form-id">Projeto:<br>
                        <select id="open-prj-form-id" name="id"></select>
                    </label>
                </p>
                <p><button type="submit">Abrir</button></p>
            </form>
        </div>
        `;
    }
}

customElements.define("app-open-project-modal", OpenProjectModal);
