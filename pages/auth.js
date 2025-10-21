/**
 * auth.js
 * Contains shared Vue logic for authentication status and core navigation.
 */
const authMixin = {
    data() {
        return {
            isLoggedIn: false,
            userName: '',
            isBusinessPartner: false,
            unreadCount: 3, // Demo count for notifications
        };
    },
    mounted() {
        this.checkLoginStatus();
    },
    methods: {
        checkLoginStatus() {
            const user = JSON.parse(sessionStorage.getItem('currentUser') || 'null');
            if (user && user.name) {
                this.isLoggedIn = true;
                this.userName = user.name;
                // Business partners have a non-null businessId
                this.isBusinessPartner = !!user.businessId; 
            }
        },
        goToNotifications() {
            // Redirects to the notifications tab of the user dashboard
            window.location.href = 'user_account.html#notifications';
        },
        logout() {
            sessionStorage.removeItem('currentUser');
            this.isLoggedIn = false;
            this.userName = '';
            this.isBusinessPartner = false;
            window.location.href = 'index.html';
        }
    }
};