
export class Notification extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this.render();
    }

    render() {
        this.shadowRoot.innerHTML = `
            <style>
                #notification-container {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 20px;
                    color: white;
                    border-radius: 4px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                    z-index: 10000;
                    max-width: 400px;
                    word-wrap: break-word;
                    animation: slideIn 0.3s ease-out;
                }
            </style>

            <div id="notification-container"></div>
        `;

        this.container = this.shadowRoot.getElementById('notification-container');
    }

    show(message) {
        this.container.textContent = message;
        this.container.style.display = 'block';
        setTimeout(() => {
            this.container.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => this.container.style.display = 'none', 300);
        }, 3000);
    }
    
    error(message) {
        this.container.style.background = '#f44336';
        this.show(message);
    }
    
    success(message) {
        this.container.style.background = '#4caf50';
        this.show(message);
    }
    
    info(message) {
        this.container.style.background = '#2196f3';
        this.show(message);
    }
}

customElements.define("app-notification", Notification);
