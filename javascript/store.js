import { EVENTS } from './events.js';

// Store class to manage application state
export class Store {
    constructor(initialState = {}) {
        this.state = initialState;
        this.subscribers = {}; // mapa de eventos para listas de callbacks de inscrição
        this.handlers = {};    // mapa de eventos para handlers;
    }

    subscribe(event, callback) {
        if (!this.subscribers[event]) {
            this.subscribers[event] = [];
        }
        this.subscribers[event].push(callback);
    }

    // Notify all subscribers of state change
    notify(event) {
        if (event !== EVENTS.MENU_OPEN_REQUESTED && event !== EVENTS.MENU_CLOSE_REQUESTED) {
            console.log('Store notifying subscribers for event:', event);
        }
        
        if (this.subscribers[event]) {
            this.subscribers[event].forEach(callback => callback(this.state));
        }
    }

    // Register an event handler
    register(event, handler){
        this.handlers[event] = handler;
    }

    // Dispatch an event to update state
    dispatch(event, payload) {
        if (event !== EVENTS.MENU_OPEN_REQUESTED && event !== EVENTS.MENU_CLOSE_REQUESTED) {
            console.log('Store dispatching event:', event, payload);
        }

        if (this.handlers[event]) {
            this.state = this.handlers[event](this.state, payload);
            this.notify(event);
        } else {
            console.error(`Evento não registrado: ${event}`);
        }
    }
}