const dropZone = document.getElementById("dropZone");
const imageInput = document.getElementById("imageInput");
const previewImg = document.getElementById("previewImg");
const resultsSection = document.getElementById("resultsSection");
const aiResponse = document.getElementById("aiResponse");
const helpBtn = document.getElementById('helpBtn');
const closeModal = document.querySelector('.close-modal');
const connectivityStatus = document.getElementById('connectivityStatus');
const demoToggle = document.getElementById('demoToggle');
const consentModal = document.getElementById('consentModal');
const acceptConsentBtn = document.getElementById('acceptConsentBtn');

let isOnline = navigator.onLine;
let isDemoMode = false;
let offlineQueue = [];
let hasConsent = false;

// Check Consent Status on Load
async function checkConsent() {
    const res = await fetch('api/consent.php');
    const data = await res.json();
    if (data.accepted) {
        hasConsent = true;
        consentModal.style.display = 'none';
    } else {
        consentModal.style.display = 'block';
    }
}

acceptConsentBtn.onclick = async () => {
    const res = await fetch('api/consent.php', { method: 'POST' });
    const data = await res.json();
    if (data.success) {
        hasConsent = true;
        consentModal.style.display = 'none';
        loadHistory(); // Reload history once consent is given
    } else {
        alert("Failed to log consent. Please refresh.");
    }
};

checkConsent();

// Connectivity Monitoring
window.addEventListener('online', () => updateConnectivity(true));
window.addEventListener('offline', () => updateConnectivity(false));

function updateConnectivity(online) {
    isOnline = online;
    connectivityStatus.className = `status-badge ${online ? 'online' : 'offline'}`;
    connectivityStatus.innerHTML = `<span class="dot"></span> ${online ? 'Online' : 'Offline'}`;
    
    if (online && offlineQueue.length > 0) {
        processOfflineQueue();
    }
}

// Demo Mode Toggle
demoToggle.onclick = () => {
    isDemoMode = !isDemoMode;
    demoToggle.innerText = `Demo Mode: ${isDemoMode ? 'On' : 'Off'}`;
    demoToggle.style.color = isDemoMode ? 'var(--primary-color)' : 'var(--secondary-color)';
};

// Modal Logic
helpBtn.onclick = () => helpModal.style.display = 'block';
closeModal.onclick = () => helpModal.style.display = 'none';
window.onclick = (e) => { if (e.target == helpModal) helpModal.style.display = 'none'; };

// Handle Drag and Drop
dropZone.addEventListener("dragover", (e) => {
  e.preventDefault();
  dropZone.style.borderColor = "var(--secondary-color)";
});

dropZone.addEventListener("dragleave", () => {
  dropZone.style.borderColor = "var(--glass-border)";
});

dropZone.addEventListener("drop", (e) => {
  e.preventDefault();
  const files = e.dataTransfer.files;
  if (files.length > 0) {
    handleImage(files[0]);
  }
});

const cameraInput = document.getElementById('cameraInput');

// Mobile Camera Support
cameraInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) handleImage(e.target.files[0]);
});

// Drag and Drop Keyboard Support (Accessibility)
dropZone.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') imageInput.click();
});

imageInput.addEventListener("change", (e) => {
  if (e.target.files.length > 0) {
    handleImage(e.target.files[0]);
  }
});

async function handleImage(file) {
  if (!file.type.startsWith("image/")) {
    alert("Please upload an image file.");
    return;
  }

  // Preview
  const reader = new FileReader();
  reader.onload = (e) => {
    previewImg.src = e.target.result;
    resultsSection.style.display = "grid";
    resultsSection.scrollIntoView({ behavior: "smooth" });
  };
  reader.readAsDataURL(file);

  // 1. Check for Demo Mode / Offline
  if (!isOnline && !isDemoMode) {
    aiResponse.innerHTML = `
        <div class="offline-warning" style="color: #fca5a5; padding: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 12px; background: rgba(30, 41, 59, 0.5);">
            <div style="display: flex; gap: 1rem; align-items: flex-start;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <div>
                    <h4 style="margin: 0 0 0.5rem 0;">Connection Unavailable</h4>
                    <p style="font-size: 0.875rem; color: #94a3b8; margin: 0;">This specimen has been saved to your local laboratory vault. Analysis will begin automatically when we detect a signal.</p>
                </div>
            </div>
        </div>
    `;
    await queueSpecimen(file); // Persist to IndexedDB
    return;
  }

  if (isDemoMode) {
      document.getElementById('scannerLine').style.display = 'block';
      aiResponse.innerHTML = '<p><span class="loader-pulse"></span>Generating High-Fidelity Mock Analysis...</p>';
      setTimeout(() => {
          document.getElementById('scannerLine').style.display = 'none';
          const mockData = {
              summary: "VIRTUAL SPECIMEN: Uniform visual patterns identified with occasional structural variations in the peripheral zones.",
              data: {
                  specimen_type: "Demonstration",
                  analyzer_confidence: 0.94,
                  findings: [
                      {feature: "Pattern Symmetry", observation: "High degree of structural alignment.", confidence: 0.98, certainty: "High"},
                      {feature: "Opacity Gradient", observation: "Gradual shift from translucent to radiopaque.", confidence: 0.72, certainty: "Med"}
                  ]
              },
              education_hub: [
                  {term: "Radiopaque", plain_explanation: "Appearing bright or white on a scan, indicating higher density."},
                  {term: "Morphology", plain_explanation: "The physical shape and structure of cells or bones."}
              ],
              disclaimer: "DEMO MODE ONLY"
          };
          renderResponse(mockData);
      }, 1500);
      return;
  }

  // 2. Standard Online Processing
  const formData = new FormData();
  formData.append("image", file);

  document.getElementById('scannerLine').style.display = 'block';
  aiResponse.innerHTML = '<p><span class="loader-pulse"></span>Conducting AI pattern extraction... Please wait.</p>';

  console.log(`Processing image. isOnline: ${isOnline}, isDemoMode: ${isDemoMode}`);

  try {
    const response = await fetch("api/process.php", {
      method: "POST",
      body: formData,
    });

    const text = await response.text();
    let data;
    try {
        data = JSON.parse(text);
    } catch (e) {
        throw new Error("Invalid server response (Non-JSON)");
    }

    document.getElementById('scannerLine').style.display = 'none';

    if (data.success) {
      renderResponse(data.interpretation);
      loadHistory();
    } else {
      aiResponse.innerHTML = `<p style="color: #ef4444;">System Error: ${data.message}</p>`;
    }
  } catch (error) {
    console.error("HandleImage Error:", error);
    document.getElementById('scannerLine').style.display = 'none';
    aiResponse.innerHTML = `<p style="color: #f59e0b;">Network failure. Specimen saved to local vault.</p>
                            <small style="color: #94a3b8; display: block; margin-top: 0.5rem;">Technical details: ${error.message}</small>`;
    await queueSpecimen(file);
  }
}

async function processOfflineQueue() {
    const pending = await getPendingSpecimens();
    if (pending.length === 0) return;

    console.log(`Restored connection. Syncing ${pending.length} specimens...`);
    
    for (const item of pending) {
        // Re-process the queued item
        await handleImage(item.file);
        await clearQueuedItem(item.id);
    }
}

async function loadHistory() {
    try {
        const response = await fetch('api/history.php');
        const data = await response.json();
        
        if (data.success && data.history.length > 0) {
            const historyList = document.getElementById('historyList');
            historyList.innerHTML = '';
            
            data.history.forEach(item => {
                const div = document.createElement('div');
                div.className = 'history-item';
                div.innerHTML = `
                    <div class="date">${new Date(item.created_at).toLocaleString()}</div>
                    <div class="snippet">${item.interpretation.substring(0, 50)}...</div>
                `;
                div.onclick = () => {
                    renderResponse(item.interpretation);
                    previewImg.src = 'uploads/' + item.image_path.split('/').pop();
                    resultsSection.style.display = 'grid';
                    resultsSection.scrollIntoView({ behavior: 'smooth' });
                };
                historyList.appendChild(div);
            });
        }
    } catch (error) {
        console.error('Failed to load history:', error);
    }
}

function renderResponse(input) {
    try {
        const json = typeof input === 'string' ? JSON.parse(input) : input;
        const reportDate = new Date().toLocaleString();
        
        let html = `
            <div class="report-meta" style="margin-bottom: 2rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem;">
                <p style="font-size: 0.75rem; color: var(--secondary-color);">Report Date: ${reportDate}</p>
            </div>

            <div class="report-summary">
                <p>${json.summary}</p>
            </div>
            
            <div class="markers-grid" style="margin-top: 2rem;">
                <h4 style="color: var(--secondary-color); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 1.5rem;">
                    Visual Evidence Dashboard (Avg Confidence: ${(json.data.analyzer_confidence * 100).toFixed(0)}%)
                </h4>
        `;

        if (json.data.findings && json.data.findings.length > 0) {
            json.data.findings.forEach(marker => {
                const percentage = (marker.confidence * 100).toFixed(0);
                const color = marker.certainty === 'High' ? '#10b981' : (marker.certainty === 'Med' ? '#3b82f6' : '#f59e0b');
                
                html += `
                    <div class="marker-item" style="margin-bottom: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="font-weight: 600; color: var(--text-color);">${marker.feature}</span>
                            <span style="color: ${color}; font-weight: 800;">${percentage}%</span>
                        </div>
                        <div class="progress-bar-bg" style="background: rgba(255,255,255,0.05); height: 6px; border-radius: 4px; overflow: hidden;">
                            <div class="progress-bar-fill" style="width: ${percentage}%; background: ${color}; height: 100%; transition: width 1s ease-out;"></div>
                        </div>
                        <p style="font-size: 0.875rem; color: var(--secondary-color); margin-top: 0.5rem;">${marker.observation}</p>
                    </div>
                `;
            });
        }

        html += `</div>`;

        // Education Hub
        if (json.education_hub && json.education_hub.length > 0) {
            html += `
                <div class="education-hub" style="margin-top: 2rem; padding: 1.5rem; background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.1); border-radius: 16px;">
                    <h5 style="color: var(--secondary-color); margin-bottom: 1rem; font-size: 0.9rem;">Terminology Bridge (Educational)</h5>
                    <div style="display: grid; gap: 1rem;">
            `;
            json.education_hub.forEach(item => {
                html += `
                    <div>
                        <strong style="color: var(--text-color); font-size: 0.875rem;">${item.term}</strong>
                        <p style="font-size: 0.825rem; color: var(--secondary-color);">${item.plain_explanation}</p>
                    </div>
                `;
            });
            html += `</div></div>`;
        }

        // Production Print Footer
        html += `
            <div class="report-footer" style="margin-top: 3rem; display: none;">
                <p><strong>Disclaimer:</strong> This BioSight AI report is for research and educational purposes only. It does not constitute a clinical diagnosis. The visual markers identified must be reviewed and validated by a board-certified medical professional prior to any clinical application.</p>
                <p style="margin-top: 0.5rem; font-size: 0.75rem;">Session: ${Math.random().toString(36).substring(7).toUpperCase()} | Integrity Verified</p>
            </div>
        `;

        aiResponse.innerHTML = html;
        document.getElementById('reportActions').style.display = 'block';
        
    } catch (e) {
        console.error("Render Error:", e);
        aiResponse.innerHTML = `<p style="color: #ef4444;">Received valid AI data but failed to render UI components.</p>`;
    }
}

async function loadSample(type) {
    const url = type === 'xray' ? 'assets/img/sample-xray.jpg' : 'assets/img/sample-micro.jpg';
    
    // Fetch the image and convert to File object to reuse handleImage pipeline
    try {
        const res = await fetch(url);
        const blob = await res.blob();
        const file = new File([blob], `${type}_sample.jpg`, { type: "image/jpeg" });
        handleImage(file);
    } catch (e) {
        console.error("Failed to load sample:", e);
    }
}

// Initial load
loadHistory();
