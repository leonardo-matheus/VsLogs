# 🚀 Activity Tracker

<p align="center">
  <img src="vscode-activity-tracker/images/icon.png" alt="Activity Tracker Logo" width="128" height="128">
</p>

<p align="center">
  <strong>Complete developer productivity tracking solution</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/VS%20Code-Extension-blue?style=flat-square&logo=visual-studio-code" alt="VS Code">
  <img src="https://img.shields.io/badge/PHP-8.3-purple?style=flat-square&logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/SQLite-Database-green?style=flat-square&logo=sqlite" alt="SQLite">
  <img src="https://img.shields.io/badge/License-MIT-yellow?style=flat-square" alt="License">
</p>

---

## 📋 Overview

Activity Tracker is a complete solution to monitor your coding productivity:

- **VS Code Extension** - Tracks your activity directly in the editor
- **PHP API** - Backend to store and process data
- **Dashboard** - Beautiful real-time visualization

## ✨ Features

| Feature | Description |
|---------|-------------|
| ⏱️ **Active Time** | Track time spent coding |
| 😴 **AFK Detection** | Detect idle periods (5+ min) |
| 📝 **Lines Typed** | Count lines of code per project |
| 🎨 **Languages** | Track programming language usage |
| 📊 **Dashboard** | Real-time charts and statistics |
| 📈 **History** | Daily, weekly, and monthly reports |
| ⏰ **Hourly Stats** | Activity distribution throughout the day |

## 🏗️ Project Structure

```
activity-tracker/
├── vscode-activity-tracker/    # VS Code Extension
│   ├── src/                    # TypeScript source
│   ├── images/                 # Extension assets
│   ├── package.json            # Extension manifest
│   └── README.md               # Extension docs
│
├── api/                        # PHP Backend
│   ├── api/                    # API endpoints
│   ├── data/                   # SQLite database
│   ├── dashboard.html          # Web dashboard
│   ├── database.php            # Database connection
│   ├── router.php              # HTTP router
│   └── README.md               # API docs
│
└── README.md                   # This file
```

## 🚀 Quick Start

### 1. Install the Extension

```bash
cd vscode-activity-tracker
npm install
npm run compile
vsce package
code --install-extension vscode-activity-tracker-1.0.0.vsix
```

### 2. Start the API

```bash
cd api
php -S localhost:8000 router.php
```

### 3. View Dashboard

Open http://localhost:8000/dashboard.html

## 📸 Screenshots

### Dashboard
![Dashboard](screenshots/dashboard.png)

### Status Bar
The extension shows your coding status in the VS Code status bar:
- 💻 Active: Currently coding
- 😴 AFK: Idle for 5+ minutes

## ⚙️ Configuration

### Extension Settings

```json
{
  "activityTracker.apiEndpoint": "http://localhost:8000/api",
  "activityTracker.afkTimeout": 300,
  "activityTracker.syncInterval": 30
}
```

## 🔧 Requirements

- **VS Code** 1.80.0 or higher
- **PHP** 8.0 or higher with SQLite extension
- **Node.js** 16+ (for building extension)

## 📄 License

MIT License - see [LICENSE](LICENSE) for details.

---

<p align="center">
  Made with ❤️ for developers who care about productivity
</p>
