<?php
require_once 'includes/security.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BioSight AI | Biomedical Image Interpreter (Research Use)</title>
    <meta name="description"
        content="An advanced biomedical image interpreter using Gemini 1.5 Pro to provide supportive, research-based insights for X-rays and microscopic images.">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2E7D32">
    <link rel="apple-touch-icon" href="assets/img/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('Service Worker Registered'))
                    .catch(err => console.log('Service Worker Registration Failed', err));
            });
        }
    </script>
</head>

<body>
    <div class="blur-background"></div>

    <header>
        <div class="logo">BIOSIGHT AI</div>
        <nav style="display: flex; align-items: center; gap: 1rem;">
            <div id="connectivityStatus" class="status-badge online">
                <span class="dot"></span> Online
            </div>
            <button class="btn-ghost" id="demoToggle">Demo Mode: Off</button>
            <button class="btn-ghost" id="helpBtn">How it works</button>
        </nav>
    </header>

    <!-- Help Modal -->
    <div id="helpModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>BioSight AI: Tutorial & Ethics</h2>
            <div class="tutorial-grid">
                <div class="step">
                    <div class="step-num">1</div>
                    <h3>Upload</h3>
                    <p>Drop an X-ray or microscopic slide. Metadata is automatically stripped for privacy.</p>
                </div>
                <div class="step">
                    <div class="step-num">2</div>
                    <h3>Analyze</h3>
                    <p>Gemini 1.5 extracts visual patterns, textures and density markers in plain language.</p>
                </div>
                <div class="step">
                    <div class="step-num">3</div>
                    <h3>Review</h3>
                    <p>Examine confidence scores and research links. Download as a professional PDF report.</p>
                </div>
            </div>
            <div class="modal-footer">
                <p><strong>⚠️ Ethical Reminder:</strong> This tool performs visual feature extraction only. It does not
                    diagnose medical conditions. All findings must be validated by a clinical professional.</p>
            </div>
        </div>
    </div>

    <!-- Mandatory Consent Modal -->
    <div id="consentModal" class="modal" style="display: block;">
        <div class="modal-content">
            <h2 style="color: #C53030; margin-bottom: 1.5rem;">Legal Consent & Non-Diagnostic Agreement</h2>
            <div
                style="background: #F8F9FA; padding: 2rem; border-radius: 12px; max-height: 400px; overflow-y: auto; margin-bottom: 2rem; font-size: 0.95rem; line-height: 1.6; border: 1px solid var(--glass-border);">
                <p><strong>BioSight AI (Version 1.0.0-PROD)</strong></p>
                <p>By using this platform, you acknowledge and agree to the following terms:</p>
                <ul style="margin-top: 1rem; padding-left: 1.5rem;">
                    <li><strong>Non-Diagnostic Use:</strong> This system is a research-focused pattern recognition tool.
                        It is NOT a medical device and should NOT be used to diagnose, treat, or prevent any disease.
                    </li>
                    <li><strong>Research Support Only:</strong> Visual characteristics identified are based on AI
                        multimodal inference and are intended for secondary academic reference only.</li>
                    <li><strong>No Doctor-Patient Relationship:</strong> Use of this software does not constitute
                        medical advice or establish a patient relationship.</li>
                    <li><strong>Data Privacy:</strong> You agree that all uploaded images will have PII removed and that
                        data will be stored transiently.</li>
                </ul>
                <p style="margin-top: 1.5rem;"><strong>Liability Release:</strong> You release BioSight AI and its
                    developers from any liability arising from clinical decisions made based on this output.</p>
            </div>
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button class="btn-primary" id="acceptConsentBtn">I Accept & Wish to Proceed</button>
            </div>
        </div>
    </div>

    <main class="main-container">
        <div class="disclaimer-banner">
            <strong>⚠️ For Research & Educational Use Only.</strong> This tool is NOT for clinical diagnosis or
            treatment decisions.
        </div>

        <section class="main-layout">
            <div class="content-left">
                <section class="sample-bench" style="margin-bottom: 3rem;">
                    <h4
                        style="color: var(--secondary-color); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1rem;">
                        Test Bench: Rapid Samples</h4>
                    <div style="display: flex; gap: 1rem;">
                        <div class="sample-card" onclick="loadSample('xray')"
                            style="flex: 1; background: #E8F5E9; border: 1px solid #C8E6C9; padding: 1rem; border-radius: 12px; cursor: pointer; display: flex; align-items: center; gap: 0.75rem;">
                            <img src="assets/img/sample-xray.jpg"
                                style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                            <div>
                                <strong style="display: block; font-size: 0.875rem;">Chest X-Ray</strong>
                                <small style="color: var(--secondary-color);">Radiology Logic</small>
                            </div>
                        </div>
                        <div class="sample-card" onclick="loadSample('micro')"
                            style="flex: 1; background: #F1F8E9; border: 1px solid #DCEDC8; padding: 1rem; border-radius: 12px; cursor: pointer; display: flex; align-items: center; gap: 0.75rem;">
                            <img src="assets/img/sample-micro.jpg"
                                style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                            <div>
                                <strong style="display: block; font-size: 0.875rem;">Cell Culture</strong>
                                <small style="color: var(--secondary-color);">Histology Logic</small>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="upload-area">
                    <form id="uploadForm">
                        <div class="upload-card" id="dropZone" role="button" aria-label="Upload biomedical specimen"
                            tabindex="0">
                            <!-- Mobile-friendly camera input + File input -->
                            <input type="file" id="imageInput" accept="image/png, image/jpeg, image/webp"
                                style="display: none;">
                            <input type="file" id="cameraInput" accept="image/*" capture="environment"
                                style="display: none;">

                            <div id="uploadPlaceholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    style="color: var(--secondary-color); margin-bottom: 1.5rem;">
                                    <path
                                        d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z">
                                    </path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                                <h3>Ingest Specimen</h3>
                                <p>Drag and drop, or use your camera</p>
                                <div style="display: flex; gap: 0.5rem; justify-content: center; margin-top: 1.5rem;">
                                    <button type="button" class="btn-primary"
                                        onclick="document.getElementById('imageInput').click()">Select File</button>
                                    <button type="button" class="btn-ghost"
                                        onclick="document.getElementById('cameraInput').click()">Capture Phone</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </section>

                <section class="results-container" id="resultsSection" style="display: none;">
                    <div class="result-card">
                        <h3>Specimen Preview</h3>
                        <div class="image-container">
                            <div id="scannerLine" class="scanner-line"></div>
                            <img id="previewImg" class="image-preview" src="" alt="Specimen Preview">
                        </div>
                    </div>
                    <div class="result-card interpretation-text">
                        <h3>Visual Observations</h3>
                        <div id="aiResponse">
                            <p>Processing analysis...</p>
                            <!-- AI response will be injected here -->
                        </div>
                        <div id="reportActions" style="margin-top: 1.5rem; display: none;">
                            <button class="btn-primary" onclick="window.print()">Download PDF Report</button>
                        </div>
                        <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--glass-border);">
                            <small style="color: var(--secondary-color);">* These observations are generated by an AI
                                model. They must
                                be
                                validated by a qualified medical professional before any clinical use.</small>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="history-sidebar">
                <h3>Session History</h3>
                <div id="historyList">
                    <p style="color: var(--secondary-color); font-size: 0.875rem;">No recent analysis in this session.
                    </p>
                </div>
            </aside>
        </section>
    </main>

    <footer class="site-footer">
        <p>Developed for the 2026 Gemini 3 Hackathon</p>
        <p>built by christopher mumba</p>
        <p style="font-size: 0.75rem; color: var(--secondary-color); margin-top: 0.5rem;">Built with Gemini 1.5 Pro &
            Native Web
            Technologies</p>
    </footer>

    <script src="assets/js/offline.js"></script>
    <script src="assets/js/app.js"></script>
</body>

</html>