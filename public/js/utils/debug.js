/**
 * 调试日志工具
 * 
 * 使用说明：
 * - 修改 DEBUG_ENABLED 常量来控制日志开关
 * - true: 开启所有调试日志
 * - false: 关闭所有调试日志
 */
const DEBUG_ENABLED = true;

const Debug = {
    _enabled: DEBUG_ENABLED,
    _prefix: '[Workflow]',

    enable() {
        this._enabled = true;
    },

    disable() {
        this._enabled = false;
    },

    log(...args) {
        if (this._enabled) {
            console.log(this._prefix, ...args);
        }
    },

    error(...args) {
        if (this._enabled) {
            console.error(this._prefix, ...args);
        }
    },

    warn(...args) {
        if (this._enabled) {
            console.warn(this._prefix, ...args);
        }
    },

    info(...args) {
        if (this._enabled) {
            console.info(this._prefix, ...args);
        }
    },

    group(label) {
        if (this._enabled) {
            console.group(`${this._prefix} ${label}`);
        }
    },

    groupEnd() {
        if (this._enabled) {
            console.groupEnd();
        }
    },

    data(label, data) {
        if (this._enabled) {
            console.log(`${this._prefix} ${label}:`, data);
        }
    }
};
