# Changelog

All notable changes to the "Activity Tracker" extension will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-01-05

### Added
- 🌐 Production server ready at https://191-235-32-212.nip.io/vslogs
- 📊 Dashboard available at /vslogs
- 🔗 API endpoints at /vslogs/api/

### Changed
- Default API endpoint now points to production server
- Improved server configuration for Nginx deployment

## [1.0.1] - 2026-01-05

### Changed
- 🌐 Default API endpoint updated to production server (https://191-235-32-212.nip.io/vslogs/api)
- 📍 API routes changed from /api/ to /vslogs/api/
- 📊 Dashboard route changed from /dashboard to /vslogs

## [1.0.0] - 2026-01-05

### Added
- ⏱️ Active time tracking - monitors coding activity
- 😴 AFK detection - identifies idle periods (5+ minutes)
- 📝 Lines typed counter - tracks code changes per project
- 🎨 Language tracking - monitors programming language usage
- 📊 Real-time dashboard integration
- 🔄 Automatic sync to backend API
- 💾 Persistent storage - data saved between sessions
- 📈 Hourly activity distribution
- 🎯 Status bar indicator with live updates

### Features
- Configurable AFK timeout (default: 5 minutes)
- Configurable sync interval (default: 30 seconds)
- Custom API endpoint configuration
- Multi-workspace support
- Daily data reset

## [Unreleased]

### Planned
- Weekly/monthly reports
- Goal setting
- Team statistics
- Export data to CSV
- Dark/light theme for dashboard
