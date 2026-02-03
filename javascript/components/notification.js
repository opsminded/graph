"use strict";

/**
 * Notification Component - Displays toast-style notifications
 * 
 * Features:
 * - Auto-dismiss after configurable duration
 * - Different types: success, error, info
 * - Smooth animations
 * - Queue system for multiple notifications
 * 
 * Usage:
 *   notification.success("Operation successful!");
 *   notification.error("Something went wrong");
 *   notification.info("Just so you know...");
 */
export class Notification extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: "open" });
    
    // Configuration
    this.displayDuration = 2000; // ms
    this.animationDuration = 300; // ms
    
    // Track active timeouts for cleanup
    this.timeouts = [];
    
    this.render();
  }

  connectedCallback() {
    this.container = this.shadowRoot.getElementById("notification-container");
  }

  disconnectedCallback() {
    // Clear all pending timeouts
    this.timeouts.forEach(timeout => clearTimeout(timeout));
    this.timeouts = [];
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
          display: none;
          opacity: 0;
          transform: translateX(100%);
          transition: opacity 0.5s ease-out, transform 0.5s ease-out;
        }

        #notification-container.show {
          display: block;
          opacity: 1;
          transform: translateX(0);
        }

        #notification-container.hide {
          opacity: 0;
          transform: translateX(100%);
        }

        #notification-container.success {
          background-color: #4caf50;
        }

        #notification-container.error {
          background-color: #f44336;
        }

        #notification-container.info {
          background-color: #2196f3;
        }

        #notification-container.warning {
          background-color: #ff9800;
        }
      </style>

      <div id="notification-container"></div>
    `;
  }

  /**
   * Shows a notification with the specified message and type.
   * @param {string} message - The message to display
   * @param {string} type - The notification type (success, error, info, warning)
   */
  show(message, type = "info") {
    // Clear any existing timeouts
    this.timeouts.forEach(timeout => clearTimeout(timeout));
    this.timeouts = [];

    // Reset classes and set content
    this.container.className = "";
    this.container.textContent = message;
    this.container.classList.add(type);

    // Make visible but keep off-screen
    this.container.style.display = "block";

    // Force reflow to ensure display:block is applied before animation
    this.container.offsetHeight;

    // Trigger slide-in animation on next frame
    requestAnimationFrame(() => {
      this.container.classList.add("show");
    });

    // Schedule hide animation
    const hideTimeout = setTimeout(() => {
      this.container.classList.remove("show");
      this.container.classList.add("hide");

      // Schedule complete removal after animation
      const removeTimeout = setTimeout(() => {
        this.container.style.display = "none";
        this.container.classList.remove("hide", type);
      }, this.animationDuration);

      this.timeouts.push(removeTimeout);
    }, this.displayDuration);

    this.timeouts.push(hideTimeout);
  }

  /**
   * Displays an error notification.
   * @param {string} message - The error message
   */
  error(message) {
    this.show(message, "error");
  }

  /**
   * Displays a success notification.
   * @param {string} message - The success message
   */
  success(message) {
    this.show(message, "success");
  }

  /**
   * Displays an info notification.
   * @param {string} message - The info message
   */
  info(message) {
    this.show(message, "info");
  }

  /**
   * Displays a warning notification.
   * @param {string} message - The warning message
   */
  warning(message) {
    this.show(message, "warning");
  }
}

customElements.define("app-notification", Notification);
