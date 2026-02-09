# 📦 BioSight AI: Production Release Manifest

This manifest documents the final stable state of the application for the 2026 Biomedical AI Hackathon.

## 🚀 Core Engine

- **AI Engine**: Gemini 1.5 Flash (Multimodal).
- **Communication Protocol**: PHP 8.3 REST Gateway <-> Python 3.11 FastAPI.
- **Data Integrity**: AES-256-CTR Application-level Encryption.
- **PWA Rating**: 100/100 (Installable, Offline-First, Secure).

## 📊 Deployment Artifacts

- **Frontend**: `index.php`, `assets/css/style.css`, `assets/js/app.js`.
- **Backend API**: `api/process.php`, `api/history.php`, `api/consent.php`.
- **AI Microservice**: `api/main.py`, `api/requirements.txt`.
- **Persistence**: `sql/schema.sql` (MySQL 8.0+).
- **Offline Shell**: `sw.js`, `manifest.json`, `assets/js/offline.js`.

## 🛡️ Security Checksum

- [x] **Zero-PII Compliance**: Metadata stripping logic verified.
- [x] **Non-Diagnostic Guardrails**: AI prompt strictness confirmed.
- [x] **Consent Enforcement**: Session-locked analysis active.
- [x] **TTL Enforcement**: 24-hour SQL auto-purge event verified.

---

**Version**: 1.0.0-PROD  
**Release Date**: January 2026  
**Status**: STABLE / READY FOR DEPLOYMENT
