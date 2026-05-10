const GeneratePage = {
    _productData: null,
    _typeNames: {
        'white_bg': '白底主图',
        'key_features': '核心卖点图',
        'selling_pt': '卖点图',
        'material': '材质图',
        'lifestyle': '场景展示图',
        'model': '模特展示图',
        'multi_scene': '多场景拼图',
        'ecommerce_detail': '电商详情图',
        'three_angle_view': '三角度拼图'
    },

    setProductData(data) {
        this._productData = data;
        this.render();
    },

    async render() {
        const app = document.getElementById('app');
        app.innerHTML = `
            <div class="page-header section-dark hero-dark">
                <h2>套图生成</h2>
                <p class="page-header-desc" style="color:rgba(255,255,255,0.7)">输入商品信息，一键生成8种电商套图</p>
            </div>
            <div class="page-content">
                <div class="form-section">
                    <div class="form-section-title">商品信息</div>
                    <div class="input-group">
                        <label class="input-label">商品 JSON</label>
                        <textarea class="input-field" id="generateProductJson" rows="8"
                            placeholder='粘贴商品分析 JSON，或从"商品分析"页面跳转'>${this._productData ? JSON.stringify(this._productData, null, 2) : ''}</textarea>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">商品参考图</div>
                    <div id="generateUpload"></div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">生成设置</div>
                    <div class="form-row-3">
                        <div class="input-group">
                            <label class="input-label">图像供应商 *</label>
                            <select class="input-field" id="generateProvider">
                                <option value="tongyi">千问 (推荐国内)</option>
                                <option value="doubao">豆包 Seedream</option>
                                <option value="openai">OpenAI</option>
                                <option value="gemini">Google Gemini</option>
                                <option value="stability">Stability AI</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label class="input-label">模型（可选）</label>
                            <input type="text" class="input-field" id="generateModel" placeholder="留空使用默认模型" />
                        </div>
                        <div class="input-group">
                            <label class="input-label">语言</label>
                            <select class="input-field" id="generateLang">
                                <option value="zh">中文</option>
                                <option value="en">English</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">图片类型</div>
                    <div class="chip-group" id="generateTypes">
                        ${Object.entries(this._typeNames).map(([id, name]) => `
                            <button class="chip ${id !== 'three_angle_view' ? 'active' : ''}" data-type="${id}">${name}</button>
                        `).join('')}
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">风格模板</div>
                    <div class="tab-group" id="generateTemplate">
                        <button class="tab-item active" data-value="1">默认商拍</button>
                        <button class="tab-item" data-value="2">生活杂志</button>
                        <button class="tab-item" data-value="3">极简高冷</button>
                        <button class="tab-item" data-value="4">活力爆款</button>
                        <button class="tab-item" data-value="5">暗调质感</button>
                    </div>
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label class="input-label">模特展示风格</label>
                        <select class="input-field" id="generateModelStyle">
                            <option value="standard">标准商拍</option>
                            <option value="bodycon">贴身合体</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label class="input-label">核心卖点图样式</label>
                        <select class="input-field" id="generateKfStyle">
                            <option value="">跟随模板</option>
                            <option value="magnifier">放大镜气泡</option>
                            <option value="icon_list">信息图标列表</option>
                            <option value="annotation">标注线指示</option>
                            <option value="split">分割板块</option>
                        </select>
                    </div>
                </div>

                <div style="text-align:center;margin:32px 0">
                    <button class="btn btn-primary btn-lg btn-pill" id="generateBtn">
                        开始生成套图
                    </button>
                </div>

                <div id="generateProgress" style="display:none"></div>
                <div id="generateGallery" style="margin-top:24px"></div>
            </div>
        `;

        UploadComponent.init('generateUpload', {
            name: 'product_images',
            multiple: true,
            maxFiles: 5,
            label: '上传商品参考图（正面图、背面图等）',
            hint: '参考图用于保持商品外观一致性'
        });

        document.querySelectorAll('#generateTypes .chip').forEach(chip => {
            chip.addEventListener('click', () => chip.classList.toggle('active'));
        });

        document.querySelectorAll('#generateTemplate .tab-item').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('#generateTemplate .tab-item').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
            });
        });

        document.getElementById('generateBtn').addEventListener('click', () => this.runGenerate());
    },

    async runGenerate() {
        const productJson = document.getElementById('generateProductJson').value.trim();
        if (!productJson) {
            App.toast('请输入商品信息 JSON', 'error');
            return;
        }

        let product;
        try {
            product = JSON.parse(productJson);
        } catch {
            App.toast('JSON 格式错误，请检查', 'error');
            return;
        }

        const provider = document.getElementById('generateProvider').value;
        const selectedTypes = Array.from(document.querySelectorAll('#generateTypes .chip.active'))
            .map(chip => chip.dataset.type);

        if (selectedTypes.length === 0) {
            App.toast('请至少选择一种图片类型', 'error');
            return;
        }

        const templateSet = document.querySelector('#generateTemplate .tab-item.active')?.dataset.value || '1';

        const btn = document.getElementById('generateBtn');
        btn.disabled = true;
        btn.innerHTML = '<div class="spinner spinner-white" style="width:16px;height:16px"></div> 生成中...';

        const progressDiv = document.getElementById('generateProgress');
        progressDiv.style.display = 'block';

        ProgressComponent.init('generateProgress', selectedTypes.map(type => ({
            label: this._typeNames[type] || type
        })));

        try {
            const data = {
                product: productJson,
                provider,
                model: document.getElementById('generateModel').value,
                lang: document.getElementById('generateLang').value,
                types: selectedTypes.join(','),
                template_set: templateSet,
                model_style: document.getElementById('generateModelStyle').value,
                key_features_style: document.getElementById('generateKfStyle').value,
            };

            const files = UploadComponent.getFiles('generateUpload');
            const fileData = files.length > 0 ? { product_images: files } : null;

            const result = await Api.generate(data, fileData);

            if (result.success && result.data) {
                const taskData = result.data;
                selectedTypes.forEach((type, i) => {
                    const status = taskData.results[type]?.status;
                    ProgressComponent.updateItem('generateProgress', i, status || 'ok');
                });
                ProgressComponent.updateProgress('generateProgress', taskData.summary.success, taskData.summary.total);

                this.renderGallery(taskData);
                App.toast(`生成完成：成功 ${taskData.summary.success} 张，失败 ${taskData.summary.failed} 张`, 'success');
            } else {
                App.toast(result.error || '生成失败', 'error');
            }
        } catch (err) {
            App.toast(err.message || '生成请求失败', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = '开始生成套图';
        }
    },

    renderGallery(taskData) {
        const gallery = document.getElementById('generateGallery');
        const images = [];

        Object.entries(taskData.results || {}).forEach(([typeId, result]) => {
            if (result.status === 'ok' && result.path) {
                images.push({
                    url: result.path,
                    label: result.name || this._typeNames[typeId] || typeId
                });
            }
        });

        GalleryComponent.render('generateGallery', images);
    }
};
