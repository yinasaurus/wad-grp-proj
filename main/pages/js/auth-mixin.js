/**
 * Shared authentication logic for all pages
 * Uses Firebase Authentication with PHP session bridge
 */
import { firebasePromise, auth, db } from './firebase-config.js';
import { onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/12.5.0/firebase-auth.js";
import { doc, getDoc } from "https://www.gstatic.com/firebasejs/12.5.0/firebase-firestore.js";

export const authMixin = {
    data() {
        return {
            isLoggedIn: false,
            userName: '',
            userEmail: '',
            userType: '',
            isBusinessPartner: false,
            userId: null,
            businessId: null,
            authInitialized: false // Track if auth has finished initializing
        };
    },
    async mounted() {
        // Wait for Firebase to initialize
        await firebasePromise;
        
        // Check sessionStorage first for immediate state
        const currentUserStr = sessionStorage.getItem('currentUser');
        if (currentUserStr) {
            try {
                const currentUser = JSON.parse(currentUserStr);
                if (currentUser && currentUser.name) {
                    this.isLoggedIn = true;
                    this.userName = currentUser.name;
                    this.userEmail = currentUser.email || '';
                    this.userType = currentUser.userType || '';
                    this.isBusinessPartner = currentUser.userType === 'business';
                    this.userId = currentUser.userId || null;
                    this.businessId = currentUser.businessId || null;
                }
            } catch (e) {
                console.error('Error parsing sessionStorage user:', e);
            }
        }
        
        // Then initialize full auth check
        this.initAuth();
    },
    methods: {
        async initAuth() {
            // Ensure Firebase is initialized
            await firebasePromise;
            
            onAuthStateChanged(auth, async (user) => {
                if (user) {
                    this.isLoggedIn = true;
                    this.userEmail = user.email;
                    this.authInitialized = true;

                    // Establish PHP session using Firebase ID token
                    try {
                        const idToken = await user.getIdToken();
                        const response = await fetch('api/bridge.php?action=session_login', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'include',
                            body: JSON.stringify({ idToken })
                        });
                        const userData = await response.json();

                        if (userData.success) {
                            this.userName = userData.user.name || (user.displayName || user.email.split('@')[0]);
                            this.userType = userData.user.userType || 'user';
                            this.isBusinessPartner = this.userType === 'business';
                            this.userId = userData.user.userId || null;
                            this.businessId = userData.business?.businessId || null;
                            
                            // Store minimal info in sessionStorage for legacy compatibility
                            const currentUser = {
                                name: this.userName,
                                email: this.userEmail,
                                userType: this.userType,
                                userId: this.userId,
                                businessId: this.businessId
                            };
                            sessionStorage.setItem('currentUser', JSON.stringify(currentUser));
                        } else {
                            // Fallback: try to get user info from Firestore
                            this.userName = user.displayName || user.email.split('@')[0];
                            try {
                                let userDoc = await getDoc(doc(db, "businesses", user.uid));
                                if (userDoc.exists()) {
                                    const data = userDoc.data();
                                    this.userType = 'business';
                                    this.isBusinessPartner = true;
                                    this.userName = data.name || data.businessName || this.userName;
                                    this.businessId = userDoc.id;
                                } else {
                                    userDoc = await getDoc(doc(db, "users", user.uid));
                                    if (userDoc.exists()) {
                                        const data = userDoc.data();
                                        this.userType = data.userType || 'user';
                                        this.userName = data.name || this.userName;
                                    }
                                }
                            } catch (e) {
                                console.error('Error getting user from Firestore:', e);
                            }
                        }
                    } catch (error) {
                        console.error('Error establishing session:', error);
                        this.userName = user.displayName || user.email.split('@')[0];
                        // Try to get user info from Firestore as fallback
                        try {
                            let userDoc = await getDoc(doc(db, "businesses", user.uid));
                            if (userDoc.exists()) {
                                const data = userDoc.data();
                                this.userType = 'business';
                                this.isBusinessPartner = true;
                                this.userName = data.name || data.businessName || this.userName;
                            } else {
                                userDoc = await getDoc(doc(db, "users", user.uid));
                                if (userDoc.exists()) {
                                    const data = userDoc.data();
                                    this.userType = data.userType || 'user';
                                    this.userName = data.name || this.userName;
                                }
                            }
                        } catch (e) {
                            console.error('Error getting user from Firestore:', e);
                        }
                    }
                } else {
                    this.isLoggedIn = false;
                    this.userName = '';
                    this.userEmail = '';
                    this.userType = '';
                    this.isBusinessPartner = false;
                    this.userId = null;
                    this.businessId = null;
                    this.authInitialized = true;
                    sessionStorage.removeItem('currentUser');
                }
            });
        },
        async logout() {
            try {
                // Clear PHP session
                await fetch('api/bridge.php?action=logout', {
                    method: 'POST',
                    credentials: 'include'
                });
                
                await signOut(auth);
                this.isLoggedIn = false;
                this.userName = '';
                this.userId = null;
                this.businessId = null;
                sessionStorage.removeItem('currentUser');
                
                // Redirect based on user type
                if (this.userType === 'business') {
                    window.location.href = 'login_business.html';
                } else {
                window.location.href = 'index.html';
                }
            } catch (error) {
                console.error('Logout error:', error);
                // Still clear local state
                this.isLoggedIn = false;
                this.userName = '';
                sessionStorage.removeItem('currentUser');
                window.location.href = 'index.html';
            }
        }
    }
};