/**
 * BioSight AI: Offline Sync Engine (IndexedDB)
 */

const DB_NAME = 'BioSightOfflineDB';
const STORE_NAME = 'pendingAnalysis';
const DB_VERSION = 1;

/**
 * Initialize IndexedDB
 */
function initDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
            }
        };

        request.onsuccess = (event) => resolve(event.target.result);
        request.onerror = (event) => reject('IndexedDB error: ' + event.target.errorCode);
    });
}

/**
 * Queue a specimen for offline analysis
 */
async function queueSpecimen(file) {
    const db = await initDB();
    const transaction = db.transaction([STORE_NAME], 'readwrite');
    const store = transaction.objectStore(STORE_NAME);
    
    // Store as blob for persistence
    const entry = {
        file: file,
        filename: file.name,
        type: file.type,
        timestamp: new Date().toISOString()
    };

    return new Promise((resolve, reject) => {
        const request = store.add(entry);
        request.onsuccess = () => resolve();
        request.onerror = () => reject();
    });
}

/**
 * Get all pending specimens
 */
async function getPendingSpecimens() {
    const db = await initDB();
    const transaction = db.transaction([STORE_NAME], 'readonly');
    const store = transaction.objectStore(STORE_NAME);
    
    return new Promise((resolve) => {
        const request = store.getAll();
        request.onsuccess = () => resolve(request.result);
    });
}

/**
 * Remove a specimen from queue after successful sync
 */
async function clearQueuedItem(id) {
    const db = await initDB();
    const transaction = db.transaction([STORE_NAME], 'readwrite');
    const store = transaction.objectStore(STORE_NAME);
    store.delete(id);
}
