import * as vscode from 'vscode';
import { ActivityTracker } from './tracker';

let tracker: ActivityTracker;

export function activate(context: vscode.ExtensionContext) {
    console.log('Activity Tracker is now active!');

    tracker = new ActivityTracker(context);
    tracker.start();

    // Comando para mostrar status
    const showStatusCommand = vscode.commands.registerCommand(
        'activityTracker.showStatus',
        () => {
            const status = tracker.getStatus();
            vscode.window.showInformationMessage(
                `⏱️ Tempo ativo: ${formatTime(status.activeTime)} | ` +
                `😴 Tempo AFK: ${formatTime(status.afkTime)} | ` +
                `Status: ${status.isAfk ? 'AFK' : 'Ativo'}`
            );
        }
    );

    // Comando para abrir dashboard
    const openDashboardCommand = vscode.commands.registerCommand(
        'activityTracker.openDashboard',
        () => {
            const config = vscode.workspace.getConfiguration('activityTracker');
            const endpoint = config.get<string>('apiEndpoint', 'http://localhost:8080/api');
            const dashboardUrl = endpoint.replace('/api', '/dashboard.html');
            vscode.env.openExternal(vscode.Uri.parse(dashboardUrl));
        }
    );

    context.subscriptions.push(showStatusCommand, openDashboardCommand);

    // Status bar
    const statusBarItem = vscode.window.createStatusBarItem(
        vscode.StatusBarAlignment.Right,
        100
    );
    statusBarItem.command = 'activityTracker.showStatus';
    context.subscriptions.push(statusBarItem);

    // Atualizar status bar a cada segundo
    setInterval(() => {
        const status = tracker.getStatus();
        const icon = status.isAfk ? '😴' : '💻';
        statusBarItem.text = `${icon} ${formatTime(status.activeTime)}`;
        statusBarItem.tooltip = `Ativo: ${formatTime(status.activeTime)} | AFK: ${formatTime(status.afkTime)}`;
        statusBarItem.show();
    }, 1000);
}

export function deactivate() {
    if (tracker) {
        tracker.stop();
    }
}

function formatTime(seconds: number): string {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    } else if (minutes > 0) {
        return `${minutes}m ${secs}s`;
    }
    return `${secs}s`;
}
