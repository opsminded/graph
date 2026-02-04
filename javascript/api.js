"use strict";

export class Api
{
    constructor(baseUrl = '')
    {
        this.getTypesURL = baseUrl + '/getTypes';
        this.getProjectURL = baseUrl + '/getProject';
        this.getProjectsURL = baseUrl + '/getProjects';

        this.insertProjectNodeURL = baseUrl + '/insertProjectNode';
        this.insertProjectURL = baseUrl + '/insertProject';

        this.getCategoriesURL = baseUrl + '/getCategories';
        this.getCategoryTypesURL = baseUrl + '/getCategoryTypes';
        
        this.insertNodeURL = baseUrl + '/insertNode';
        this.insertEdgeURL = baseUrl + '/insertEdge';
        this.deleteEdgeURL = baseUrl + '/deleteEdge';
        this.deleteProjectNodeURL = baseUrl + '/deleteProjectNode';
    }

    fetchCategories() {
        return fetch(this.getCategoriesURL)
            .then(response => {
                if (!response.ok) {
                    console.error('Response not ok:', response);
                    throw new Error(`Erro ao carregar categorias: ${response.status}`);
                }
                return response.json();
            })
            .then(({ data }) => {
                return data;
            });
    }

    fetchTypes() {
        return fetch(this.getTypesURL)
            .then(response => {
                if (!response.ok) {
                    console.error('Response not ok:', response);
                    throw new Error(`Erro ao carregar tipos: ${response.status}`);
                }
                return response.json();
            })
            .then(({ data }) => {
                return data;
            });
    }

    fetchCategoryTypes(categoryId) {
        return fetch(`${this.getCategoryTypesURL}?category=${encodeURIComponent(categoryId)}`)
            .then(response => {
                if (!response.ok) {
                    console.error('Response not ok:', response);
                    throw new Error(`Erro ao carregar tipos da categoria: ${response.status}`);
                }
                return response.json();
            })
            .then(({ data }) => {
                return data;
            });
    }

    fetchTypeNodes(typeId) {
        return fetch(`/getTypeNodes?type=${encodeURIComponent(typeId)}`)
            .then(response => {
                if (!response.ok) {
                    console.error('Response not ok:', response);
                    throw new Error(`Erro ao carregar nós do tipo: ${response.status}`);
                }
                return response.json();
            })
            .then(({ data }) => {
                return data;
            });
    }

    fetchProjects() {
        return fetch(this.getProjectsURL)
            .then(response => {
                if (!response.ok) {
                    console.error('Response not ok:', response);
                    throw new Error(`Erro ao carregar projetos: ${response.status}`);
                }
                return response.json();
            })
            .then(({ data }) => {
                return data;
            });
    }

    fetchProject(projectId) {
        return fetch(`${this.getProjectURL}?id=${encodeURIComponent(projectId)}`)
            .then(response => {
                if (!response.ok) {
                    console.error('Response not ok:', response);
                    throw new Error(`Erro ao carregar projeto: ${response.status}`);
                }
                return response.json();
            })
            .then(({ data }) => {
                return data;
            });
    }

    fetchProjectGraph(projectId) {
        return fetch(`/getProjectGraph?id=${encodeURIComponent(projectId)}`)
            .then(response => {
                if (!response.ok) {
                    console.error('Response not ok:', response);
                    throw new Error(`Erro ao carregar gráficos do projeto: ${response.status}`);
                }
                return response.json();
            })
            .then(({ data }) => {
                return data;
            });
    }

    fetchProjectStatus(projectId) {
        return fetch(`/getProjectStatus?id=${encodeURIComponent(projectId)}`)
            .then(response => {
                if (!response.ok) {
                    console.error('Response not ok:', response);
                    throw new Error(`Erro ao carregar status do projeto: ${response.status}`);
                }
                return response.json();
            })
            .then(({ data }) => {
                return data;
            });
    }

    insertProject(projectData) {
        return fetch(this.insertProjectURL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(projectData)
        })
        .then(response => {
            if (!response.ok) {
                console.error('Response not ok:', response);
                throw new Error(`Erro ao inserir projeto: ${response.status}`);
            }
            return response.json();
        })
        .then(({ data }) => {
            return data;
        });
    }

    insertProjectNode(nodeData) {
        return fetch(this.insertProjectNodeURL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(nodeData)
        })
        .then(response => {
            if (!response.ok) {
                console.error('Response not ok:', response);
                throw new Error(`Erro ao inserir nó do projeto: ${response.status}`);
            }
            return response.json();
        })
        .then(({ data }) => {
            return data;
        });
    }

    insertNode(nodeData)
    {
        return fetch(this.insertNodeURL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(nodeData)
        })
        .then(response => {
            if (!response.ok) {
                console.error('Response not ok:', response);
                throw new Error(`Erro ao inserir nó: ${response.status}`);
            }
            return response.json();
        })
        .then(({ data }) => {
            return data;
        });
    }

    insertEdge(edgeData) {
        return fetch(this.insertEdgeURL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(edgeData)
        })
        .then(response => {
            if (!response.ok) {
                console.error('Response not ok:', response);
                throw new Error(`Erro ao inserir aresta: ${response.status}`);
            }
            return response.json();
        })
        .then(({ data }) => {
            return data;
        });
    }

    deleteProjectNode(node) {
        return fetch(this.deleteProjectNodeURL, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(node)
        })
        .then(response => {
            if (!response.ok) {
                console.error('Response not ok:', response);
                throw new Error(`Erro ao deletar nó do projeto: ${response.status}`);
            }
        });
    }

    deleteEdge(edge)
    {
        return fetch(this.deleteEdgeURL, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(edge)
        })
        .then(response => {
            if (!response.ok) {
                console.error('Response not ok:', response);
                throw new Error(`Erro ao deletar aresta: ${response.status}`);
            }
        });
    }
}
