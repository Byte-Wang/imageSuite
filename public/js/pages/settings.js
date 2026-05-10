const SettingsPage = {
    async render() {
        const app = document.getElementById('app');
        app.innerHTML = `
            <div class="page-header section-light">
                <h2>设置</h2>
                <p class="page-header-desc">查看供应商配置状态和系统信息</p>
            </div>
            <div class="page-content">
                <div class="settings-section">
                    <h3>供应商状态</h3>
                    <div class="provider-grid" id="providerGrid">
                        <div style="text-align:center;padding:40px">
                            <div class="spinner"></div>
                            <p style="margin-top:12px;font-size:14px;color:var(--color-text-tertiary)">加载中...</p>
                        </div>
                    </div>
                </div>

                <div class="settings-section">
                    <h3>环境变量配置说明</h3>
                    <div class="result-panel">
                        <table style="width:100%;font-size:14px;border-collapse:collapse">
                            <thead>
                                <tr style="border-bottom:1px solid rgba(0,0,0,0.08)">
                                    <th style="text-align:left;padding:8px;font-weight:600">供应商</th>
                                    <th style="text-align:left;padding:8px;font-weight:600">API Key 环境变量</th>
                                    <th style="text-align:left;padding:8px;font-weight:600">Base URL 环境变量</th>
                                    <th style="text-align:left;padding:8px;font-weight:600">Model 环境变量</th>
                                </tr>
                            </thead>
                            <tbody id="envTable"></tbody>
                        </table>
                    </div>
                </div>

                <div class="settings-section">
                    <h3>系统信息</h3>
                    <div class="settings-row">
                        <span class="settings-label">应用名称</span>
                        <span class="settings-value" id="sysAppName">-</span>
                    </div>
                    <div class="settings-row">
                        <span class="settings-label">默认语言</span>
                        <span class="settings-value" id="sysDefaultLang">-</span>
                    </div>
                    <div class="settings-row">
                        <span class="settings-label">默认模板</span>
                        <span class="settings-value" id="sysDefaultTemplate">-</span>
                    </div>
                    <div class="settings-row">
                        <span class="settings-label">支持的图片类型</span>
                        <span class="settings-value" id="sysImageTypes">-</span>
                    </div>
                </div>
            </div>
        `;

        this.loadProviders();
        this.loadConfig();
    },

    async loadProviders() {
        try {
            const result = await Api.getProviders();
            if (result.success && result.data) {
                this.renderProviders(result.data);
            }
        } catch (err) {
            document.getElementById('providerGrid').innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">⚠</div>
                    <div class="empty-state-title">加载失败</div>
                    <div class="empty-state-desc">${err.message}</div>
                </div>
            `;
        }
    },

    renderProviders(data) {
        const grid = document.getElementById('providerGrid');
        grid.innerHTML = (data.all || []).map(p => `
            <div class="provider-card">
                <div class="provider-card-header">
                    <span class="provider-card-name">${p.name}</span>
                    <span class="badge ${p.configured ? 'badge-success' : 'badge-error'}">
                        ${p.configured ? '已配置' : '未配置'}
                    </span>
                </div>
                <div class="provider-card-company">${p.company}</div>
                <div class="provider-card-model">模型: ${p.model}</div>
                ${p.key_preview ? `<div style="font-size:12px;color:var(--color-text-tertiary)">Key: ${p.key_preview}</div>` : ''}
                ${p.custom_url ? `<div style="font-size:12px;color:var(--color-text-tertiary)">URL: ${p.custom_url}</div>` : ''}
                ${p.supports_reference ? '<div style="margin-top:4px"><span class="badge badge-info">支持参考图</span></div>' : ''}
            </div>
        `).join('');

        const envVars = [
            { name: 'OpenAI', key: 'OPENAI_API_KEY', url: 'OPENAI_BASE_URL', model: 'OPENAI_MODEL' },
            { name: 'Gemini', key: 'GEMINI_API_KEY', url: 'GEMINI_BASE_URL', model: 'GEMINI_MODEL' },
            { name: 'Stability', key: 'STABILITY_API_KEY', url: 'STABILITY_BASE_URL', model: 'STABILITY_MODEL' },
            { name: '千问', key: 'DASHSCOPE_API_KEY', url: 'DASHSCOPE_BASE_URL', model: 'DASHSCOPE_MODEL' },
            { name: '豆包', key: 'ARK_API_KEY', url: 'ARK_BASE_URL', model: 'ARK_IMAGE_MODEL' },
        ];

        document.getElementById('envTable').innerHTML = envVars.map(v => `
            <tr style="border-bottom:1px solid rgba(0,0,0,0.04)">
                <td style="padding:8px">${v.name}</td>
                <td style="padding:8px"><code style="font-size:12px;background:var(--color-light-gray);padding:2px 6px;border-radius:4px">${v.key}</code></td>
                <td style="padding:8px"><code style="font-size:12px;background:var(--color-light-gray);padding:2px 6px;border-radius:4px">${v.url}</code></td>
                <td style="padding:8px"><code style="font-size:12px;background:var(--color-light-gray);padding:2px 6px;border-radius:4px">${v.model}</code></td>
            </tr>
        `).join('');
    },

    async loadConfig() {
        try {
            const result = await Api.getConfig();
            if (result.success && result.data) {
                const cfg = result.data;
                document.getElementById('sysAppName').textContent = cfg.app?.name || '-';
                document.getElementById('sysDefaultLang').textContent = cfg.defaults?.lang === 'zh' ? '中文' : 'English';
                document.getElementById('sysDefaultTemplate').textContent = `模板 ${cfg.defaults?.template_set || 1}`;
                document.getElementById('sysImageTypes').textContent = Object.values(cfg.image_types || {}).join('、');
            }
        } catch (err) {
            console.error('加载配置失败:', err);
        }
    }
};
