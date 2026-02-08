# 🩺 BioSight AI — Intelligent Biomedical Image Interpreter

This repository contains original code authored by christopher and is licensed under the MIT License.

This project uses the Gemini 3 API, which is governed by Google’s terms of service.
The license applies only to the application code and not to third-party services or AI model outputs.

**Contest Phase: 0 — Contest-Compliant Project Definition**

**BioSight AI** is a premium, research-focused web application designed to assist biomedical researchers and students in analyzing complex imagery (X-rays, microscopic slides) using the power of **Gemini 1.5 Multimodal AI**.

## 🏆 Contest Compliance & Project Scope

This project was conceived and developed entirely within the contest window.

### 🎯 Scope Matrix

| Feature                | Status          | Specification                                                           |
| :--------------------- | :-------------- | :---------------------------------------------------------------------- |
| **Biomedical Vision**  | ✅ In-Scope     | Visual pattern extraction, texture density, and morphological analysis. |
| **Gemini Integration** | ✅ In-Scope     | Real-time multimodal interpretation (Gemini 1.5).                       |
| **Offline Resilience** | ✅ In-Scope     | IndexedDB "Vault" for specimen queuing.                                 |
| **Diagnosis**          | ❌ Out-of-Scope | **MANDATORY:** The system never claims to diagnose or treat disease.    |
| **Clinical Decision**  | ❌ Out-of-Scope | No treatment, dosage, or surgical recommendations.                      |

### 🛡️ Medical Safety Boundaries

- **Zero-Diagnosis Protocol**: forbidden from utilizing clinical diagnostic terminology.
- **Structural Language**: Findings are restricted to observational characteristics (e.g., _"Structural Discontinuity"_ vs _"Fracture"_).
- **Human-in-the-Loop**: Positioned as a **Research Assistant Tool**, requiring validation by board-certified professionals.

### 🔐 Privacy-First Data Rules

- **Zero-PII Storage**: No Name, DOB, or Patient IDs are accepted/stored.
- **Metadata Stripping**: Python backend sanitizes EXIF/DICOM data before AI processing.
- **Transient Residency (TTL)**: Automatic 24-hour expiration of research records using MySQL event scheduler.
- **Encryption-at-Rest**: interpretations are AES-256 encrypted at the application layer.

## 🚀 Key Features

- **Multimodal AI Analysis**: Leverages Google Gemini 1.5 to perform high-precision visual feature extraction.
- **Offline-First Resilience**: Includes a live connectivity monitor and a deferred IndexedDB upload queue.
- **Professional Reporting**: One-click "Download PDF" with specialized academic print templates.
- **Education Hub**: Terminologies bridge translated technical findings for non-medical users.

## ⚙️ Installation & Setup

1. **Requirements**: PHP 8.x, MySQL (WAMP/XAMPP), Python 3.10+.
2. **Env**: Setup `.env` with `GEMINI_API_KEY` and `APP_ENCRYPTION_KEY` (32 chars).
3. **Database**: Import `sql/schema.sql`.
4. **Python**: Run `pip install -r api/requirements.txt`.
5. **Start**: Navigate to the directory in your local server.

_Developed for the 2026 GEMINI 3 Hackathon._
