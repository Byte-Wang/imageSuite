const VideoPage = {
    async render() {
        const app = document.getElementById('app');
        app.innerHTML = `
            <div class="page-header section-light">
                <h2>视频生成</h2>
                <p class="page-header-desc">基于商品图片和卖点，生成电商展示视频</p>
            </div>
            <div class="page-content">
                <div class="form-section">
                    <div class="form-section-title">参考图片</div>
                    <div id="videoUpload"></div>
                    <p style="font-size:14px;color:var(--color-text-tertiary);margin-top:8px">
                        不上传图片时，将自动使用最近生成的套图
                    </p>
                </div>

                <div class="form-section">
                    <div class="form-section-title">视频设置</div>
                    <div class="form-row-3">
                        <div class="input-group">
                            <label class="input-label">画面比例</label>
                            <select class="input-field" id="videoRatio">
                                <option value="16:9">16:9 横屏</option>
                                <option value="9:16">9:16 竖屏</option>
                                <option value="1:1">1:1 方形</option>
                                <option value="4:3">4:3</option>
                                <option value="3:4">3:4</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label class="input-label">时长（秒）</label>
                            <select class="input-field" id="videoDuration">
                                <option value="4">4秒</option>
                                <option value="5">5秒</option>
                                <option value="6" selected>6秒</option>
                                <option value="8">8秒</option>
                                <option value="10">10秒</option>
                                <option value="12">12秒</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label class="input-label">生成声音</label>
                            <div style="padding-top:6px">
                                <div class="toggle" id="videoAudioToggle"></div>
                            </div>
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-label">自定义 Prompt（可选）</label>
                        <textarea class="input-field" id="videoPrompt" rows="3"
                            placeholder="留空将根据商品信息自动生成视频描述"></textarea>
                    </div>
                </div>

                <div style="text-align:center;margin:32px 0">
                    <button class="btn btn-primary btn-lg btn-pill" id="videoBtn">
                        生成视频
                    </button>
                </div>

                <div id="videoResult" style="display:none">
                    <div class="result-panel">
                        <h4 style="margin-bottom:16px">生成结果</h4>
                        <div id="videoPlayer"></div>
                    </div>
                </div>
            </div>
        `;

        UploadComponent.init('videoUpload', {
            name: 'images',
            multiple: true,
            maxFiles: 5,
            label: '上传参考图片',
            hint: '将用于视频内容参考'
        });

        const audioToggle = document.getElementById('videoAudioToggle');
        audioToggle.addEventListener('click', () => {
            audioToggle.classList.toggle('active');
        });

        document.getElementById('videoBtn').addEventListener('click', () => this.runGenerate());
    },

    async runGenerate() {
        const btn = document.getElementById('videoBtn');
        btn.disabled = true;
        btn.innerHTML = '<div class="spinner spinner-white" style="width:16px;height:16px"></div> 生成中，请耐心等待...';

        try {
            const data = {
                ratio: document.getElementById('videoRatio').value,
                duration: document.getElementById('videoDuration').value,
                audio: document.getElementById('videoAudioToggle').classList.contains('active') ? 'true' : 'false',
                prompt: document.getElementById('videoPrompt').value,
            };

            const files = UploadComponent.getFiles('videoUpload');
            const fileData = files.length > 0 ? { images: files } : null;

            const result = await Api.generateVideo(data, fileData);

            if (result.success && result.data) {
                this.renderResult(result.data);
                App.toast('视频生成完成', 'success');
            } else {
                App.toast(result.error || '视频生成失败', 'error');
            }
        } catch (err) {
            App.toast(err.message || '视频生成请求失败', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = '生成视频';
        }
    },

    renderResult(data) {
        const resultDiv = document.getElementById('videoResult');
        resultDiv.style.display = 'block';

        const playerDiv = document.getElementById('videoPlayer');
        if (data.video_url) {
            playerDiv.innerHTML = `
                <video controls style="width:100%;max-width:640px;border-radius:8px;margin:0 auto;display:block">
                    <source src="${data.video_url}" type="video/mp4">
                    您的浏览器不支持视频播放
                </video>
                <p style="margin-top:12px;font-size:14px;color:var(--color-text-tertiary);text-align:center">
                    Task ID: ${data.task_id || '-'}
                </p>
            `;
        } else {
            playerDiv.innerHTML = '<p>视频已生成，但无法预览</p>';
        }

        resultDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
