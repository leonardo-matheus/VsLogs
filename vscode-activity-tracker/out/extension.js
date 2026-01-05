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
exports.activate = activate;
exports.deactivate = deactivate;
const vscode = __importStar(require("vscode"));
const tracker_1 = require("./tracker");
let tracker;
function activate(context) {
    console.log('Activity Tracker is now active!');
    tracker = new tracker_1.ActivityTracker(context);
    tracker.start();
    // Comando para mostrar status
    const showStatusCommand = vscode.commands.registerCommand('activityTracker.showStatus', () => {
        const status = tracker.getStatus();
        vscode.window.showInformationMessage(`⏱️ Tempo ativo: ${formatTime(status.activeTime)} | ` +
            `😴 Tempo AFK: ${formatTime(status.afkTime)} | ` +
            `Status: ${status.isAfk ? 'AFK' : 'Ativo'}`);
    });
    // Comando para abrir dashboard
    const openDashboardCommand = vscode.commands.registerCommand('activityTracker.openDashboard', () => {
        const config = vscode.workspace.getConfiguration('activityTracker');
        const endpoint = config.get('apiEndpoint', 'http://localhost:8080/api');
        const dashboardUrl = endpoint.replace('/api', '/dashboard.html');
        vscode.env.openExternal(vscode.Uri.parse(dashboardUrl));
    });
    context.subscriptions.push(showStatusCommand, openDashboardCommand);
    // Status bar
    const statusBarItem = vscode.window.createStatusBarItem(vscode.StatusBarAlignment.Right, 100);
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
function deactivate() {
    if (tracker) {
        tracker.stop();
    }
}
function formatTime(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }
    else if (minutes > 0) {
        return `${minutes}m ${secs}s`;
    }
    return `${secs}s`;
}
//# sourceMappingURL=extension.js.map