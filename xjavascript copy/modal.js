
import { EVENTS } from './events.js';
import {createOptionElement} from './utilities.js';

export class Modal
{
    constructor(store)
    {
        this.NewProjectModal = new NewProjectModal(store);
        this.openProjectModal = new OpenProjectModal(store);

        this.store = store;
        this.htmlElement = document.getElementById('modal');
        this.htmlElementCloseModal = document.getElementById('close-modal-btn');
        this.setupEventListeners();
        this.setupSubscription();
    }

    setupEventListeners()
    {
        this.htmlElementCloseModal.addEventListener('click', (event) => {
            this.store.dispatch(EVENTS.MODAL_CLOSE_REQUESTED, {});
        });
    }

    setupSubscription()
    {
        this.store.subscribe(EVENTS.MODAL_CLOSE_REQUESTED, async (state) => {
            console.log('Modal close requested');
            this.hide();
        });

        this.store.subscribe(EVENTS.MODAL_OPEN_PROJECT_REQUESTED, async (state) => {
            this.show();
        });
    }

    show() {
        this.htmlElement.classList.add('show');
    }

    hide() {
        this.htmlElement.classList.remove('show');
    }
}

class NewProjectModal
{
    constructor(store)
    {        
        this.store = store;
        this.htmlElement = document.getElementById('modal-new-prj');
        this.htmlNewProjectFormElement = document.getElementById('new-prj-form');
        this.htmlNewProjectFormNameElement = document.getElementById('new-prj-form-name');
        this.setupEventListeners();
        this.setupSubscription();
    }

    setupEventListeners()
    {
    }

    setupSubscription()
    {
    }
}

class OpenProjectModal
{
    constructor(store)
    {
        this.store = store;
        this.htmlElement = document.getElementById('modal-open-prj');
        this.htmlOpenProjectFormElement = document.getElementById('open-prj-form');
        this.htmlOpenProjectFormIdElement = document.getElementById('open-prj-form-id');
        this.setupEventListeners();
        this.setupSubscription();
    }

    setupEventListeners()
    {
        this.htmlOpenProjectFormElement.addEventListener('submit', (event) => {
            event.preventDefault();
            const selectedProjectId = this.htmlOpenProjectFormIdElement.value;
            this.store.dispatch(EVENTS.PROJECT_SELECTED, { selectedProjectId: selectedProjectId });
        });
    }

    setupSubscription()
    {
        this.store.subscribe(EVENTS.PROJECT_SELECTED, async (state) => {
            console.log('Project selected:', state.selectedProjectId);
            window.location.href = `/?project=${state.selectedProjectId}`;
        });

        this.store.subscribe(EVENTS.PROJECTS_LIST_LOADED, async (state) => {
            console.log('Loading projects into OpenProjectModal:', state.projects);
            this.loadProjects(state.projects);
            this.show();
        });

        this.store.subscribe(EVENTS.MODAL_OPEN_PROJECT_REQUESTED, async (state) => {
            this.show();
        });
    }

    loadProjects(projects) {
        console.log('Loading projects into select element:', projects);

        this.htmlOpenProjectFormIdElement.innerHTML = '';
        projects.forEach((project) => {
            this.htmlOpenProjectFormIdElement.appendChild(
                createOptionElement(project.id, project.name)
            );
        });
    }

    show() {
        console.log('Showing OpenProjectModal');
        this.htmlElement.classList.add('show');
    }

    hide() {
        console.log('Hiding OpenProjectModal');
        this.htmlElement.classList.remove('show');
    }
}