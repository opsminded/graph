
export const API = {
    GET_PROJECTS:   basePath + '/getProjects',
    GET_PROJECT:    basePath + '/getProject',
    UPDATE_PROJECT: basePath + '/updateProject',
    INSERT_PROJECT: basePath + '/insertProject',
    GET_STATUS:     basePath + '/getStatus',
    GET_CATEGORIES: basePath + '/getCategories',
    GET_TYPES:      basePath + '/getTypes',
    INSERT_EDGE:    basePath + '/insertEdge',
    DELETE_EDGE:    basePath + '/deleteEdge'
};

export async function fetchProjects() {
    console.log('Fetching projects from API...');
    try {
        const response = await fetch(API.GET_PROJECTS);
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

export async function fetchProject(projectId) {
    console.log('Fetching project from API with ID:', projectId);
    try {
        const response = await fetch(`${API.GET_PROJECT}?id=${encodeURIComponent(projectId)}`);
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