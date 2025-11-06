/**
 * Notification Mixin
 * Provides notification functionality for Vue components
 */

export const notificationMixin = {
    data() {
        return {
            notifications: [],
            unreadCount: 0,
            notificationPermission: 'default',
            notificationCheckInterval: null
        };
    },
    async mounted() {
        // Request notification permission
        if ('Notification' in window) {
            this.notificationPermission = Notification.permission;
            if (Notification.permission === 'default') {
                // Don't request immediately, let user click bell first
            }
        }
        
        // Check for notifications if user is logged in
        if (this.isLoggedIn) {
            await this.checkNotifications();
            await this.checkUpcomingEvents();
            
            // Check for notifications every 5 minutes
            this.notificationCheckInterval = setInterval(async () => {
                if (this.isLoggedIn) {
                    await this.checkUpcomingEvents();
                    await this.checkNotifications();
                }
            }, 5 * 60 * 1000); // 5 minutes
        }
    },
    beforeUnmount() {
        if (this.notificationCheckInterval) {
            clearInterval(this.notificationCheckInterval);
        }
    },
    methods: {
        async requestNotificationPermission() {
            if ('Notification' in window && Notification.permission === 'default') {
                const permission = await Notification.requestPermission();
                this.notificationPermission = permission;
                return permission === 'granted';
            }
            return Notification.permission === 'granted';
        },
        
        async checkUpcomingEvents() {
            if (!this.isLoggedIn) return;
            
            try {
                const response = await fetch('api/notifications.php?action=check_upcoming_events', {
                    method: 'GET',
                    credentials: 'include'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.notifications_created > 0) {
                        await this.checkNotifications();
                    }
                }
            } catch (error) {
                console.error('Error checking upcoming events:', error);
            }
        },
        
        async checkNotifications() {
            if (!this.isLoggedIn) return;
            
            try {
                const response = await fetch('api/notifications.php?action=get_notifications', {
                    method: 'GET',
                    credentials: 'include'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        this.notifications = data.notifications || [];
                        this.unreadCount = data.unread_count || 0;
                        
                        if (this.notificationPermission === 'granted' && this.unreadCount > 0) {
                            const unreadNotifications = this.notifications.filter(n => !n.is_read);
                            unreadNotifications.forEach(notification => {
                                this.showBrowserNotification(notification);
                            });
                        }
                    }
                }
            } catch (error) {
                console.error('Error fetching notifications:', error);
            }
        },
        
        showBrowserNotification(notification) {
            if ('Notification' in window && Notification.permission === 'granted') {
                const notificationObj = new Notification(notification.title, {
                    body: notification.message,
                    icon: '/favicon.ico', // You can add a favicon
                    badge: '/favicon.ico',
                    tag: `event-${notification.event_id}`, 
                    requireInteraction: false
                });
                
                notificationObj.onclick = () => {
                    window.focus();
                    window.location.href = 'event.html';
                    notificationObj.close();
                };
                
                setTimeout(() => {
                    notificationObj.close();
                }, 5000);
            }
        },
        
        async markNotificationRead(notificationId) {
            try {
                const response = await fetch('api/notifications.php?action=mark_read', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        notification_id: notificationId
                    })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        const notification = this.notifications.find(n => n.id === notificationId);
                        if (notification) {
                            notification.is_read = true;
                            this.unreadCount = Math.max(0, this.unreadCount - 1);
                        }
                    }
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        },
        
        async markAllNotificationsRead() {
            try {
                const response = await fetch('api/notifications.php?action=mark_read', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({})
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        this.notifications.forEach(n => n.is_read = true);
                        this.unreadCount = 0;
                    }
                }
            } catch (error) {
                console.error('Error marking all notifications as read:', error);
            }
        },
        
        formatNotificationTime(createdAt) {
            const date = new Date(createdAt);
            const now = new Date();
            const diff = now - date;
            
            const seconds = Math.floor(diff / 1000);
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);
            const days = Math.floor(hours / 24);
            
            if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`;
            if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            if (minutes > 0) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
            return 'Just now';
        }
    }
};

