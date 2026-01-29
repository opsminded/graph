import { Store } from './store.js';
import { EVENTS } from './events.js';
import { fetchProjects, fetchProject } from './api.js';

import { Modal } from './modal.js';
import { Menu } from './menu.js';

export class App
{
    constructor() {
        this.store = new Store();

        this.modal = new Modal(this.store);
        this.menu = new Menu(this.store);

        this.htmlBtnExport = document.getElementById('export-btn');
        this.htmlFitGraph = document.getElementById('fit-btn');

        const urlParams = new URLSearchParams(window.location.search);
        const projectID = urlParams.get('project');
        this.store.state.selectedProjectId = projectID;

        this.registerEvents();
        this.registerHandlers();
        this.setupSubscription();

        this.store.dispatch(EVENTS.INITIAL_LOAD, { initialLoad: true });
    }

    registerEvents() {
        window.document.addEventListener('mousemove', (e) => {
            this.menu.onMouseMove(e);
        });

        this.htmlBtnExport.addEventListener('click', (e) => {
            alert('Export not implemented yet.');
        });

        this.htmlFitGraph.addEventListener('click', (e) => {
            alert('Fit Graph not implemented yet.');
        });
    }

    registerHandlers() {
        this.store.register(EVENTS.INITIAL_LOAD, (state, payload) => ({
            ...state,
            initialLoad: payload.initialLoad
        }));

        this.store.register(EVENTS.PROJECTS_LIST_LOADED, (state, payload) => ({
            ...state,
            projects: payload.projects
        }));

        this.store.register(EVENTS.PROJECT_SELECTED, (state, payload) => ({
            ...state,
            selectedProjectId: payload.selectedProjectId
        }));

        this.store.register(EVENTS.PROJECT_OPEN_REQUESTED, (state, payload) => ({
            ...state,
            selectedProjectId: payload.selectedProjectId
        }));

        this.store.register(EVENTS.PROJECT_OPENED, (state, payload) => ({
            ...state,
            project: payload.project
        }));

        this.store.register(EVENTS.MODAL_CLOSE_REQUESTED, (state, payload) => ({
            ...state
        }));

        this.store.register(EVENTS.MODAL_CLOSED, (state, payload) => ({
            ...state
        }));

        this.store.register(EVENTS.MODAL_OPEN_PROJECT_REQUESTED, (state, payload) => ({
            ...state
        }));

        this.store.register(EVENTS.MENU_CLOSE_REQUESTED, (state, payload) => ({
            ...state
        }));

        this.store.register(EVENTS.MENU_OPEN_REQUESTED, (state, payload) => ({
            ...state
        }));
    }

    setupSubscription()
    {
        this.store.subscribe(EVENTS.INITIAL_LOAD, async (state) => {
            console.log('In App setupSubscription. Initial Load. Loading projects list...');
            if (state.initialLoad) {
                const projects = await fetchProjects();
                this.store.dispatch(EVENTS.PROJECTS_LIST_LOADED, { projects });
            }
        });

        this.store.subscribe(EVENTS.PROJECT_OPEN_REQUESTED, async (state) => {
            console.log('Project Selected. vai buscar:', state.selectedProjectId);
            const project = await fetchProject(state.selectedProjectId);
            console.log('Project data:', project);
            this.updateCY(project);
        });
    }
}