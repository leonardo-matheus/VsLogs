import * as vscode from 'vscode';
import { ActivityTracker } from './tracker';

export class SidebarProvider implements vscode.WebviewViewProvider {
    public static readonly viewType = 'vslogs.statsView';
    private _view?: vscode.WebviewView;
    private _tracker: ActivityTracker;

    constructor(
        private readonly _extensionUri: vscode.Uri,
        tracker: ActivityTracker
    ) {
        this._tracker = tracker;
    }

    public resolveWebviewView(
        webviewView: vscode.WebviewView,
        context: vscode.WebviewViewResolveContext,
        _token: vscode.CancellationToken
    ) {
        this._view = webviewView;

        webviewView.webview.options = {
            enableScripts: true,
            localResourceRoots: [this._extensionUri]
        };

        webviewView.webview.html = this._getHtmlForWebview(webviewView.webview);

        // Atualizar a cada segundo
        setInterval(() => {
            this._updateStats();
        }, 1000);

        // Handle messages from webview
        webviewView.webview.onDidReceiveMessage(async (data) => {
            switch (data.type) {
                case 'openDashboard':
                    vscode.commands.executeCommand('activityTracker.openDashboard');
                    break;
                case 'copyToken':
                    vscode.commands.executeCommand('activityTracker.copyToken');
                    break;
            }
        });
    }

    private _updateStats() {
        if (this._view) {
            const status = this._tracker.getStatus();
            const userId = this._tracker.getUserIdPublic();
            this._view.webview.postMessage({
                type: 'updateStats',
                data: {
                    activeTime: status.activeTime,
                    afkTime: status.afkTime,
                    isAfk: status.isAfk,
                    linesTyped: status.linesTyped,
                    languages: Object.fromEntries(status.languages),
                    userId: userId
                }
            });
        }
    }

    private _getHtmlForWebview(webview: vscode.Webview): string {
        return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VsLogs Stats</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: var(--vscode-font-family);
            color: var(--vscode-foreground);
            background: var(--vscode-sideBar-background);
            padding: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--vscode-widget-border);
        }
        .header h2 {
            font-size: 14px;
            font-weight: 600;
            color: var(--vscode-foreground);
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            margin-top: 6px;
        }
        .status-active {
            background: #2ea04366;
            color: #3fb950;
        }
        .status-afk {
            background: #f8514966;
            color: #f85149;
        }
        .stats-grid {
            display: grid;
            gap: 10px;
        }
        .stat-card {
            background: var(--vscode-editor-background);
            border-radius: 6px;
            padding: 12px;
            border: 1px solid var(--vscode-widget-border);
        }
        .stat-card .label {
            font-size: 11px;
            color: var(--vscode-descriptionForeground);
            margin-bottom: 4px;
        }
        .stat-card .value {
            font-size: 18px;
            font-weight: 600;
            color: var(--vscode-foreground);
        }
        .stat-card .value.active { color: #3fb950; }
        .stat-card .value.afk { color: #f85149; }
        .stat-card .value.lines { color: #58a6ff; }
        .languages {
            margin-top: 12px;
        }
        .languages h3 {
            font-size: 12px;
            color: var(--vscode-descriptionForeground);
            margin-bottom: 8px;
        }
        .lang-item {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            padding: 4px 0;
            border-bottom: 1px solid var(--vscode-widget-border);
        }
        .lang-item:last-child {
            border-bottom: none;
        }
        .buttons {
            margin-top: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        button {
            width: 100%;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-primary {
            background: var(--vscode-button-background);
            color: var(--vscode-button-foreground);
        }
        .btn-primary:hover {
            background: var(--vscode-button-hoverBackground);
        }
        .btn-secondary {
            background: var(--vscode-button-secondaryBackground);
            color: var(--vscode-button-secondaryForeground);
        }
        .btn-secondary:hover {
            background: var(--vscode-button-secondaryHoverBackground);
        }
        .token-section {
            margin-top: 12px;
            padding: 8px;
            background: var(--vscode-textBlockQuote-background);
            border-radius: 4px;
            border-left: 3px solid var(--vscode-textLink-foreground);
        }
        .token-section .label {
            font-size: 10px;
            color: var(--vscode-descriptionForeground);
        }
        .token-section .token {
            font-size: 11px;
            font-family: monospace;
            word-break: break-all;
            color: var(--vscode-textLink-foreground);
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>📊 VsLogs</h2>
        <span id="statusBadge" class="status-badge status-active">Ativo</span>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">⏱️ Tempo Ativo</div>
            <div class="value active" id="activeTime">00:00:00</div>
        </div>
        <div class="stat-card">
            <div class="label">😴 Tempo AFK</div>
            <div class="value afk" id="afkTime">00:00:00</div>
        </div>
        <div class="stat-card">
            <div class="label">📝 Linhas Digitadas</div>
            <div class="value lines" id="linesTyped">0</div>
        </div>
    </div>

    <div class="languages" id="languagesSection" style="display:none;">
        <h3>🎨 Linguagens</h3>
        <div id="languagesList"></div>
    </div>

    <div class="token-section">
        <div class="label">🔑 Seu Token de Compartilhamento:</div>
        <div class="token" id="userToken">Carregando...</div>
    </div>

    <div class="buttons">
        <button class="btn-primary" onclick="openDashboard()">
            🌐 Abrir Dashboard
        </button>
        <button class="btn-secondary" onclick="copyToken()">
            📋 Copiar Token
        </button>
    </div>

    <script>
        const vscode = acquireVsCodeApi();

        function formatTime(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            return [h, m, s].map(v => v.toString().padStart(2, '0')).join(':');
        }

        function openDashboard() {
            vscode.postMessage({ type: 'openDashboard' });
        }

        function copyToken() {
            vscode.postMessage({ type: 'copyToken' });
        }

        window.addEventListener('message', event => {
            const message = event.data;
            if (message.type === 'updateStats') {
                const data = message.data;
                
                document.getElementById('activeTime').textContent = formatTime(data.activeTime);
                document.getElementById('afkTime').textContent = formatTime(data.afkTime);
                document.getElementById('linesTyped').textContent = data.linesTyped.toLocaleString();
                document.getElementById('userToken').textContent = data.userId;
                
                const badge = document.getElementById('statusBadge');
                if (data.isAfk) {
                    badge.textContent = 'AFK';
                    badge.className = 'status-badge status-afk';
                } else {
                    badge.textContent = 'Ativo';
                    badge.className = 'status-badge status-active';
                }

                // Languages
                const langSection = document.getElementById('languagesSection');
                const langList = document.getElementById('languagesList');
                const langs = Object.entries(data.languages);
                
                if (langs.length > 0) {
                    langSection.style.display = 'block';
                    langList.innerHTML = langs
                        .sort((a, b) => b[1] - a[1])
                        .slice(0, 5)
                        .map(([lang, lines]) => 
                            '<div class="lang-item"><span>' + lang + '</span><span>' + lines + ' linhas</span></div>'
                        ).join('');
                }
            }
        });
    </script>
</body>
</html>`;
    }
}
