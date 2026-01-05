"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
exports.ActivityTracker = void 0;
const vscode = __importStar(require("vscode"));
const https = __importStar(require("https"));
const http = __importStar(require("http"));
class ActivityTracker {
    constructor(context) {
        this.activeTime = 0;
        this.afkTime = 0;
        this.lastActivity = new Date();
        this.sessionStart = new Date();
        this.isAfk = false;
        this.syncInterval = null;
        this.tickInterval = null;
        this.disposables = [];
        this.linesTyped = 0;
        this.languages = new Map();
        this.hourlyActivity = new Map();
        this.context = context;
        this.sessionId = this.generateSessionId();
        this.loadState();
    }
    generateSessionId() {
        return `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }
    loadState() {
        const today = new Date().toDateString();
        const savedDate = this.context.globalState.get('lastDate');
        if (savedDate === today) {
            this.activeTime = this.context.globalState.get('activeTime', 0);
            this.afkTime = this.context.globalState.get('afkTime', 0);
            this.linesTyped = this.context.globalState.get('linesTyped', 0);
            const savedLangs = this.context.globalState.get('languages', []);
            this.languages = new Map(savedLangs);
            const savedHourly = this.context.globalState.get('hourlyActivity', []);
            this.hourlyActivity = new Map(savedHourly);
        }
        else {
            // Novo dia, resetar contadores
            this.activeTime = 0;
            this.afkTime = 0;
            this.linesTyped = 0;
            this.languages = new Map();
            this.hourlyActivity = new Map();
            this.context.globalState.update('lastDate', today);
        }
    }
    saveState() {
        this.context.globalState.update('activeTime', this.activeTime);
        this.context.globalState.update('afkTime', this.afkTime);
        this.context.globalState.update('linesTyped', this.linesTyped);
        this.context.globalState.update('languages', Array.from(this.languages.entries()));
        this.context.globalState.update('hourlyActivity', Array.from(this.hourlyActivity.entries()));
        this.context.globalState.update('lastDate', new Date().toDateString());
    }
    start() {
        this.registerActivityListeners();
        this.startTicking();
        this.startSyncing();
        console.log('Activity Tracker started');
    }
    stop() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
        }
        if (this.tickInterval) {
            clearInterval(this.tickInterval);
        }
        this.disposables.forEach(d => d.dispose());
        this.saveState();
        this.syncToApi();
        console.log('Activity Tracker stopped');
    }
    registerActivityListeners() {
        // Detectar atividade do editor
        this.disposables.push(vscode.window.onDidChangeActiveTextEditor(() => this.recordActivity()), vscode.window.onDidChangeTextEditorSelection(() => this.recordActivity()), vscode.workspace.onDidChangeTextDocument((e) => this.onDocumentChange(e)), vscode.window.onDidChangeWindowState((e) => {
            if (e.focused) {
                this.recordActivity();
            }
        }), vscode.workspace.onDidOpenTextDocument(() => this.recordActivity()), vscode.workspace.onDidSaveTextDocument(() => this.recordActivity()), vscode.window.onDidOpenTerminal(() => this.recordActivity()), vscode.window.onDidChangeActiveTerminal(() => this.recordActivity()));
    }
    onDocumentChange(e) {
        this.recordActivity();
        // Contar linhas digitadas
        for (const change of e.contentChanges) {
            const linesAdded = change.text.split('\n').length - 1;
            const linesChanged = Math.max(1, linesAdded + 1);
            this.linesTyped += linesChanged;
        }
        // Registrar linguagem
        const lang = e.document.languageId;
        if (lang && lang !== 'plaintext') {
            const current = this.languages.get(lang) || 0;
            this.languages.set(lang, current + 1);
        }
        // Registrar atividade por hora
        const hour = new Date().getHours().toString().padStart(2, '0');
        const currentHourly = this.hourlyActivity.get(hour) || 0;
        this.hourlyActivity.set(hour, currentHourly + 1);
    }
    recordActivity() {
        this.lastActivity = new Date();
        if (this.isAfk) {
            this.isAfk = false;
            console.log('User returned from AFK');
        }
    }
    startTicking() {
        this.tickInterval = setInterval(() => {
            const config = vscode.workspace.getConfiguration('activityTracker');
            const afkTimeout = config.get('afkTimeout', 300);
            const now = new Date();
            const secondsSinceActivity = Math.floor((now.getTime() - this.lastActivity.getTime()) / 1000);
            if (secondsSinceActivity >= afkTimeout) {
                if (!this.isAfk) {
                    this.isAfk = true;
                    console.log('User is now AFK');
                }
                this.afkTime++;
            }
            else {
                this.activeTime++;
            }
            this.saveState();
        }, 1000);
    }
    startSyncing() {
        const config = vscode.workspace.getConfiguration('activityTracker');
        const syncIntervalSeconds = config.get('syncInterval', 30);
        this.syncInterval = setInterval(() => {
            this.syncToApi();
        }, syncIntervalSeconds * 1000);
        // Sync inicial
        setTimeout(() => this.syncToApi(), 5000);
    }
    async syncToApi() {
        const config = vscode.workspace.getConfiguration('activityTracker');
        const endpoint = config.get('apiEndpoint', 'http://localhost:8000/api');
        // Converter Map para objeto
        const languagesObj = {};
        this.languages.forEach((value, key) => {
            languagesObj[key] = value;
        });
        const hourlyObj = {};
        this.hourlyActivity.forEach((value, key) => {
            hourlyObj[key] = value;
        });
        const data = {
            user_id: this.getUserId(),
            session_id: this.sessionId,
            active_time: this.activeTime,
            afk_time: this.afkTime,
            is_active: !this.isAfk,
            workspace: vscode.workspace.name || 'Unknown',
            timestamp: new Date().toISOString(),
            lines_typed: this.linesTyped,
            languages: languagesObj,
            hourly_activity: hourlyObj
        };
        try {
            await this.postData(`${endpoint}/activity.php`, data);
            console.log('Activity synced to API');
        }
        catch (error) {
            console.error('Failed to sync activity:', error);
        }
    }
    getUserId() {
        let userId = this.context.globalState.get('userId');
        if (!userId) {
            userId = `user_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
            this.context.globalState.update('userId', userId);
        }
        return userId;
    }
    getUserIdPublic() {
        return this.getUserId();
    }
    postData(url, data) {
        return new Promise((resolve, reject) => {
            const postData = JSON.stringify(data);
            const urlObj = new URL(url);
            const options = {
                hostname: urlObj.hostname,
                port: urlObj.port || (urlObj.protocol === 'https:' ? 443 : 80),
                path: urlObj.pathname,
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Content-Length': Buffer.byteLength(postData)
                }
            };
            const client = urlObj.protocol === 'https:' ? https : http;
            const req = client.request(options, (res) => {
                if (res.statusCode && res.statusCode >= 200 && res.statusCode < 300) {
                    resolve();
                }
                else {
                    reject(new Error(`HTTP ${res.statusCode}`));
                }
            });
            req.on('error', reject);
            req.write(postData);
            req.end();
        });
    }
    getStatus() {
        return {
            activeTime: this.activeTime,
            afkTime: this.afkTime,
            isAfk: this.isAfk,
            lastActivity: this.lastActivity,
            sessionStart: this.sessionStart,
            linesTyped: this.linesTyped,
            languages: this.languages
        };
    }
}
exports.ActivityTracker = ActivityTracker;
//# sourceMappingURL=tracker.js.map