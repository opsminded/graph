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
        return this._fetchJSON(this.getCategoriesURL);
    }

    fetchTypes() {
        return this._fetchJSON(this.getTypesURL);
    }

    fetchCategoryTypes(categoryId) {
        return this._fetchJSON(`${this.getCategoryTypesURL}?category=${encodeURIComponent(categoryId)}`);
    }

    fetchTypeNodes(typeId) {
        return this._fetchJSON(`/getTypeNodes?type=${encodeURIComponent(typeId)}`);
    }

    fetchProjects() {
        return this._fetchJSON(this.getProjectsURL);
    }

    fetchProject(projectId) {
        if (!projectId) {
            throw new Error('projectId is required');
        }
        return this._fetchJSON(`${this.getProjectURL}?id=${encodeURIComponent(projectId)}`);
    }

    fetchProjectGraph(projectId) {
        if (!projectId) {
            throw new Error('projectId is required');
        }
        return this._fetchJSON(`/getProjectGraph?id=${encodeURIComponent(projectId)}`);
    }
    
    fetchProjectStatus(projectId) {
        if (!projectId) {
            throw new Error('projectId is required');
        }
        return this._fetchJSON(`/getProjectStatus?id=${encodeURIComponent(projectId)}`);
    }

    insertProject(projectData) {
        return this._fetchJSON(this.insertProjectURL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(projectData)
        });
    }

    insertProjectNode(nodeData) {
        return this._fetchJSON(this.insertProjectNodeURL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(nodeData)
        });
    }

    insertNode(nodeData)
    {
        return this._fetchJSON(this.insertNodeURL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(nodeData)
        });
    }

    insertEdge(edgeData) {
        return this._fetchJSON(this.insertEdgeURL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(edgeData)
        });
    }

    deleteProjectNode(node) {
        return this._fetch(this.deleteProjectNodeURL, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(node)
        });
    }

    deleteEdge(edge)
    {
        return this._fetch(this.deleteEdgeURL, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(edge)
        });
    }

    async _fetchJSON(url, options = {}) {
        const response = await fetch(url, options);
        if (!response.ok) {
            console.error('Response not ok:', response);
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        const { data } = await response.json();
        return data;
    }

    async _fetch(url, options = {}) {
        const response = await fetch(url, options);
        if (!response.ok) {
            console.error('Response not ok:', response);
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        console.log("Fetch response:", response);
        return response;
    }
}
