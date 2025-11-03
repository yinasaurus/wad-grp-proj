/**
 * Firebase configuration and initialization
 * Shared across all pages for consistency
 * 
 * Note: Firebase keys are public by design (client-side), but we load them
 * from a config endpoint for better security practices and easier management.
 */

import { initializeApp } from "https://www.gstatic.com/firebasejs/12.5.0/firebase-app.js";
import { getAuth } from "https://www.gstatic.com/firebasejs/12.5.0/firebase-auth.js";
import { getFirestore } from "https://www.gstatic.com/firebasejs/12.5.0/firebase-firestore.js";

// Firebase config - will be loaded from API endpoint
let firebaseConfig = null;
let app = null;
let auth = null;
let db = null;
let firebaseInitialized = false;

/**
 * Initialize Firebase with config from server
 */
async function initFirebase() {
    if (firebaseInitialized) {
        return { app, auth, db };
    }
    
    try {
        // Load config from secure endpoint
        const response = await fetch('api/config.php');
        const config = await response.json();
        
        if (!config.firebase || !config.firebase.apiKey) {
            throw new Error('Firebase configuration not found');
        }
        
        firebaseConfig = {
            apiKey: config.firebase.apiKey,
            authDomain: config.firebase.authDomain,
            projectId: config.firebase.projectId,
            storageBucket: config.firebase.storageBucket,
            messagingSenderId: config.firebase.messagingSenderId,
            appId: config.firebase.appId,
            measurementId: config.firebase.measurementId
        };
        
        // Initialize Firebase
        app = initializeApp(firebaseConfig);
        auth = getAuth(app);
        db = getFirestore(app);
        
        firebaseInitialized = true;
        console.log('Firebase initialized');
        
        return { app, auth, db };
    } catch (error) {
        console.error('Failed to initialize Firebase:', error);
        throw error;
    }
}

// Initialize Firebase immediately
const firebasePromise = initFirebase();

// Export promise-based initialization for async use
export { firebasePromise, initFirebase };

// Export getters for backwards compatibility
// These will wait for initialization if needed
export function getApp() {
    if (!firebaseInitialized) {
        throw new Error('Firebase not initialized yet. Use firebasePromise or await initFirebase() first.');
    }
    return app;
}

export function getAuthInstance() {
    if (!firebaseInitialized) {
        throw new Error('Firebase not initialized yet. Use firebasePromise or await initFirebase() first.');
    }
    return auth;
}

export function getDbInstance() {
    if (!firebaseInitialized) {
        throw new Error('Firebase not initialized yet. Use firebasePromise or await initFirebase() first.');
    }
    return db;
}

// For backwards compatibility - will be set after init
firebasePromise.then(() => {
    // These are set by initFirebase, but we export them for direct access
    // Note: This works because ES modules bindings are live references
});

// Export for direct use (will be undefined until initialized, but modules can wait)
export { app, auth, db };