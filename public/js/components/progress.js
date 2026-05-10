const ProgressComponent = {
    init(containerId, items = []) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = `
            <div class="generate-progress" id="${containerId}-list">
                ${items.map((item, i) => `
                    <div class="generate-progress-item" id="${containerId}-item-${i}">
                        <div class="generate-progress-icon">
                            <div class="spinner" id="${containerId}-spinner-${i}"></div>
                        </div>
                        <div class="generate-progress-name">${item.label}</div>
                        <div class="generate-progress-status" id="${containerId}-status-${i}">
                            <span class="badge badge-info">等待中</span>
                        </div>
                    </div>
                `).join('')}
            </div>
            <div class="progress-bar" style="margin-top:16px">
                <div class="progress-bar-fill" id="${containerId}-bar" style="width:0%"></div>
            </div>
        `;
    },

    updateItem(containerId, index, status, message = '') {
        const statusEl = document.getElementById(`${containerId}-status-${index}`);
        const spinnerEl = document.getElementById(`${containerId}-spinner-${index}`);
        if (!statusEl || !spinnerEl) return;

        const badgeClass = {
            'pending': 'badge-info',
            'running': 'badge-info',
            'ok': 'badge-success',
            'error': 'badge-error'
        }[status] || 'badge-info';

        const label = {
            'pending': '等待中',
            'running': '生成中...',
            'ok': '完成',
            'error': '失败'
        }[status] || status;

        statusEl.innerHTML = `<span class="badge ${badgeClass}">${message || label}</span>`;

        if (status === 'ok') {
            spinnerEl.outerHTML = '<span style="color:#34c759;font-size:16px">✓</span>';
        } else if (status === 'error') {
            spinnerEl.outerHTML = '<span style="color:#ff3b30;font-size:16px">✗</span>';
        }
    },

    updateProgress(containerId, completed, total) {
        const bar = document.getElementById(`${containerId}-bar`);
        if (bar) {
            bar.style.width = `${(completed / total) * 100}%`;
        }
    }
};
