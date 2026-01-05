import * as vscode from 'vscode';
import * as https from 'https';
import * as http from 'http';

interface ActivityStatus {
    activeTime: number;
    afkTime: number;
    isAfk: boolean;
    lastActivity: Date;
    sessionStart: Date;
    linesTyped: number;
    languages: Map<string, number>;
}

interface LanguageStats {
    [key: string]: number;
}

interface ActivityData {
    user_id: string;
    session_id: string;
    active_time: number;
    afk_time: number;
    is_active: boolean;
    workspace: string;
    timestamp: string;
    lines_typed: number;
    languages: LanguageStats;
    hourly_activity: { [hour: string]: number };
}

export class ActivityTracker {
    private context: vscode.ExtensionContext;
    private activeTime: number = 0;
    private afkTime: number = 0;
    private lastActivity: Date = new Date();
    private sessionStart: Date = new Date();
    private sessionId: string;
    private isAfk: boolean = false;
    private syncInterval: NodeJS.Timeout | null = null;
    private tickInterval: NodeJS.Timeout | null = null;
    private disposables: vscode.Disposable[] = [];
    private linesTyped: number = 0;
    private languages: Map<string, number> = new Map();
    private hourlyActivity: Map<string, number> = new Map();

    constructor(context: vscode.ExtensionContext) {
        this.context = context;
        this.sessionId = this.generateSessionId();
        this.loadState();
    }

    private generateSessionId(): string {
        return `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    private loadState(): void {
        const today = new Date().toDateString();
        const savedDate = this.context.globalState.get<string>('lastDate');
        
        if (savedDate === today) {
            this.activeTime = this.context.globalState.get<number>('activeTime', 0);
            this.afkTime = this.context.globalState.get<number>('afkTime', 0);
            this.linesTyped = this.context.globalState.get<number>('linesTyped', 0);
            const savedLangs = this.context.globalState.get<[string, number][]>('languages', []);
            this.languages = new Map(savedLangs);
            const savedHourly = this.context.globalState.get<[string, number][]>('hourlyActivity', []);
            this.hourlyActivity = new Map(savedHourly);
        } else {
            // Novo dia, resetar contadores
            this.activeTime = 0;
            this.afkTime = 0;
            this.linesTyped = 0;
            this.languages = new Map();
            this.hourlyActivity = new Map();
            this.context.globalState.update('lastDate', today);
        }
    }

    private saveState(): void {
        this.context.globalState.update('activeTime', this.activeTime);
        this.context.globalState.update('afkTime', this.afkTime);
        this.context.globalState.update('linesTyped', this.linesTyped);
        this.context.globalState.update('languages', Array.from(this.languages.entries()));
        this.context.globalState.update('hourlyActivity', Array.from(this.hourlyActivity.entries()));
        this.context.globalState.update('lastDate', new Date().toDateString());
    }

    public start(): void {
        this.registerActivityListeners();
        this.startTicking();
        this.startSyncing();
        
        console.log('Activity Tracker started');
    }

    public stop(): void {
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

    private registerActivityListeners(): void {
        // Detectar atividade do editor
        this.disposables.push(
            vscode.window.onDidChangeActiveTextEditor(() => this.recordActivity()),
            vscode.window.onDidChangeTextEditorSelection(() => this.recordActivity()),
            vscode.workspace.onDidChangeTextDocument((e) => this.onDocumentChange(e)),
            vscode.window.onDidChangeWindowState((e) => {
                if (e.focused) {
                    this.recordActivity();
                }
            }),
            vscode.workspace.onDidOpenTextDocument(() => this.recordActivity()),
            vscode.workspace.onDidSaveTextDocument(() => this.recordActivity()),
            vscode.window.onDidOpenTerminal(() => this.recordActivity()),
            vscode.window.onDidChangeActiveTerminal(() => this.recordActivity())
        );
    }

    private onDocumentChange(e: vscode.TextDocumentChangeEvent): void {
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

    private recordActivity(): void {
        this.lastActivity = new Date();
        if (this.isAfk) {
            this.isAfk = false;
            console.log('User returned from AFK');
        }
    }

    private startTicking(): void {
        this.tickInterval = setInterval(() => {
            const config = vscode.workspace.getConfiguration('activityTracker');
            const afkTimeout = config.get<number>('afkTimeout', 300);
            
            const now = new Date();
            const secondsSinceActivity = Math.floor(
                (now.getTime() - this.lastActivity.getTime()) / 1000
            );

            if (secondsSinceActivity >= afkTimeout) {
                if (!this.isAfk) {
                    this.isAfk = true;
                    console.log('User is now AFK');
                }
                this.afkTime++;
            } else {
                this.activeTime++;
            }

            this.saveState();
        }, 1000);
    }

    private startSyncing(): void {
        const config = vscode.workspace.getConfiguration('activityTracker');
        const syncIntervalSeconds = config.get<number>('syncInterval', 30);

        this.syncInterval = setInterval(() => {
            this.syncToApi();
        }, syncIntervalSeconds * 1000);

        // Sync inicial
        setTimeout(() => this.syncToApi(), 5000);
    }

    private async syncToApi(): Promise<void> {
        const config = vscode.workspace.getConfiguration('activityTracker');
        const endpoint = config.get<string>('apiEndpoint', 'http://localhost:8000/api');

        // Converter Map para objeto
        const languagesObj: LanguageStats = {};
        this.languages.forEach((value, key) => {
            languagesObj[key] = value;
        });

        const hourlyObj: { [hour: string]: number } = {};
        this.hourlyActivity.forEach((value, key) => {
            hourlyObj[key] = value;
        });

        const data: ActivityData = {
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
        } catch (error) {
            console.error('Failed to sync activity:', error);
        }
    }

    private getUserId(): string {
        let userId = this.context.globalState.get<string>('userId');
        if (!userId) {
            userId = `user_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
            this.context.globalState.update('userId', userId);
        }
        return userId;
    }

    public getUserIdPublic(): string {
        return this.getUserId();
    }

    private postData(url: string, data: any): Promise<void> {
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
                } else {
                    reject(new Error(`HTTP ${res.statusCode}`));
                }
            });

            req.on('error', reject);
            req.write(postData);
            req.end();
        });
    }

    public getStatus(): ActivityStatus {
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
