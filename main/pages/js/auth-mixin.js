/**
 * Shared authentication logic for all pages
 * Uses MySQL-based PHP sessions instead of Firebase
 */
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
        // Check session storage first for immediate state
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
            try {
                // Check PHP session status using MySQL-based authentication
                const resp = await fetch('api/auth.php?action=check', {
                    method: 'GET',
                    credentials: 'include'
                });
                
                if (!resp.ok) {
                    throw new Error('Failed to check session status');
                }
                
                const data = await resp.json();
                
                if (data.loggedIn) {
                    this.isLoggedIn = true;
                    this.userType = data.userType || 'consumer';
                    this.userId = data.userId || null;
                    this.businessId = data.businessId || null;
                    this.isBusinessPartner = this.userType === 'business';
                    
                    // Get full user info from MySQL
                    const userResp = await fetch('api/bridge.php?action=get_user', {
                        method: 'GET',
                        credentials: 'include'
                    });
                    
                    if (userResp.ok) {
                        const userData = await userResp.json();
                        if (userData.success) {
                            this.userName = userData.user.name || userData.user.email?.split('@')[0] || 'User';
                            this.userEmail = userData.user.email || '';
                            this.userType = userData.user.userType || this.userType;
                            this.isBusinessPartner = this.userType === 'business';
                            
                            // Store in sessionStorage for quick access
                            const currentUser = {
                                name: this.userName,
                                email: this.userEmail,
                                userType: this.userType,
                                userId: this.userId,
                                businessId: this.businessId
                            };
                            sessionStorage.setItem('currentUser', JSON.stringify(currentUser));
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
                    sessionStorage.removeItem('currentUser');
                }
                
                this.authInitialized = true;
            } catch (error) {
                console.error('Error checking auth:', error);
                this.authInitialized = true;
            }
        },
        async logout() {
            try {
                // Clear PHP session (MySQL-based)
                await fetch('api/auth.php?action=logout', {
                    method: 'POST',
                    credentials: 'include'
                });
                
                this.isLoggedIn = false;
                this.userName = '';
                this.userEmail = '';
                this.userType = '';
                this.isBusinessPartner = false;
                this.userId = null;
                this.businessId = null;
                sessionStorage.removeItem('currentUser');
                
                // Reload page to clear all state
                window.location.reload();
            } catch (error) {
                console.error('Logout error:', error);
                window.location.reload();
            }
        }
    }
};

