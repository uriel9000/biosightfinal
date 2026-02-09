# 🩺 BioSight AI — Intelligent Biomedical Interpreter

**BioSight AI** is a research tool utilizing **Gemini 1.5 Flash** to extract visual features from biomedical imagery (X-rays, slides) for educational and preliminary triage support.

### 🤖 Gemini 1.5 Integration

Gemini 1.5 Flash serves as the multimodal reasoning engine. It processes raw visual data to identify morphological patterns, structural anomalies, and density variations. The implementation uses a **Prompt-to-Observation** pipeline:

1. **Input**: Sanitized image bytes.
2. **Reasoning**: Gemini extracts visual markers based on medical vision prompts.
3. **Output**: Structured JSON containing observational summaries and analyzer confidence scores.
   _Note: The system identifies visual patterns only and does not provide clinical diagnoses._

### 🚀 Key Features

- **Gemini Multimodal Vision**: High-precision feature extraction.
- **Privacy-First**: No PII accepted; AES-256 encrypted storage.
- **Offline Resilience**: IndexedDB vault for specimen queuing.
- **Judge Dashboard**: Rapid endpoints for health and demo status.

### ⚙️ Quick Start

1. **Env**: Set `GEMINI_API_KEY` and `APP_ENCRYPTION_KEY` in `.env`.
2. **Database**: Import `sql/schema.sql`.
3. **Python**: `pip install -r api/requirements.txt`
4. **Run**: `python api/main.py`

_Developed for the 2026 GEMINI 3 Hackathon._
