"use strict";

export class Menu extends HTMLElement {
  static observedAttributes = [
    "keep-open",
    "add-node-visible",
    "add-edge-visible",
    "export-visible",
    "fit-visible",
  ];

  constructor() {
    super();

    // Debounce configuration for mouse movement handler
    this.debounce = { delay: 50, timeout: null };

    // AbortController for automatic event listener cleanup
    this.abortController = new AbortController();

    this.render();
  }

  /**
   * Called when the element is inserted into the DOM.
   * Sets up all event listeners and initializes menu state.
   */
  connectedCallback() {

    // Cache DOM references from shadow DOM
    this.menu = this.shadowRoot.getElementById("menu");

    // Set initial visibility based on keep-open attribute
    if (this.hasAttribute("keep-open")) {
      this.menu.style.display = "block";
    } else {
      this.menu.style.display = "none";
    }

    // Initialize close/pin button
    this.closeMenuBtn = this.shadowRoot.getElementById("close-menu-btn");
    this.closeMenuBtn.addEventListener(
      "click",
      (e) => {
        e.preventDefault();
        if (this.hasAttribute("keep-open")) {
          this.removeAttribute("keep-open");
        } else {
          this.setAttribute("keep-open", "true");
        }
      },
      { signal: this.abortController.signal },
    );

    this.newPrjBtn = this.shadowRoot.getElementById("new-prj-btn");
    this.newPrjBtn.addEventListener(
      "click",
      (e) => {
        e.preventDefault();
        alert("Novo Projeto button clicked");
        this.dispatchEvent(
          new CustomEvent("new-prj-btn-clicked", {
            bubbles: true,
            composed: true,
          }),
        );
      },
      { signal: this.abortController.signal },
    );

    this.addNodeBtn = this.shadowRoot.getElementById("add-node-btn");
    this.addNodeBtn.addEventListener(
      "click",
      (e) => {
        e.preventDefault();
        alert("Adicionar Item button clicked");
        this.dispatchEvent(
          new CustomEvent("add-node-btn-clicked", {
            bubbles: true,
            composed: true,
          }),
        );
      },
      { signal: this.abortController.signal },
    );

    this.addEdgeBtn = this.shadowRoot.getElementById("add-edge-btn");
    this.addEdgeBtn.addEventListener(
      "click",
      (e) => {
        e.preventDefault();
        alert("Adicionar Conexão button clicked");
        this.dispatchEvent(
          new CustomEvent("add-edge-btn-clicked", {
            bubbles: true,
            composed: true,
          }),
        );
      },
      { signal: this.abortController.signal },
    );

    this.openPrjBtn = this.shadowRoot.getElementById("open-prj-btn");
    this.openPrjBtn.addEventListener(
      "click",
      (e) => {
        e.preventDefault();
        this.dispatchEvent(
          new CustomEvent("open-prj-btn-clicked", {
            bubbles: true,
            composed: true,
          }),
        );
      },
      { signal: this.abortController.signal },
    );

    this.exportBtn = this.shadowRoot.getElementById("export-btn");

    this.exportBtn.addEventListener(
      "click",
      (e) => {
        e.preventDefault();
        alert("Export button clicked");
        this.dispatchEvent(
          new CustomEvent("export-btn-clicked", {
            bubbles: true,
            composed: true,
          }),
        );
      },
      { signal: this.abortController.signal },
    );

    this.fitBtn = this.shadowRoot.getElementById("fit-btn");
    this.fitBtn.addEventListener(
      "click",
      (e) => {
        e.preventDefault();
        alert("Fit button clicked");
        this.dispatchEvent(
          new CustomEvent("fit-btn-clicked", {
            bubbles: true,
            composed: true,
          }),
        );
      },
      { signal: this.abortController.signal },
    );

    // Set initial visibility based on attributes
    this.updateButtonVisibility();

    this.boundMouseHandler = this.handleMouseMove.bind(this);
    document.addEventListener("mousemove", this.boundMouseHandler, {
      signal: this.abortController.signal,
    });
  }

  disconnectedCallback() {
    if (this.debounce.timeout) {
      clearTimeout(this.debounce.timeout);
    }
    this.abortController.abort();
  }

  attributeChangedCallback(name, oldValue, newValue) {

    if (name === "keep-open") {
      if (this.hasAttribute("keep-open")) {
        if (this.menu) {
          this.menu.style.display = "block";
          this.closeMenuBtn.textContent = "X";
        }
      } else {
        if (this.closeMenuBtn) {
          this.closeMenuBtn.textContent = "fixar";
        }
      }
    }

    // Handle visibility attributes - only update if elements are available
    if (name.endsWith("-visible")) {
      this.updateButtonVisibility();
    }
  }

  /**
   * Returns the CSS styles for the menu component.
   * @returns {string} Template literal with CSS styles
   */
  getStyles() {
    return `
      <style>
        #menu {
          background-color: #FAF9F5;
          border-right: 1px solid #CCC;
          height: 100%;
          left: 0;
          padding: 10px;
          position: fixed;
          text-align: center;
          top: 0;
          width: 270px;
          z-index: 500;
          display: none;
        }

        #menu-title {
          font-size: 24px;
          line-height: 24px;
          margin: 10px 0;
          padding: 0;
        }

        #menu-title span {
          color: #B62B2B;
          font-weight: bold;
        }

        #close-menu {
          text-align: right;
        }

        #project-panel {
          background-color: #fafafa;
          border-top: 1px solid #CCC;
          border-bottom: 1px solid #CCC;
          padding-bottom: 10px;
        }

        #project-panel button {
          margin: 0 10px;
        }

        #add-node-btn, #add-edge-btn,
        #export-btn,
        #fit-btn {
          width: 90%;
          margin: 5px 0;
          padding: 10px;
          font-size: 16px;
          display: none;
        }
      </style>
    `;
  }

  getTemplate() {
    const buttonText = this.hasAttribute("keep-open") ? "X" : "fixar";

    return `
      <div id="menu">
        <div id="close-menu">
          <button id="close-menu-btn">${buttonText}</button>
        </div>

        <img src="/images/logo.png" alt="Logo" width="32" height="32">
        
        <h2 id="menu-title"><span>Brades</span>ketch</h2>

        <div id="project-panel">
          <h3>Projetos</h3>
          <p>
            <button id="new-prj-btn" title="Novo Projeto">Novo</button>
            <button id="open-prj-btn" title="Abrir Projeto">Abrir</button>
          </p>
        </div>

        <div id="add-node-div">
          <p><button id="add-node-btn" type="submit">Adicionar Item</button></p>
        </div>

        <div id="add-edge-div">
          <p><button id="add-edge-btn" type="submit">Adicionar Conexão</button></p>
        </div>

        <p>
          <button id="export-btn" type="button">Exportar</button>
        </p>

        <p>
          <button id="fit-btn" type="button">Ajustar</button>
        </p>
      </div>
    `;
  }

  /**
   * Updates button visibility based on attributes.
   * Safe to call even if DOM elements don't exist yet.
   */
  updateButtonVisibility() {
    if (this.addNodeBtn) {
      this.addNodeBtn.style.display = this.hasAttribute("add-node-visible") 
        ? "block" 
        : "none";
    }
    if (this.addEdgeBtn) {
      this.addEdgeBtn.style.display = this.hasAttribute("add-edge-visible") 
        ? "block" 
        : "none";
    }
    if (this.exportBtn) {
      this.exportBtn.style.display = this.hasAttribute("export-visible") 
        ? "block" 
        : "none";
    }
    if (this.fitBtn) {
      this.fitBtn.style.display = this.hasAttribute("fit-visible") 
        ? "block" 
        : "none";
    }
  }

  // Property getters/setters for programmatic access
  set addNodeVisible(isVisible) {
    if (isVisible) {
      this.setAttribute("add-node-visible", "");
    } else {
      this.removeAttribute("add-node-visible");
    }
  }

  get addNodeVisible() {
    return this.hasAttribute("add-node-visible");
  }

  set addEdgeVisible(isVisible) {
    if (isVisible) {
      this.setAttribute("add-edge-visible", "");
    } else {
      this.removeAttribute("add-edge-visible");
    }
  }

  get addEdgeVisible() {
    return this.hasAttribute("add-edge-visible");
  }

  set exportVisible(isVisible) {
    if (isVisible) {
      this.setAttribute("export-visible", "");
    } else {
      this.removeAttribute("export-visible");
    }
  }

  get exportVisible() {
    return this.hasAttribute("export-visible");
  }

  set fitVisible(isVisible) {
    if (isVisible) {
      this.setAttribute("fit-visible", "");
    } else {
      this.removeAttribute("fit-visible");
    }
  }

  get fitVisible() {
    return this.hasAttribute("fit-visible");
  }

  render() {
    this.attachShadow({ mode: "open" });
    this.shadowRoot.innerHTML = this.getStyles() + this.getTemplate();
  }

  handleMouseMove(e) {
    if (this.debounce.timeout) {
      clearTimeout(this.debounce.timeout);
    }

    this.debounce.timeout = setTimeout(() => {
      if (e.clientX <= 300 && this.menu.style.display === "none") {
        this.menu.style.display = "block";
        return;
      } else {
        if (this.hasAttribute("keep-open")) {
          return;
        }
        if (e.clientX > 300 && this.menu.style.display === "block") {
          this.menu.style.display = "none";
        }
      }
    }, this.debounce.delay);
  }
}

customElements.define("app-menu", Menu);
