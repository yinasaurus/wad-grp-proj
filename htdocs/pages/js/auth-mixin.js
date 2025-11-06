
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
            authInitialized: false 
        };
    },
    async mounted() {
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
        this.initAuth();
    },
    methods: {
        async initAuth() {
            try {
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
        handleLogoClick(event) {
            if (this.isBusinessPartner) {
                event.preventDefault();
                window.location.href = 'partner_dashboard.html';
            }
        },
        async logout() {
            try {
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
                
                window.location.reload();
            } catch (error) {
                console.error('Logout error:', error);
                window.location.reload();
            }
        }
    }
};

