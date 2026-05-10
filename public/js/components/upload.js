const UploadComponent = {
    init(containerId, options = {}) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const name = options.name || 'images';
        const multiple = options.multiple !== false;
        const accept = options.accept || 'image/*';
        const maxFiles = options.maxFiles || 5;
        const label = options.label || '拖拽图片到此处，或点击选择文件';
        const hint = options.hint || '支持 JPG、PNG、WebP 格式';

        container.innerHTML = `
            <div class="upload-zone" id="${containerId}-zone">
                <div class="upload-zone-icon">⬆</div>
                <div class="upload-zone-text">${label}</div>
                <div class="upload-zone-hint">${hint}</div>
                <input type="file" name="${name}" id="${containerId}-input"
                    accept="${accept}" ${multiple ? 'multiple' : ''}
                    style="display:none" />
            </div>
            <div class="image-preview-list" id="${containerId}-preview"></div>
        `;

        const zone = document.getElementById(`${containerId}-zone`);
        const input = document.getElementById(`${containerId}-input`);
        const preview = document.getElementById(`${containerId}-preview`);
        const files = [];

        zone.addEventListener('click', () => input.click());

        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('dragover');
        });

        zone.addEventListener('dragleave', () => {
            zone.classList.remove('dragover');
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('dragover');
            const newFiles = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
            this.addFiles(containerId, newFiles, maxFiles);
        });

        input.addEventListener('change', () => {
            const newFiles = Array.from(input.files);
            this.addFiles(containerId, newFiles, maxFiles);
            input.value = '';
        });

        container._files = files;
        container._getFiles = () => container._files;
    },

    addFiles(containerId, newFiles, maxFiles) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const files = container._files;
        const remaining = maxFiles - files.length;
        const toAdd = newFiles.slice(0, remaining);

        toAdd.forEach(file => {
            files.push(file);
            this.renderPreview(containerId, file, files.length - 1);
        });

        if (newFiles.length > remaining) {
            App.toast(`最多上传 ${maxFiles} 张图片`, 'warning');
        }
    },

    renderPreview(containerId, file, index) {
        const preview = document.getElementById(`${containerId}-preview`);
        if (!preview) return;

        const item = document.createElement('div');
        item.className = 'image-preview-item';
        item.dataset.index = index;

        const reader = new FileReader();
        reader.onload = (e) => {
            item.innerHTML = `
                <img src="${e.target.result}" alt="预览" />
                <button class="image-preview-remove" data-container="${containerId}" data-index="${index}">&times;</button>
            `;
            item.querySelector('.image-preview-remove').addEventListener('click', (ev) => {
                ev.stopPropagation();
                this.removeFile(containerId, parseInt(ev.target.dataset.index));
            });
        };
        reader.readAsDataURL(file);

        preview.appendChild(item);
    },

    removeFile(containerId, index) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container._files.splice(index, 1);
        const preview = document.getElementById(`${containerId}-preview`);
        preview.innerHTML = '';

        container._files.forEach((file, i) => {
            this.renderPreview(containerId, file, i);
        });
    },

    getFiles(containerId) {
        const container = document.getElementById(containerId);
        return container?._files || [];
    }
};
