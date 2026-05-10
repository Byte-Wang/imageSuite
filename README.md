# E-Commerce Image Suite

AI 驱动的电商套图生成平台，支持商品智能分析、8种电商图片生成、视频生成。

## 功能概览

### 1. 商品分析
上传商品图片，AI 自动分析并提取：
- 商品名称与描述
- 目标人群与使用场景
- 核心卖点（材质/版型/设计/舒适度）
- 视觉特征与风格定位

### 2. 套图生成
一键生成 8 种专业电商图片：

| 图片类型 | 说明 |
|---------|------|
| 白底主图 | 纯白背景，商品居中 |
| 核心卖点图 | 图标+文案信息图 |
| 卖点图 | 场景化卖点展示 |
| 材质图 | 微距特写纹理 |
| 场景展示图 | 真实使用场景 |
| 模特展示图 | 真人穿着效果 |
| 多场景拼图 | 多场景一致性展示 |
| 电商详情图 | 完整详情页设计 |

### 3. 视频生成
基于商品图片和卖点，生成电商展示视频，支持多种比例和时长。

## 环境要求

- PHP 8.0+
- Apache（需启用 mod_rewrite）或 Nginx
- PHP 扩展：json, curl, fileinfo

## 安装部署

### 1. 克隆项目

```bash
git clone <repo-url>
cd phpProject
```

### 2. 配置 Web 服务器

**Apache**（已包含 `.htaccess`）：
```apache
<VirtualHost *:80>
    DocumentRoot "/path/to/phpProject/public"
    ServerName your-domain.com
    <Directory "/path/to/phpProject/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx**：
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/phpProject/public;
    index index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 3. 配置 API Key

#### 阿里云通义千问

**视觉分析**（商品分析用）：
```bash
export DASHSCOPE_API_KEY="sk-xxxxxxxxxxxx"
```

**图像生成**（套图生成用）：
```bash
export DASHSCOPE_API_KEY="sk-xxxxxxxxxxxx"
export DASHSCOPE_MODEL="wan2.7-image-pro"
```

#### 其他供应商

| 供应商 | API Key 环境变量 | 说明 |
|-------|-----------------|------|
| 阿里云通义千问 | `DASHSCOPE_API_KEY` | 国内直连，推荐 |
| 字节豆包 | `ARK_API_KEY` | 国内直连 |
| OpenAI | `OPENAI_API_KEY` | GPT-4o 视觉分析 / DALL·E 3 图像 |
| Google Gemini | `GEMINI_API_KEY` | Imagen 3 图像 |
| Stability AI | `STABILITY_API_KEY` | Stable Image Core |

也可以不配置环境变量，在前端页面或 API 参数中直接传入 `api_key`。

### 4. 设置目录权限

```bash
chmod -R 755 storage/
```

## API 接口

### 商品分析

```
POST /api/analyze
```

**参数：**
```json
{
  "provider": "tongyi",
  "api_key": "sk-xxxx",
  "lang": "zh",
  "images": ["base64编码的图片或URL"]
}
```

**响应：**
```json
{
  "success": true,
  "data": {
    "product_name": "夏季宽松字母印花T恤",
    "product_type": "服装",
    "garment_position": "top",
    "selling_points": [...],
    "target_audience": "年轻女性",
    "target_scenes": ["居家", "出行"],
    "product_style": "休闲"
  }
}
```

### 套图生成

```
POST /api/generate
```

**参数：**
```json
{
  "provider": "tongyi",
  "api_key": "sk-xxxx",
  "product": { /* 商品分析结果 */ },
  "types": "white_bg,key_features,selling_pt,material,lifestyle,model,multi_scene,ecommerce_detail",
  "template_set": 1,
  "model_style": "standard",
  "product_images": ["base64编码的图片或URL"]
}
```

**响应：**
```json
{
  "success": true,
  "data": {
    "task_id": "task_abc123",
    "output_dir": "/path/to/storage/output/task_abc123",
    "results": {
      "white_bg": { "status": "ok", "path": "/path/to/白底主图.jpg" },
      "key_features": { "status": "ok", "path": "/path/to/核心卖点图.jpg" }
    },
    "summary": { "total": 8, "success": 8, "failed": 0 }
  }
}
```

### 查询生成状态

```
GET /api/generate/{task_id}
```

### 视频生成

```
POST /api/video
```

**参数：**
```json
{
  "ratio": "16:9",
  "duration": 6,
  "audio": false,
  "prompt": "商品展示视频...",
  "images": ["base64编码的图片或URL"]
}
```

### 获取供应商状态

```
GET /api/providers
```

### 获取系统配置

```
GET /api/config
```

## 前端页面

部署后访问 `http://your-domain.com/` 即可使用：

- **商品分析**：上传图片 → AI 分析 → 获取结构化信息
- **套图生成**：粘贴商品 JSON → 选择类型/模板 → 一键生成
- **视频生成**：上传参考图 → 设置参数 → 生成视频
- **设置**：查看供应商配置状态

## 目录结构

```
phpProject/
├── index.php              # 入口文件
├── .htaccess              # Apache URL 重写
├── config/
│   ├── config.php         # 主配置
│   └── providers.php      # 供应商配置
├── app/
│   ├── Controllers/       # 控制器
│   ├── Services/          # 服务层（核心业务）
│   ├── Providers/         # 供应商适配器
│   ├── Middleware/        # 中间件
│   └── Utils/             # 工具类
├── public/
│   ├── index.html         # 前端页面
│   ├── css/               # 样式文件
│   └── js/                # JavaScript
└── storage/
    ├── output/            # 生成的图片
    ├── uploads/           # 上传文件
    └── logs/              # 日志
```

## 错误排查

生成失败时，错误日志会保存在 `storage/logs/error_logs/` 目录，包含：
- 请求时间、供应商、图片类型
- 错误类型和消息
- 完整的 Prompt 内容

## License

MIT
