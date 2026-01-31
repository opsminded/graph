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
            })
            .catch(error => {
                console.error('[fetchCategories] Fetch error:', error);
                return [];
            }
        );
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
            })
            .catch(error => {
                console.error('[fetchTypes] Fetch error:', error);
                return [];
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
            })
            .catch(error => {
                console.error('[getCategoryTypes] Fetch error:', error);
                return [];
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
            })
            .catch(error => {
                console.error('[fetchTypeNodes] Fetch error:', error);
                return [];
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
            })
            .catch(error => {
                console.error('[fetchProjects] Fetch error:', error);
                return [];
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
            })
            .catch(error => {
                console.error('[fetchProject] Fetch error:', error);
                return null;
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
            })
            .catch(error => {
                console.error('[fetchProjectGraph] Fetch error:', error);
                return [];
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
            })
            .catch(error => {
                console.error('[fetchProjectStatus] Fetch error:', error);
                return null;
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
        })
        .catch(error => {
            console.error('[insertProject] Fetch error:', error);
            return null;
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
        })
        .catch(error => {
            console.error('[insertProjectNode] Fetch error:', error);
            return null;
        });
    }
}
