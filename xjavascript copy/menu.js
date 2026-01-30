
import { EVENTS } from './events.js';

export class Menu {
    constructor(store) {
        this.store = store;
        this.MENU_WIDTH_THRESHOLD = 300;
        this.keepClosed = false;
        this.mouseMoveDebounceTimer = null;
        this.htmlElement = document.getElementById('menu');
        this.htmlCloseBtnElement = document.getElementById('close-menu-btn');
        this.htmlBtnLogin = document.getElementById('login-btn');
        this.htmlBtnNewProject = document.getElementById('new-prj-btn');
        this.htmlBtnOpenProject = document.getElementById('open-prj-btn');

        this.menuAddEdge = new MenuAddEdge(this.store);
        this.menuAddNode = new MenuAddNode(this.store);

        this.setupEventListeners();
        this.setupSubscriptions();
    }

    setupEventListeners()
    {
        this.htmlCloseBtnElement.addEventListener('click', () => {
            this.keepClosed = !this.keepClosed;
            if (this.keepClosed) {
                this.htmlCloseBtnElement.textContent = 'fixar';
            } else {
                this.htmlCloseBtnElement.textContent = 'X';
            }
            this.store.dispatch(EVENTS.MENU_CLOSE_REQUESTED, {});
        });

        this.htmlBtnLogin.addEventListener('click', () => {
            alert('Funcionalidade de login ainda não implementada.');
        });

        this.htmlBtnNewProject.addEventListener('click', () => {
            alert('Funcionalidade de novo projeto ainda não implementada.');
        });

        this.htmlBtnOpenProject.addEventListener('click', () => {
            this.store.dispatch(EVENTS.MODAL_OPEN_PROJECT_REQUESTED, {});
        });
    }

    setupSubscriptions() {
        this.store.subscribe(EVENTS.MENU_CLOSE_REQUESTED, async (state) => {
            this.hide();
        });

        this.store.subscribe(EVENTS.MENU_OPEN_REQUESTED, async (state) => {
            this.show();
        });
    }

    show() {
        this.htmlElement.classList.remove('hide');
    }

    hide() {
        this.htmlElement.classList.add('hide');
    }

    onMouseMove(e) {
        if (this.mouseMoveDebounceTimer) return;
        
        this.mouseMoveDebounceTimer = setTimeout(() => {
            this.mouseMoveDebounceTimer = null;
        }, 30);
        
        if (e.clientX > this.MENU_WIDTH_THRESHOLD && this.keepClosed) {
            this.store.dispatch(EVENTS.MENU_CLOSE_REQUESTED, {});
        } else if (e.clientX <= this.MENU_WIDTH_THRESHOLD) {
            this.store.dispatch(EVENTS.MENU_OPEN_REQUESTED, {});
        }
    }
}

class MenuAddNode
{
    constructor(store)
    {
        this.store = store;

        this.htmlFormElement = document.getElementById('add-node-form');
        this.htmlSelectCategory = document.getElementById('add-node-form-category');
        this.htmlSelectType = document.getElementById('add-node-form-type');
        this.htmlSelectNode = document.getElementById('add-node-form-node');

        this.setupEventListeners();
    }

    setupEventListeners()
    {
        this.htmlFormElement.addEventListener('submit', (e) => {
            e.preventDefault();
            const category = this.htmlSelectCategory.value;
            const type = this.htmlSelectType.value;
            const nodeId = this.htmlSelectNode.value;
            alert(`Adicionar nó: categoria=${category}, tipo=${type}, id=${nodeId}`);
        });

        this.htmlSelectCategory.addEventListener('change', (e) => {
            const category = this.htmlSelectCategory.value;
            alert(`Categoria selecionada: ${category}`);
        });

        this.htmlSelectType.addEventListener('change', (e) => {
            const category = this.htmlSelectCategory.value;
            const type = this.htmlSelectType.value;
            alert(`Tipo selecionado: categoria=${category}, tipo=${type}`);
        });
    }
}

class MenuAddEdge
{
    constructor(store)
    {
        this.store = store;

        this.htmlFormElement = document.getElementById('add-edge-form');

        this.setupEventListeners();
    }

    setupEventListeners()
    {
        this.htmlFormElement.addEventListener('submit', (e) => {
            e.preventDefault();
            alert('Adicionar aresta - funcionalidade ainda não implementada.');
        });
    }
}