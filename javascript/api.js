
export class Api
{
    constructor(baseUrl = '')
    {
        this.getTypesURL = baseUrl + '/getTypes';
        this.getProjectURL = baseUrl + '/getProject';
        this.getProjectsURL = baseUrl + '/getProjects';
        this.getCategoriesURL = baseUrl + '/getCategories';
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
        return fetch('/insertProject', {
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
}
