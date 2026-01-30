
export class Api
{
    constructor(baseUrl = '')
    {
        this.getProjectURL = baseUrl + '/getProject';
        this.getProjectsURL = baseUrl + '/getProjects';
    }

    async fetchCategories() {
        console.log('Fetching categories from API...');
        try {
            const response = await fetch('/getCategories');
            if (!response.ok) {
                console.error('Response not ok:', response);
                throw new Error(`Erro ao carregar categorias: ${response.status}`);
            }
            const { data } = await response.json();
            console.log('Categories fetched:', data);
            return data;
        } catch (error) {
            console.error('[fetchCategories] Fetch error:', error);
            return [];
        }
    }

    async fetchTypes() {
        console.log('Fetching types from API...');
        try {
            const response = await fetch('/getTypes');
            if (!response.ok) {
                console.error('Response not ok:', response);
                throw new Error(`Erro ao carregar tipos: ${response.status}`);
            }
            const { data } = await response.json();
            console.log('Types fetched:', data);
            return data;
        }
        catch (error) {
            console.error('[fetchTypes] Fetch error:', error);
            return [];
        }
    }

    async fetchProjects() {
        console.log('Fetching projects from API...');
        try {
            const response = await fetch(this.getProjectsURL);
            if (!response.ok) {
                console.error('Response not ok:', response);
                throw new Error(`Erro ao carregar projetos: ${response.status}`);
            }
            const { data } = await response.json();
            console.log('Projects fetched:', data);
            return data;
        } catch (error) {
            console.error('[fetchProjects] Fetch error:', error);
            return [];
        }
    }

    async fetchProject(projectId) {
        console.log('Fetching project from API with ID:', projectId);
        try {
            const response = await fetch(`${this.getProjectURL}?id=${encodeURIComponent(projectId)}`);
            if (!response.ok) {
                console.error('Response not ok:', response);
                throw new Error(`Erro ao carregar projeto: ${response.status}`);
            }
            const { data } = await response.json();
            console.log('Project fetched:', data);
            return data;
        } catch (error) {
            console.error('[fetchProject] Fetch error:', error);
            return null;
        }
    }

    async fetchProjectGraph(projectId) {
        console.log('Fetching project graph from API with ID:', projectId);
        try {
            const response = await fetch(`/getProjectGraph?id=${encodeURIComponent(projectId)}`);
            if (!response.ok) {
                console.error('Response not ok:', response);
                throw new Error(`Erro ao carregar gráficos do projeto: ${response.status}`);
            }
            const { data } = await response.json();
            console.log('Project graph fetched:', data);
            return data;
        } catch (error) {
            console.error('[fetchProjectGraph] Fetch error:', error);
            return [];
        }
    }
}