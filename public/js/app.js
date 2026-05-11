const App = {
    init() {
        Router.register('home', () => this.renderHome());
        Router.register('workflow', () => WorkflowPage.render());
        Router.register('analyze', () => AnalyzePage.render());
        Router.register('generate', () => GeneratePage.render());
        Router.register('video', () => VideoPage.render());

        Router.init();

        const hamburger = document.getElementById('navHamburger');
        const mobileMenu = document.getElementById('mobileMenu');
        hamburger.addEventListener('click', () => {
            mobileMenu.classList.toggle('open');
        });

        document.querySelectorAll('.mobile-menu-link').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.remove('open');
            });
        });
    },

    renderHome() {
        const app = document.getElementById('app');
        app.innerHTML = `
            <section class="hero hero-dark">
                <div class="container">
                    <h1>E-Commerce<br>Image Suite</h1>
                    <p class="hero-subtitle">AI 驱动的电商套图生成平台，一键生成8种专业商品图片</p>
                    <div class="hero-actions">
                        <a href="#/workflow" class="btn btn-primary btn-lg btn-pill">开始使用</a>
                        <a href="#/video" class="btn btn-outline btn-lg btn-pill" style="color:#fff;border-color:#fff">视频生成</a>
                    </div>
                </div>
            </section>

            <section class="section section-light">
                <div class="container" style="text-align:center">
                    <h2 style="margin-bottom:40px">工作流程</h2>
                    <div style="max-width:800px;margin:0 auto">
                        <div style="display:flex;align-items:center;justify-content:center;gap:32px;flex-wrap:wrap">
                            <div style="text-align:center">
                                <div style="width:80px;height:80px;border-radius:50%;background:var(--color-apple-blue);display:flex;align-items:center;justify-content:center;color:white;font-size:28px;margin-bottom:12px">1</div>
                                <div style="font-size:15px;font-weight:600">上传图片</div>
                                <div style="font-size:13px;color:var(--color-text-tertiary)">拖拽或选择商品图片</div>
                            </div>
                            <div style="font-size:32px;color:var(--color-text-tertiary)">→</div>
                            <div style="text-align:center">
                                <div style="width:80px;height:80px;border-radius:50%;background:var(--color-apple-blue);display:flex;align-items:center;justify-content:center;color:white;font-size:28px;margin-bottom:12px">2</div>
                                <div style="font-size:15px;font-weight:600">AI分析</div>
                                <div style="font-size:13px;color:var(--color-text-tertiary)">自动提取商品信息</div>
                            </div>
                            <div style="font-size:32px;color:var(--color-text-tertiary)">→</div>
                            <div style="text-align:center">
                                <div style="width:80px;height:80px;border-radius:50%;background:var(--color-apple-blue);display:flex;align-items:center;justify-content:center;color:white;font-size:28px;margin-bottom:12px">3</div>
                                <div style="font-size:15px;font-weight:600">编辑信息</div>
                                <div style="font-size:13px;color:var(--color-text-tertiary)">调整卖点和场景</div>
                            </div>
                            <div style="font-size:32px;color:var(--color-text-tertiary)">→</div>
                            <div style="text-align:center">
                                <div style="width:80px;height:80px;border-radius:50%;background:#34c759;display:flex;align-items:center;justify-content:center;color:white;font-size:28px;margin-bottom:12px">4</div>
                                <div style="font-size:15px;font-weight:600">生成套图</div>
                                <div style="font-size:13px;color:var(--color-text-tertiary)">一键生成8种图片</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section section-dark" style="color:var(--color-white)">
                <div class="container" style="text-align:center">
                    <h2 style="margin-bottom:16px;color:var(--color-white)">8种专业电商图片</h2>
                    <p style="font-size:17px;color:rgba(255,255,255,0.7);margin-bottom:40px">覆盖电商全场景，从白底主图到详情页一应俱全</p>
                    <div class="grid-3" style="gap:12px">
                        ${[
                            ['白底主图', '纯白背景，商品居中'],
                            ['核心卖点图', '图标+文案信息图'],
                            ['卖点图', '场景化卖点展示'],
                            ['材质图', '微距特写纹理'],
                            ['场景展示图', '真实使用场景'],
                            ['模特展示图', '真人穿着效果'],
                            ['多场景拼图', '多场景一致性展示'],
                            ['电商详情图', '完整详情页设计'],
                            ['三角度拼图', '360度全方位展示']
                        ].map(([name, desc]) => `
                            <div style="background:var(--color-dark-surface-3);border-radius:8px;padding:16px;text-align:left">
                                <div style="font-size:15px;font-weight:600;margin-bottom:4px">${name}</div>
                                <div style="font-size:13px;color:rgba(255,255,255,0.5)">${desc}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </section>

            <section class="section section-light">
                <div class="container" style="text-align:center">
                    <h2 style="margin-bottom:16px">5大图像供应商</h2>
                    <p style="font-size:17px;color:var(--color-text-tertiary);margin-bottom:40px">国内直连 + 国际覆盖，灵活选择</p>
                    <div class="grid-2" style="max-width:640px;margin:0 auto">
                        ${[
                            ['千问 wan2.7', '阿里云 DashScope', '国内直连，推荐'],
                            ['豆包 Seedream', '字节跳动', '国内直连，风格多样'],
                            ['OpenAI GPT Image', 'OpenAI', '高质量写实'],
                            ['Gemini Imagen', 'Google', '细节优秀'],
                        ].map(([name, company, desc]) => `
                            <div class="card card-flat">
                                <div class="card-body">
                                    <div style="font-size:15px;font-weight:600;margin-bottom:2px">${name}</div>
                                    <div style="font-size:12px;color:var(--color-text-tertiary);margin-bottom:4px">${company}</div>
                                    <div style="font-size:13px;color:var(--color-text-secondary)">${desc}</div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </section>
        `;
    },

    toast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'toastOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
};

document.addEventListener('DOMContentLoaded', () => App.init());
