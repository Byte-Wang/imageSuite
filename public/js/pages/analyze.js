const AnalyzePage = {
    async render() {
        const app = document.getElementById('app');
        app.innerHTML = `
            <div class="page-header section-light">
                <h2>商品分析</h2>
                <p class="page-header-desc">上传商品图片，AI 自动分析卖点、目标人群、使用场景</p>
            </div>
            <div class="page-content">
                <div class="form-section">
                    <div class="form-section-title">上传商品图片</div>
                    <div id="analyzeUpload"></div>
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

                <div style="text-align:center;margin:24px 0">
                    <button class="btn btn-primary btn-lg" id="analyzeBtn">
                        开始分析
                    </button>
                </div>

                <div id="analyzeResult" style="display:none">
                    <div class="form-section">
                        <div class="form-section-title">分析结果</div>
                        <div class="result-panel">
                            <div id="analyzeJson" class="json-viewer"></div>
                        </div>
                    </div>

                    <div style="text-align:center;margin-top:16px">
                        <button class="btn btn-primary" id="analyzeToGenerate">
                            使用此结果生成套图 →
                        </button>
                        <button class="btn btn-outline" id="analyzeCopyJson" style="margin-left:8px">
                            复制 JSON
                        </button>
                    </div>
                </div>
            </div>
        `;

        UploadComponent.init('analyzeUpload', {
            name: 'images',
            multiple: true,
            maxFiles: 5,
            label: '拖拽商品图片到此处，或点击选择',
            hint: '支持正面图、背面图等多角度，最多5张'
        });

        document.getElementById('analyzeBtn').addEventListener('click', () => this.runAnalyze());
        document.getElementById('analyzeToGenerate').addEventListener('click', () => {
            if (this._lastResult) {
                window.location.hash = '#/generate';
                setTimeout(() => {
                    if (typeof GeneratePage !== 'undefined' && GeneratePage.setProductData) {
                        GeneratePage.setProductData(this._lastResult);
                    }
                }, 100);
            }
        });
        document.getElementById('analyzeCopyJson').addEventListener('click', () => {
            if (this._lastResult) {
                navigator.clipboard.writeText(JSON.stringify(this._lastResult, null, 2))
                    .then(() => App.toast('已复制到剪贴板', 'success'))
                    .catch(() => App.toast('复制失败', 'error'));
            }
        });
    },

    async runAnalyze() {
        const files = UploadComponent.getFiles('analyzeUpload');
        if (files.length === 0) {
            App.toast('请先上传商品图片', 'error');
            return;
        }

        const btn = document.getElementById('analyzeBtn');
        btn.disabled = true;
        btn.innerHTML = '<div class="spinner spinner-white" style="width:16px;height:16px"></div> 分析中...';

        try {
            const data = {
                provider: document.getElementById('analyzeProvider').value,
                lang: document.getElementById('analyzeLang').value,
            };

            const result = await Api.analyze(data, { images: files });

            if (result.success && result.data) {
                this._lastResult = result.data;
                this.renderResult(result.data);
                App.toast('分析完成', 'success');
            } else {
                App.toast(result.error || '分析失败', 'error');
            }
        } catch (err) {
            App.toast(err.message || '分析请求失败', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = '开始分析';
        }
    },

    renderResult(data) {
        const resultDiv = document.getElementById('analyzeResult');
        resultDiv.style.display = 'block';

        const jsonDiv = document.getElementById('analyzeJson');
        jsonDiv.innerHTML = this.syntaxHighlight(JSON.stringify(data, null, 2));

        resultDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    syntaxHighlight(json) {
        return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, (match) => {
            let cls = 'json-number';
            if (/^"/.test(match)) {
                cls = /:$/.test(match) ? 'json-key' : 'json-string';
            } else if (/true|false/.test(match)) {
                cls = 'json-bool';
            }
            return `<span class="${cls}">${match}</span>`;
        });
    },

    _lastResult: null
};
