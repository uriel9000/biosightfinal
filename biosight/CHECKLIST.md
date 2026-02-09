# 🚀 BioSight AI: Production Readiness Checklist (Phase 11)

This document outlines the final validation steps required before the official production launch.

---

## 🧪 1. Functional Validation

- [ ] **End-to-End Analysis**: Verify image upload (file & camera) -> Python processing -> JSON parsing -> UI rendering.
- [ ] **PWA Installability**: Verify manifest.json is detected and "Add to Home Screen" prompt appears on Android/iOS.
- [ ] **Offline Resilience**: Disable network, upload image, verify it persists in IndexedDB, enable network, verify auto-sync.
- [ ] **History & Decryption**: Verify that previous analyses can be reloaded and are correctly decrypted from the database.
- [ ] **PDF Generation**: Verify print layout on mobile and desktop; ensure the legal footer and logo appear correctly.

## 🛡️ 2. Security & Privacy Audit

- [ ] **Consent Gate**: Confirm that `/api/process.php` and `/api/history.php` return 403/Fail if consent session is missing.
- [ ] **Encryption Check**: Inspect `analysis_logs` table manually to ensure `interpretation_blob` is unreadable binary.
- [ ] **PII Removal**: Confirm EXIF data is stripped from images in the `uploads/` directory after Python processing.
- [ ] **CSP Headers**: Verify via Browser DevTools that Content Security Policy is active and blocking unauthorized domains.
- [ ] **Rate Limiting**: Attempt 5 rapid uploads to confirm the 10-second cooldown triggers a 429 response.

## ⚡ 3. Performance Benchmarks

- [ ] **Lighthouse Score**: Aim for 90+ in PWA, Accessibility, and Best Practices.
- [ ] **Inference Latency**: Confirm Gemini 1.5 Flash returns interpretations in under 8 seconds for average specimens.
- [ ] **Asset Size**: Ensure `style.css` and `app.js` are minified for production (Current: ~15KB combined).
- [ ] **Database Cleanup**: Verify MySQL Event Scheduler is `ON` and purges test data hourly.

## ⚖️ 4. Ethical & UX Validation

- [ ] **Plain Language Review**: Verify the "Education Hub" terminology bridge is explainable to non-medical users.
- [ ] **Red List Verification**: Audit 10 AI responses to ensure ZERO diagnostic words (fracture, cancer, etc.) are present.
- [ ] **Visual Cues**: Ensure confidence bars use the correct color logic (High=Green, Low=Amber).
- [ ] **Disclaimer Visibility**: Ensure the persistent header disclaimer is visible on all screen sizes.

## 🛠️ 5. Deployment & Rollback Plan

- [ ] **Environment Sync**: Ensure production `.env` has a unique 32-character `APP_ENCRYPTION_KEY`.
- [ ] **Database Migration**: Securely run `sql/schema.sql` on the production MySQL instance.
- [ ] **Rollback Strategy**:
  - **Code**: Maintain the previous stable git commit tag; use `git checkout [tag]` for instant revert.
  - **Database**: Perform a binary backup before schema changes.
  - **AI**: Maintain a fallback "Version 1.0" prompt if the new production prompt causes hallucinations.

---

**Status:** ⬜ Pending Approval | ✅ Ready for Launch
