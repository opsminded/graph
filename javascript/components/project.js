"use strict";

import { Api } from "../api.js";
import cytoscape from "/javascript/libs/cytoscape.esm.min.mjs";
import { InfoPanel } from "./info-panel.js";
import { EVENTS } from "../events.js";

const STATUS_UPDATE_INTERVAL = 5000;

export class Project extends HTMLElement {
  constructor() {
    super();

    this.api = new Api();

    this.cy = null;

    this.selectedNodes = [];
    this.selectedEdge = null;

    this.statusUpdateTimer = null;

    // AbortController for automatic event listener cleanup
    this.abortController = new AbortController();
    this.render();
  }

  connectedCallback() {
    this.projectTitle = this.shadowRoot.getElementById("project-title");
    this.projectAuthor = this.shadowRoot.getElementById("project-author");
    this.projectHeader = this.shadowRoot.getElementById("project-header");
    this.importNodeButton = this.shadowRoot.getElementById("import-node-btn");
    this.addNodeButton = this.shadowRoot.getElementById("add-node-btn");
    this.addEdgeButton = this.shadowRoot.getElementById("add-edge-btn");
    this.infoPanel = this.shadowRoot.querySelector("app-info-panel");

    this.importNodeModal = this.shadowRoot.getElementById("import-node-modal");
    this.importNodeForm = this.shadowRoot.getElementById("import-node-form");
    this.importNodeCategory = this.shadowRoot.getElementById(
      "import-node-category",
    );
    this.importNodeType = this.shadowRoot.getElementById("import-node-type");
    this.importNodeNode = this.shadowRoot.getElementById("import-node-node");
    this.importNodeFormCancelButton =
      this.shadowRoot.getElementById("cancel-import-node");

    this.addNodeModal = this.shadowRoot.getElementById("add-node-modal");
    this.addNodeForm = this.shadowRoot.getElementById("add-node-form");
    this.addNodeCategory = this.shadowRoot.getElementById("add-node-category");
    this.addNodeType = this.shadowRoot.getElementById("add-node-type");

    this.addNodeFormCancelButton =
      this.shadowRoot.getElementById("cancel-add-node");

    this.removeNodeModal = this.shadowRoot.getElementById("remove-node-modal");
    this.removeEdgeModal = this.shadowRoot.getElementById("remove-edge-modal");

    this.removeNodeForm = this.shadowRoot.getElementById("remove-node-form");
    this.removeEdgeForm = this.shadowRoot.getElementById("remove-edge-form");

    this.removeNodeButton = this.shadowRoot.getElementById("remove-node-btn");
    this.removeEdgeButton = this.shadowRoot.getElementById("remove-edge-btn");

    this.removeEdgeSource =
      this.shadowRoot.getElementById("remove-edge-source");
    this.removeEdgeTarget =
      this.shadowRoot.getElementById("remove-edge-target");

    this.removeNodeCancelButton =
      this.shadowRoot.getElementById("cancel-remove-node");
    this.removeEdgeCancelButton =
      this.shadowRoot.getElementById("cancel-remove-edge");

    this.exportButton = this.shadowRoot.getElementById("export-btn");
    this.exportButton.addEventListener(
      "click",
      () => this.export(),
      this.abortController.signal,
    );
    this.exportButton.style.display = "inline-block";

    this.adjustButton = this.shadowRoot.getElementById("adjust-btn");
    this.adjustButton.addEventListener(
      "click",
      () => this.fit(),
      this.abortController.signal,
    );
    this.adjustButton.style.display = "inline-block";

    this.cyContainer = this.shadowRoot.getElementById("cy");

    this.importNodeButton.addEventListener(
      "click",
      () => {
        this.importNodeModal.style.display = "block";

        this.api
          .fetchCategories()
          .then((categories) => {
            this.importNodeCategory.innerHTML =
              '<option value="" disabled selected>Selecione uma categoria</option>';
            categories.forEach((category) => {
              const option = document.createElement("option");
              option.value = category.id;
              option.textContent = category.name;
              this.importNodeCategory.appendChild(option);
            });
            this.importNodeType.innerHTML =
              '<option value="" disabled selected>Selecione um tipo</option>';
            this.importNodeNode.innerHTML =
              '<option value="" disabled selected>Selecione um item</option>';
          })
          .catch((error) => {
            console.error("Error fetching categories:", error);
          });
      },
      this.abortController.signal,
    );

    this.importNodeCategory.addEventListener(
      "change",
      () => {
        const categoryId = this.importNodeCategory.value;
        this.api
          .fetchCategoryTypes(categoryId)
          .then((types) => {
            this.importNodeType.innerHTML =
              '<option value="" disabled selected>Selecione um tipo</option>';
            types.forEach((type) => {
              const option = document.createElement("option");
              option.value = type.id;
              option.textContent = type.name;
              this.importNodeType.appendChild(option);
            });
            this.importNodeNode.innerHTML =
              '<option value="" disabled selected>Selecione um item</option>';
          })
          .catch((error) => {
            console.error("Error fetching types:", error);
          });
      },
      this.abortController.signal,
    );

    this.importNodeType.addEventListener(
      "change",
      () => {
        const typeId = this.importNodeType.value;
        this.api
          .fetchTypeNodes(typeId)
          .then((nodes) => {
            this.importNodeNode.innerHTML =
              '<option value="" disabled selected>Selecione um item</option>';
            nodes.forEach((node) => {
              const option = document.createElement("option");
              option.value = node.id;
              option.textContent = node.label;
              this.importNodeNode.appendChild(option);
            });
          })
          .catch((error) => {
            console.error("Error fetching nodes:", error);
          });
      },
      this.abortController.signal,
    );

    this.importNodeFormCancelButton.addEventListener(
      "click",
      () => {
        this.importNodeForm.reset();
        this.importNodeModal.style.display = "none";
      },
      this.abortController.signal,
    );

    this.addNodeButton.addEventListener(
      "click",
      () => {
        this.api
          .fetchCategories()
          .then((categories) => {
            this.addNodeCategory.innerHTML =
              '<option value="" disabled selected>Selecione uma categoria</option>';
            categories.forEach((category) => {
              console.log("Adding category option:", category);
              const option = document.createElement("option");
              option.value = category.id;
              option.textContent = category.name;
              this.addNodeCategory.appendChild(option);
            });
            this.addNodeType.innerHTML =
              '<option value="" disabled selected>Selecione um tipo</option>';
          })
          .catch((error) => {
            console.error("Error fetching categories:", error);
          });

        this.addNodeModal.style.display = "block";
      },
      this.abortController.signal,
    );

    this.addNodeCategory.addEventListener(
      "change",
      () => {
        const categoryId = this.addNodeCategory.value;
        this.api
          .fetchCategoryTypes(categoryId)
          .then((types) => {
            this.addNodeType.innerHTML =
              '<option value="" disabled selected>Selecione um tipo</option>';
            types.forEach((type) => {
              const option = document.createElement("option");
              option.value = type.id;
              option.textContent = type.name;
              this.addNodeType.appendChild(option);
            });
          })
          .catch((error) => {
            console.error("Error fetching types:", error);
          });
      },
      this.abortController.signal,
    );

    this.addEdgeButton.addEventListener(
      "click",
      () => {
        const edge = {
          project: this.project.id,
          label: "connects_to",
          source: this.selectedNodes[0],
          target: this.selectedNodes[1],
          data: {},
        };

        this.api
          .insertEdge(edge)
          .then((newEdge) => {
            this.dispatchEvent(
              new CustomEvent(EVENTS.RELOAD_PROJECT, {
                bubbles: true,
                composed: true,
              }),
            );
          })
          .catch((error) => {
            this.showNotification(
              `Erro ao criar ligação: ${error.message}`,
              "error",
            );
          });

        this.selectedNodes = [];
        this.addEdgeButton.style.display = "none";
        this.cy.elements().unselect();
      },
      this.abortController.signal,
    );

    this.importNodeForm.addEventListener(
      "submit",
      (e) => {
        e.preventDefault();

        const formData = new FormData(this.importNodeForm);

        const nodeData = {
          project_id: this.project.id,
          node_id: formData.get("import-node-node"),
        };

        this.api
          .insertProjectNode(nodeData)
          .then((node) => {
            this.dispatchEvent(
              new CustomEvent(EVENTS.RELOAD_PROJECT, {
                bubbles: true,
                composed: true,
              }),
            );
          })
          .catch((error) => {
            this.showNotification(
              `Erro ao importar item: ${error.message}`,
              "error",
            );
          });

        this.importNodeModal.style.display = "none";
        this.importNodeForm.reset();
      },
      this.abortController.signal,
    );

    this.addNodeForm.addEventListener(
      "submit",
      (e) => {
        e.preventDefault();

        const formData = new FormData(this.addNodeForm);

        const nodeData = {
          id: formData.get("add-node-id"),
          label: formData.get("add-node-label"),
          category: formData.get("add-node-category"),
          type: formData.get("add-node-type"),
          data: {},
        };

        console.log("Creating node with data:", nodeData);

        this.api
          .insertNode(nodeData)
          .then((newNode) => {
            this.dispatchEvent(
              new CustomEvent(EVENTS.RELOAD_PROJECT, {
                bubbles: true,
                composed: true,
              }),
            );
          })
          .catch((error) => {
            this.showNotification(
              `Erro ao criar item: ${error.message}`,
              "error",
            );
          });

        this.addNodeModal.style.display = "none";
        this.addNodeForm.reset();
      },
      this.abortController.signal,
    );

    this.addNodeFormCancelButton.addEventListener(
      "click",
      () => {
        this.addNodeForm.reset();
        this.addNodeModal.style.display = "none";
      },
      this.abortController.signal,
    );

    this.removeNodeButton.addEventListener(
      "click",
      () => {
        this.removeNodeModal.style.display = "block";
        if (this.selectedNodes.length === 1) {
          this.removeNodeForm.querySelector("#remove-node-id").value =
            this.selectedNodes[0];
        }
      },
      this.abortController.signal,
    );

    this.removeNodeCancelButton.addEventListener(
      "click",
      () => {
        this.removeNodeForm.reset();
        this.removeNodeModal.style.display = "none";
      },
      this.abortController.signal,
    );

    this.removeEdgeButton.addEventListener(
      "click",
      () => {
        this.removeEdgeModal.style.display = "block";
        if (this.selectedEdge) {
          console.log("Selected edge for removal:", this.selectedEdge);
          this.removeEdgeSource.value = this.selectedEdge.source;
          this.removeEdgeTarget.value = this.selectedEdge.target;
        }
      },
      this.abortController.signal,
    );

    this.removeEdgeCancelButton.addEventListener(
      "click",
      () => {
        this.removeEdgeForm.reset();
        this.removeEdgeModal.style.display = "none";
      },
      this.abortController.signal,
    );

    this.removeNodeForm.addEventListener(
      "submit",
      (e) => {
        e.preventDefault();
        const formData = new FormData(this.removeNodeForm);
        const nodeId = formData.get("remove-node-id");

        const nodeData = {
          project_id: this.project.id,
          node_id: nodeId,
        };

        this.api
          .deleteProjectNode(nodeData)
          .then(() => {
            this.dispatchEvent(
              new CustomEvent(EVENTS.RELOAD_PROJECT, {
                bubbles: true,
                composed: true,
              }),
            );
          })
          .catch((error) => {
            this.showNotification(
              `Erro ao remover item: ${error.message}`,
              "error",
            );
          });

        this.removeNodeModal.style.display = "none";
        this.removeNodeForm.reset();
      },
      this.abortController.signal,
    );

    this.removeEdgeForm.addEventListener(
      "submit",
      (e) => {
        e.preventDefault();
        const formData = new FormData(this.removeEdgeForm);

        console.log("Form data entries:", formData);

        const edgeData = {
          source: formData.get("remove-edge-source"),
          target: formData.get("remove-edge-target"),
        };

        console.log("Removing edge:", edgeData);

        this.api
          .deleteEdge(edgeData)
          .then(() => {
            this.dispatchEvent(
              new CustomEvent(EVENTS.RELOAD_PROJECT, {
                bubbles: true,
                composed: true,
              }),
            );
          })
          .catch((error) => {
            this.showNotification(
              `Erro ao remover ligação: ${error.message}`,
              "error",
            );
          });

        this.removeEdgeModal.style.display = "none";
        this.removeEdgeForm.reset();
      },
      this.abortController.signal,
    );

    document.addEventListener(
      "keydown",
      (e) => {
        if (e.key === "Escape" && this.cy) {
          this.cy.elements().unselect();
          this.selectedNodes = [];
          this.selectedEdge = null;
          this.infoPanel.node = null;
          this.addNodeModal.style.display = "none";
          this.importNodeModal.style.display = "none";
          this.removeNodeModal.style.display = "none";
          this.removeEdgeModal.style.display = "none";
        }
      },
      this.abortController.signal,
    );
  }

  disconnectedCallback() {
    this.abortController.abort();
  }

  showNotification(message, type = "info") {
    this.dispatchEvent(
      new CustomEvent("show-notification", {
        detail: { message, type },
        bubbles: true,
        composed: true,
      }),
    );
  }

  openProject(project, graph, status) {
    console.log("Opening project:", project, "graph", graph, "status", status);
    this.project = project;
    if (this.statusUpdateTimer) {
      clearInterval(this.statusUpdateTimer);
    }
    this.statusUpdateTimer = null;

    this.statusUpdateTimer = setInterval(() => {
      this.api
        .fetchProjectStatus(project.id)
        .then((statuses) => {
          this.updateStatus(statuses);
        })
        .catch((error) => {
          console.error("Erro ao atualizar status dos nós:", error);
        });
    }, STATUS_UPDATE_INTERVAL);

    this.projectTitle.textContent = project.name;
    this.projectAuthor.textContent = project.author;
    this.importNodeButton.style.display = "inline-block";
    this.addNodeButton.style.display = "inline-block";
    this.projectHeader.style.display = "block";

    if (this.cy) {
      this.cy.destroy();
    }

    // Initialize Cytoscape
    graph.container = this.cyContainer;
    this.layout = graph.layout;
    this.cy = cytoscape(graph);

    // Setup Cytoscape event listeners after initialization
    this.setupCytoscapeEvents();

    this.updateStatus(status);
  }

  closeProject() {
    this.project = null;
    this.selectedNodes = [];
    this.selectedEdge = null;
    if (this.cy) {
      this.cy.destroy();
      this.cy = null;
    }
    this.infoPanel.node = null;
    this.projectTitle.textContent = "";
    this.projectAuthor.textContent = "";
    this.importNodeButton.style.display = "none";
    this.addNodeButton.style.display = "none";
    this.addEdgeButton.style.display = "none";
    this.exportButton.style.display = "none";
    this.adjustButton.style.display = "none";
    this.projectHeader.style.display = "none";
  }
  updateStatus(statusUpdates) {
    if (!statusUpdates || !Array.isArray(statusUpdates)) {
      return;
    }
    this.status = statusUpdates;

    statusUpdates.forEach((update) => {
      const node = this.cy.$("#" + update.node_id);
      if (node.length > 0) {
        // Remove classes de status anteriores
        let classes = node.classes();
        classes.forEach((cls) => {
          if (cls.startsWith("node-status")) {
            node.removeClass(cls);
          }
        });

        // Adiciona nova classe de status
        node.addClass(`node-status-${update.status}`);
      }
    });
  }

  render() {
    this.attachShadow({ mode: "open" });
    this.shadowRoot.innerHTML = `
            <style>
                #cy {
                    position: absolute;

                    left: 0px;
                    top: 0;
                    bottom: 0;
                    right: 0;

                    width: 100%;
                    height: 100%;
                    
                    z-index: 100;
                }

                #buttons-container {
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    width: 500px;
                    z-index: 101;
                    text-align: right;
                }

                #buttons-container button {
                    margin: 0 10px;
                    display: none;
                }

                #project-header {
                    position: absolute;
                    right: 10px;
                    margin-right: 20px;
                    top: 20px;
                    z-index: 101;
                    display: none;
                }

                #import-node-modal,
                #add-node-modal,
                #remove-node-modal,
                #remove-edge-modal {
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
            </style>
            <div id="project-container">
                <div id="project-header">
                  <h2 id="project-title"></h2>
                  <p>Autor: <span id="project-author"></span></p>
                </div>

                <div id="buttons-container">
                    <button id="import-node-btn">Importar Item</button>
                    <button id="add-node-btn">Novo Item</button>
                    <button id="remove-node-btn">Remover Item</button>
                    <button id="add-edge-btn">Nova Ligação</button>
                    <button id="remove-edge-btn">Remover Ligação</button>
                    <button id="export-btn">Exportar</button>
                    <button id="adjust-btn">Ajustar</button>
                </div>

                <div id="cy"></div>
                <app-info-panel></app-info-panel>

                <div id="import-node-modal">
                    <form id="import-node-form">
                        <p>
                          <label for="import-node-category">Categoria do Item:<br>
                            <select id="import-node-category" name="import-node-category"></select>
                          </label>
                        </p>
                        
                        <p>
                          <label for="import-node-type">Tipo do Item:<br>
                            <select id="import-node-type" name="import-node-type"></select>
                          </label>
                        </p>

                        <p>
                          <label for="import-node-node">Item:<br>
                            <select id="import-node-node" name="import-node-node"></select>
                          </label>
                        </p>

                        <p>
                          <button type="submit">Importar Item</button>
                          <button type="button" id="cancel-import-node">Cancelar</button>
                        </p>
                    </form>
                </div>

                <div id="add-node-modal">
                    <form id="add-node-form">
                      <p>
                        <label for="add-node-id">Identificador único:<br>
                          <input type="text" id="add-node-id" name="add-node-id" required>
                        </label>
                      </p>

                      <p>
                        <label for="add-node-label">Rótulo do Item:<br>
                          <input type="text" id="add-node-label" name="add-node-label" required>
                        </label>
                      </p>

                      <p>
                        <label for="add-node-category">Categoria do Item:<br>
                          <select id="add-node-category" name="add-node-category"></select>
                        </label>
                      </p>

                      <p>
                        <label for="add-node-type">Tipo do Item:<br>
                          <select id="add-node-type" name="add-node-type"></select>
                        </label>
                      </p>

                      <p>
                        <button type="submit">Adicionar Item</button>
                        <button type="button" id="cancel-add-node">Cancelar</button>
                      </p>
                    </form>
                </div>

                <div id="remove-node-modal">
                    <form id="remove-node-form">
                        <h3>Remover Item</h3>
                        <p>Este item será removido apenas do projeto</p>
                        <p>Se este item for dependência de outros itens, as ligações serão mantidas</p>

                        <p>
                          <label for="remove-node-id">Identificador único:<br>
                            <input type="text" id="remove-node-id" name="remove-node-id" required readonly style="background-color: #f0f0f0;">
                          </label>
                        </p>
                        
                        <p>
                          <button type="submit">Remover Item</button>
                          <button type="button" id="cancel-remove-node">Cancelar</button>
                        </p>
                    </form>
                </div>

                <div id="remove-edge-modal">
                    <form id="remove-edge-form">
                        <h3>Remover Ligação</h3>
                        <p>Uma ligação vale para todos os projetos.</p>
                        <p>Remover uma ligação pode afetar outros projetos que a utilizam.</p>

                        <p>
                          <label for="remove-edge-source">Origem:<br>
                            <input type="text" id="remove-edge-source" name="remove-edge-source" required readonly style="background-color: #f0f0f0;">
                          </label>
                        </p>

                        <p>
                          <label for="remove-edge-target">Destino:<br>
                            <input type="text" id="remove-edge-target" name="remove-edge-target" required readonly style="background-color: #f0f0f0;">
                          </label>
                        </p>

                        <p>
                          <button type="submit">Remover Ligação</button>
                          <button type="button" id="cancel-remove-edge">Cancelar</button>
                        </p>
                    </form>
                </div>
            </div>
        `;
  }

  /**
   * Configura todos os event listeners do Cytoscape.
   * Deve ser chamado após a inicialização do cy.
   */
  setupCytoscapeEvents() {
    if (!this.cy) {
      console.warn("Cytoscape not initialized");
      return;
    }

    // Evento: Seleção de nó
    this.cy.on("select", "node", (e) => {
      const n = e.target;
      this.selectedNodes.push(n.id());

      if (this.selectedNodes.length === 1) {
        this.removeNodeButton.style.display = "inline-block";
      } else {
        this.removeNodeButton.style.display = "none";
      }

      if (this.selectedNodes.length === 2) {
        // Dois nós selecionados, pronto para adicionar aresta
        this.addEdgeButton.style.display = "inline-block";
      }

      if (this.selectedNodes.length > 2) {
        this.addEdgeButton.style.display = "none";
        this.selectedNodes = [];
        this.cy.elements().unselect();
      }
    });

    // Evento: Seleção de aresta
    this.cy.on("select", "edge", (e) => {
      const edge = e.target;
      this.selectedEdge = {
        id: edge.id(),
        source: edge.data("source"),
        target: edge.data("target"),
      };
      this.removeEdgeButton.style.display = "inline-block";
    });

    // Evento: Deseleção de nó
    this.cy.on("unselect", "node", () => {
      this.selectedNodes = [];
      this.removeNodeButton.style.display = "none";
    });

    // Evento: Deseleção de aresta
    this.cy.on("unselect", "edge", () => {
      this.selectedEdge = null;
      this.addEdgeButton.style.display = "none";
      this.removeEdgeButton.style.display = "none";
    });

    // Evento: Duplo clique em nó (para mostrar info)
    this.cy.on("dbltap", "node", (e) => {
      const node = e.target;
      this.infoPanel.node = node.data();
      this.removeNodeButton.style.display = "none";
    });

    // Evento: Clique no background (deseleciona tudo)
    this.cy.on("tap", (e) => {
      if (e.target === this.cy) {
        this.cy.elements().unselect();
        this.infoPanel.node = null;
        this.addEdgeButton.style.display = "none";
        this.removeNodeButton.style.display = "none";
        this.removeEdgeButton.style.display = "none";
      }
    });
  }

  export() {
    if (!this.cy) return;

    let pngData = this.cy.png({
      full: true,
    });

    let link = document.createElement("a");
    link.href = pngData;
    link.download = "graph.png";
    link.click();
  }

  fit() {
    if (this.cy) {
      this.cy.layout(this.layout).run();
    }
  }
}

customElements.define("app-project", Project);
