const GalleryComponent = {
    render(containerId, images = []) {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (images.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">🖼</div>
                    <div class="empty-state-title">暂无图片</div>
                    <div class="empty-state-desc">生成完成后，图片将在此处展示</div>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <div class="image-grid">
                ${images.map(img => `
                    <div class="image-grid-item">
                        <img src="${img.url}" alt="${img.label}" loading="lazy" />
                        <div class="image-label">${img.label}</div>
                    </div>
                `).join('')}
            </div>
        `;
    }
};
