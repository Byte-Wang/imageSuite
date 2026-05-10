const WorkflowPage = {
    _step: 1,
    _productData: null,
    _analyzeFiles: [],
    _generateFiles: [],
    
    _log(msg, data = null) {
        if (data !== null) {
            Debug.data(msg, data);
        } else {
            Debug.log(msg);
        }
    },
    
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

    async render() {
        this._log(`[RENDER] Step ${this._step}`);
        const app = document.getElementById('app');
        app.innerHTML = `
            <div class="page-header section-dark hero-dark">
                <h2 style="color:white;">商品套图生成</h2>
                <p class="page-header-desc" style="color:rgba(255,255,255,0.7)">上传图片 → AI 分析 → 编辑信息 → 一键生成</p>
                <div style="margin-top:16px">
                    <div class="progress-bar" style="max-width:400px;margin:0 auto;height:3px">
                        <div class="progress-bar-fill" id="workflowProgress" style="width:${(this._step / 3) * 100}%"></div>
                    </div>
                    <div style="display:flex;justify-content:center;gap:24px;margin-top:8px;font-size:12px;color:rgba(255,255,255,0.6)">
                        <span ${this._step >= 1 ? 'style="color:white"' : ''}>1. 上传分析</span>
                        <span ${this._step >= 2 ? 'style="color:white"' : ''}>2. 编辑信息</span>
                        <span ${this._step >= 3 ? 'style="color:white"' : ''}>3. 生成套图</span>
                    </div>
                </div>
            </div>
            <div class="page-content">
                ${this.renderStep1()}
                <div id="step2" style="display:none">${this.renderStep2()}</div>
                <div id="step3" style="display:none">${this.renderStep3()}</div>
                <div id="generateProgress" style="display:none"></div>
                <div id="generateGallery" style="margin-top:24px"></div>
            </div>
        `;

        if (this._step === 1) {
            this._log('[RENDER] Initializing Step 1');
            this.initStep1();
        } else if (this._step === 2) {
            this._log('[RENDER] Initializing Step 2');
            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'block';
            this.initStep2();
        } else if (this._step === 3) {
            this._log('[RENDER] Initializing Step 3');
            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'none';
            document.getElementById('step3').style.display = 'block';
            this.initStep3();
        }
    },

    renderStep1() {
        return `
            <div id="step1">
                <div class="form-section">
                    <div class="form-section-title">上传商品图片</div>
                    <div id="workflowUpload"></div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">分析设置</div>
                    <div class="form-row">
                        <div class="input-group">
                            <label class="input-label">视觉模型供应商</label>
                            <select class="input-field" id="analyzeProvider">
                                <option value="">自动选择</option>
                                <option value="tongyi">阿里云通义千问</option>
                                <option value="doubao">字节豆包视觉</option>
                                <option value="openai">OpenAI GPT-4o</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label class="input-label">输出语言</label>
                            <select class="input-field" id="analyzeLang">
                                <option value="zh">中文</option>
                                <option value="en">English</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="text-align:center;margin:32px 0">
                    <button class="btn btn-primary btn-lg btn-pill" id="analyzeBtn">
                        开始分析
                    </button>
                </div>
            </div>
        `;
    },

    renderStep2() {
        const d = this._productData || {};
        const sp = d.selling_points || [];
        
        return `
            <div id="step2">
                <div class="form-section">
                    <div class="form-section-title">商品基本信息</div>
                    <div class="form-row">
                        <div class="input-group">
                            <label class="input-label">商品名称</label>
                            <input type="text" class="input-field" id="productName" 
                                value="${d.product_name || ''}" placeholder="如：夏季宽松字母印花T恤" />
                        </div>
                        <div class="input-group">
                            <label class="input-label">中文名称（简短版）</label>
                            <input type="text" class="input-field" id="productNameZh" 
                                value="${d.product_name_zh || ''}" placeholder="用于文案叠加" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="input-group">
                            <label class="input-label">商品类型</label>
                            <select class="input-field" id="productType">
                                <option value="服装" ${d.product_type === '服装' ? 'selected' : ''}>服装</option>
                                <option value="3C数码" ${d.product_type === '3C数码' ? 'selected' : ''}>3C数码</option>
                                <option value="家居" ${d.product_type === '家居' ? 'selected' : ''}>家居</option>
                                <option value="美妆" ${d.product_type === '美妆' ? 'selected' : ''}>美妆</option>
                                <option value="食品" ${d.product_type === '食品' ? 'selected' : ''}>食品</option>
                                <option value="其他" ${d.product_type === '其他' ? 'selected' : ''}>其他</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label class="input-label">服装位置</label>
                            <select class="input-field" id="garmentPosition">
                                <option value="top" ${d.garment_position === 'top' ? 'selected' : ''}>上装</option>
                                <option value="bottom" ${d.garment_position === 'bottom' ? 'selected' : ''}>下装</option>
                                <option value="full-body" ${d.garment_position === 'full-body' ? 'selected' : ''}>全身装</option>
                                <option value="non-apparel" ${d.garment_position === 'non-apparel' ? 'selected' : ''}>非服装</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="input-group">
                            <label class="input-label">颜色</label>
                            <input type="text" class="input-field" id="productColor" 
                                value="${d.color || ''}" placeholder="如：pure white" />
                        </div>
                        <div class="input-group">
                            <label class="input-label">材质</label>
                            <input type="text" class="input-field" id="productMaterial" 
                                value="${d.material || ''}" placeholder="如：cotton" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="input-group">
                            <label class="input-label">版型</label>
                            <input type="text" class="input-field" id="productStyle" 
                                value="${d.style || ''}" placeholder="如：宽松 oversized" />
                        </div>
                        <div class="input-group">
                            <label class="input-label">风格描述</label>
                            <input type="text" class="input-field" id="productStyleDesc" 
                                value="${d.product_style || ''}" placeholder="如：法式浪漫" />
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-label">商品描述（用于生成）</label>
                        <textarea class="input-field" id="productDesc" rows="3"
                            placeholder="英文描述，50词以内">${d.product_description_for_prompt || ''}</textarea>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">目标人群与场景</div>
                    <div class="form-row">
                        <div class="input-group">
                            <label class="input-label">目标人群</label>
                            <input type="text" class="input-field" id="targetAudience" 
                                value="${d.target_audience || ''}" placeholder="如：年轻女性" />
                        </div>
                        <div class="input-group">
                            <label class="input-label">模特种族</label>
                            <select class="input-field" id="modelEthnicity">
                                <option value="asian" ${d.model_ethnicity === 'asian' ? 'selected' : ''}>亚洲</option>
                                <option value="western" ${d.model_ethnicity === 'western' ? 'selected' : ''}>欧美</option>
                                <option value="mixed" ${d.model_ethnicity === 'mixed' ? 'selected' : ''}>混血</option>
                            </select>
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-label">使用场景（每行一个）</label>
                        <textarea class="input-field" id="targetScenes" rows="3"
                            placeholder="居家&#10;出行&#10;约会">${(d.target_scenes || []).join('\n')}</textarea>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">核心卖点（可添加/编辑/删除）</div>
                    <div id="sellingPointsList">
                        ${sp.map((item, i) => this.renderSellingPoint(item, i)).join('')}
                    </div>
                    <button class="btn btn-outline btn-sm" id="addSellingPoint" style="margin-top:12px">
                        + 添加卖点
                    </button>
                </div>

                <div class="form-section">
                    <div class="form-section-title">视觉特征</div>
                    <div class="chip-group" id="visualFeatures">
                        ${(d.visual_features || []).map((feature, i) => `
                            <span class="chip active" data-index="${i}">${feature} <button style="margin-left:4px;border:none;background:none;color:inherit;cursor:pointer">&times;</button></span>
                        `).join('')}
                    </div>
                    <input type="text" class="input-field" id="newVisualFeature" placeholder="添加视觉特征" style="margin-top:12px" />
                    <button class="btn btn-outline btn-sm" id="addVisualFeature" style="margin-top:8px">添加</button>
                </div>

                <div style="text-align:center;margin:32px 0">
                    <button class="btn btn-outline" id="backToStep1">← 返回</button>
                    <button class="btn btn-primary btn-lg btn-pill" id="nextToStep3" style="margin-left:12px">
                        下一步：生成设置 →
                    </button>
                </div>
            </div>
        `;
    },

    renderSellingPoint(item, index) {
        const iconOptions = ['fabric', 'fit', 'design', 'comfort', 'quality', 'function', 'scene'];
        return `
            <div class="card card-flat" style="padding:16px;margin-bottom:12px" data-index="${index}">
                <div class="form-row-3" style="gap:12px">
                    <div>
                        <label class="input-label" style="font-size:12px">图标类型</label>
                        <select class="input-field" style="font-size:14px" data-field="icon">
                            ${iconOptions.map(icon => `<option value="${icon}" ${item.icon === icon ? 'selected' : ''}>${this.iconLabel(icon)}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="input-label" style="font-size:12px">中文标题</label>
                        <input type="text" class="input-field" style="font-size:14px" data-field="zh" 
                            value="${item.zh || ''}" placeholder="卖点标题" />
                    </div>
                    <div>
                        <label class="input-label" style="font-size:12px">英文标题</label>
                        <input type="text" class="input-field" style="font-size:14px" data-field="en" 
                            value="${item.en || ''}" placeholder="English title" />
                    </div>
                </div>
                <div class="form-row" style="gap:12px;margin-top:8px">
                    <div style="flex:1">
                        <label class="input-label" style="font-size:12px">中文描述</label>
                        <input type="text" class="input-field" style="font-size:14px" data-field="zh_desc" 
                            value="${item.zh_desc || ''}" placeholder="≤15字" />
                    </div>
                    <div style="flex:1">
                        <label class="input-label" style="font-size:12px">英文描述</label>
                        <input type="text" class="input-field" style="font-size:14px" data-field="en_desc" 
                            value="${item.en_desc || ''}" placeholder="≤12 words" />
                    </div>
                </div>
                <div style="margin-top:8px">
                    <label class="input-label" style="font-size:12px">视觉关键词（英文，逗号分隔）</label>
                    <input type="text" class="input-field" style="font-size:14px" data-field="visual_keywords" 
                        value="${(item.visual_keywords || []).join(', ') || ''}" placeholder="fabric, stitching" />
                </div>
                <button class="btn btn-outline btn-sm" style="margin-top:8px;color:#ff3b30;border-color:#ff3b30" 
                    id="removeSp-${index}">删除此卖点</button>
            </div>
        `;
    },

    iconLabel(icon) {
        const labels = {
            'fabric': '材质',
            'fit': '版型',
            'design': '设计',
            'comfort': '舒适',
            'quality': '品质',
            'function': '功能',
            'scene': '场景'
        };
        return labels[icon] || icon;
    },

    renderStep3() {
        return `
            <div id="step3">
                <div class="form-section">
                    <div class="form-section-title">商品参考图（用于保持商品一致性）</div>
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
                    <button class="btn btn-outline" id="backToStep2">← 返回编辑</button>
                    <button class="btn btn-primary btn-lg btn-pill" id="generateBtn" style="margin-left:12px">
                        开始生成套图
                    </button>
                </div>
            </div>
        `;
    },

    initStep1() {
        UploadComponent.init('workflowUpload', {
            name: 'images',
            multiple: true,
            maxFiles: 5,
            label: '拖拽商品图片到此处，或点击选择',
            hint: '支持正面图、背面图等多角度，最多5张'
        });

        // 如果已有分析过的文件，自动填充
        if (this._analyzeFiles && this._analyzeFiles.length > 0) {
            this._log('[STEP 1] Restoring files:', this._analyzeFiles);
            UploadComponent.addFiles('workflowUpload', this._analyzeFiles, 5);
        }

        document.getElementById('analyzeBtn').addEventListener('click', () => this.runAnalyze());
    },

    async runAnalyze() {
        this._log('[STEP 1] Starting analysis...');
        const files = UploadComponent.getFiles('workflowUpload');
        if (files.length === 0) {
            this._log('[STEP 1] No files uploaded');
            App.toast('请先上传商品图片', 'error');
            return;
        }

        this._log('[STEP 1] Files:', files);
        this._analyzeFiles = files;

        const btn = document.getElementById('analyzeBtn');
        btn.disabled = true;
        btn.innerHTML = '<div class="spinner spinner-white" style="width:16px;height:16px"></div> 分析中...';

        try {
            const data = {
                provider: document.getElementById('analyzeProvider').value,
                lang: document.getElementById('analyzeLang').value,
            };

            this._log('[STEP 1] Calling API.analyze...');
            const result = await Api.analyze(data, { images: files });
            this._log('[STEP 1] API response:', result);

            if (result.success && result.data) {
                this._productData = result.data;
                this._step = 2;
                this._log('[STEP 1] Analysis successful, moving to Step 2');
                this.render();
                App.toast('分析完成，请编辑商品信息', 'success');
            } else {
                this._log('[STEP 1] Analysis failed:', result.error);
                App.toast(result.error || '分析失败', 'error');
            }
        } catch (err) {
            this._log('[STEP 1] Analysis error:', err);
            App.toast(err.message || '分析请求失败', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = '开始分析';
        }
    },

    initStep2() {
        this._log('[STEP 2] Initializing...');
        document.getElementById('addSellingPoint').addEventListener('click', () => this.addSellingPoint());
        
        document.querySelectorAll('[id^="removeSp-"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const index = parseInt(e.target.id.split('-')[1]);
                this.removeSellingPoint(index);
            });
        });

        document.getElementById('addVisualFeature').addEventListener('click', () => this.addVisualFeature());
        
        document.getElementById('visualFeatures').addEventListener('click', (e) => {
            if (e.target.tagName === 'BUTTON') {
                e.target.parentElement.remove();
            }
        });

        document.getElementById('backToStep1').addEventListener('click', () => {
            this._log('[STEP 2] Going back to Step 1');
            this._step = 1;
            this.render();
        });

        document.getElementById('nextToStep3').addEventListener('click', () => this.goToStep3());
        this._log('[STEP 2] Event listeners attached');
    },

    addSellingPoint() {
        const list = document.getElementById('sellingPointsList');
        const index = list.children.length;
        list.innerHTML += this.renderSellingPoint({}, index);
        
        document.getElementById(`removeSp-${index}`).addEventListener('click', () => {
            this.removeSellingPoint(index);
        });
    },

    removeSellingPoint(index) {
        const cards = document.querySelectorAll('#sellingPointsList .card');
        cards.forEach((card, i) => {
            if (parseInt(card.dataset.index) === index) {
                card.remove();
            } else if (parseInt(card.dataset.index) > index) {
                card.dataset.index = i - 1;
            }
        });
    },

    addVisualFeature() {
        const input = document.getElementById('newVisualFeature');
        const value = input.value.trim();
        if (!value) return;
        
        const container = document.getElementById('visualFeatures');
        const index = container.children.length;
        container.innerHTML += `<span class="chip active" data-index="${index}">${value} <button style="margin-left:4px;border:none;background:none;color:inherit;cursor:pointer">&times;</button></span>`;
        input.value = '';
    },

    goToStep3() {
        this._log('[STEP 2 -> 3] Going to Step 3');
        this._productData = this.collectProductData();
        this._log('[STEP 2 -> 3] Collected product data:', this._productData);
        this._step = 3;
        this.render();
    },

    collectProductData() {
        this._log('[COLLECT] Collecting product data from form...');
        const data = {
            product_name: document.getElementById('productName').value,
            product_name_zh: document.getElementById('productNameZh').value,
            product_type: document.getElementById('productType').value,
            garment_position: document.getElementById('garmentPosition').value,
            color: document.getElementById('productColor').value,
            material: document.getElementById('productMaterial').value,
            style: document.getElementById('productStyle').value,
            product_style: document.getElementById('productStyleDesc').value,
            product_description_for_prompt: document.getElementById('productDesc').value,
            target_audience: document.getElementById('targetAudience').value,
            model_ethnicity: document.getElementById('modelEthnicity').value,
            target_scenes: document.getElementById('targetScenes').value.split('\n').map(s => s.trim()).filter(Boolean),
            visual_features: Array.from(document.querySelectorAll('#visualFeatures .chip'))
                .map(chip => chip.textContent.replace('×', '').trim()),
            selling_points: this.collectSellingPoints()
        };
        this._log('[COLLECT] Collected data:', data);
        return data;
    },

    collectSellingPoints() {
        const points = [];
        document.querySelectorAll('#sellingPointsList .card').forEach(card => {
            const obj = {};
            card.querySelectorAll('[data-field]').forEach(input => {
                const field = input.dataset.field;
                if (input.tagName === 'INPUT') {
                    if (field === 'visual_keywords') {
                        obj[field] = input.value.split(',').map(s => s.trim()).filter(Boolean);
                    } else {
                        obj[field] = input.value;
                    }
                } else if (input.tagName === 'SELECT') {
                    obj[field] = input.value;
                }
            });
            if (Object.keys(obj).length > 0) {
                points.push(obj);
            }
        });
        return points;
    },

    initStep3() {
        this._log('[STEP 3] Initializing...');
        UploadComponent.init('generateUpload', {
            name: 'product_images',
            multiple: true,
            maxFiles: 5,
            label: '上传商品参考图（正面图、背面图等）',
            hint: '参考图用于保持商品外观一致性'
        });

        // 自动复用第一步上传的商品图片
        if (this._analyzeFiles && this._analyzeFiles.length > 0) {
            this._log('[STEP 3] Reusing Step 1 files:', this._analyzeFiles);
            UploadComponent.addFiles('generateUpload', this._analyzeFiles, 5);
        }

        document.querySelectorAll('#generateTypes .chip').forEach(chip => {
            chip.addEventListener('click', () => chip.classList.toggle('active'));
        });

        document.querySelectorAll('#generateTemplate .tab-item').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('#generateTemplate .tab-item').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
            });
        });

        document.getElementById('backToStep2').addEventListener('click', () => {
            this._log('[STEP 3] Going back to Step 2');
            this._step = 2;
            this.render();
        });

        document.getElementById('generateBtn').addEventListener('click', () => this.runGenerate());
        this._log('[STEP 3] Event listeners attached');
    },

    async runGenerate() {
        this._log('[STEP 3] Starting generation...');
        const provider = document.getElementById('generateProvider').value;
        const selectedTypes = Array.from(document.querySelectorAll('#generateTypes .chip.active'))
            .map(chip => chip.dataset.type);

        if (selectedTypes.length === 0) {
            this._log('[STEP 3] No types selected');
            App.toast('请至少选择一种图片类型', 'error');
            return;
        }

        this._log('[STEP 3] Selected types:', selectedTypes);
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
            const productJson = JSON.stringify(this._productData);
            this._log('[STEP 3] Product JSON:', this._productData);
            
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

            this._log('[STEP 3] Calling API.generate...');
            const result = await Api.generate(data, fileData);
            this._log('[STEP 3] API response:', result);

            if (result.success && result.data) {
                const taskData = result.data;
                selectedTypes.forEach((type, i) => {
                    const status = taskData.results[type]?.status;
                    ProgressComponent.updateItem('generateProgress', i, status || 'ok');
                });
                ProgressComponent.updateProgress('generateProgress', taskData.summary.success, taskData.summary.total);

                this.renderGallery(taskData);
                this._log(`[STEP 3] Generation complete: ${taskData.summary.success} success, ${taskData.summary.failed} failed`);
                App.toast(`生成完成：成功 ${taskData.summary.success} 张，失败 ${taskData.summary.failed} 张`, 'success');
            } else {
                this._log('[STEP 3] Generation failed:', result.error);
                App.toast(result.error || '生成失败', 'error');
            }
        } catch (err) {
            this._log('[STEP 3] Generation error:', err);
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
