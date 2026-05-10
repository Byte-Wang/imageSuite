const API_BASE = '../api';

const Api = {
    async request(method, path, data = null, files = null) {
        const url = `${API_BASE}${path}`;
        let options = { method };

        if (files) {
            const formData = new FormData();
            if (data) {
                Object.entries(data).forEach(([key, value]) => {
                    if (value !== null && value !== undefined && value !== '') {
                        formData.append(key, typeof value === 'object' ? JSON.stringify(value) : value);
                    }
                });
            }
            Object.entries(files).forEach(([key, fileOrList]) => {
                if (fileOrList instanceof FileList || Array.isArray(fileOrList)) {
                    for (const f of fileOrList) {
                        formData.append(key, f);
                    }
                } else {
                    formData.append(key, fileOrList);
                }
            });
            options.body = formData;
        } else if (data && method !== 'GET') {
            options.headers = { 'Content-Type': 'application/json' };
            options.body = JSON.stringify(data);
        }

        try {
            const resp = await fetch(url, options);
            const json = await resp.json();

            if (!resp.ok || json.error) {
                throw new Error(json.error || `HTTP ${resp.status}`);
            }

            return json;
        } catch (err) {
            console.error(`API ${method} ${path} failed:`, err);
            throw err;
        }
    },

    getProviders() {
        return this.request('GET', '/providers');
    },

    getConfig() {
        return this.request('GET', '/config');
    },

    analyze(data, files) {
        return this.request('POST', '/analyze', data, files);
    },

    generate(data, files) {
        return this.request('POST', '/generate', data, files);
    },

    getGenerateStatus(taskId) {
        return this.request('GET', `/generate/${taskId}`);
    },

    generateVideo(data, files) {
        return this.request('POST', '/video', data, files);
    }
};
