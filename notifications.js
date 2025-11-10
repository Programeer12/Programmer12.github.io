/**
 * Global Notification System JavaScript
 * Handles real-time notifications across all pages with glass notifications
 */

class NotificationManager {
    constructor() {
        this.isInitialized = false;
        this.lastUnreadCount = 0;
        this.lastNotificationId = 0;
        this.pollInterval = null;
        this.glassNotifications = [];
        
        this.init();
    }

    init() {
        if (this.isInitialized) return;
        
        // Request notification permission
        this.requestPermission();
        
        // Initialize notification indicator
        this.initializeIndicator();
        
        // Create glass notification container
        this.createGlassContainer();
        
        // NOTE: Polling is now handled by individual dashboard pages
        // Do not start automatic polling here to avoid API conflicts
        
        this.isInitialized = true;
    }

    requestPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                console.log('Notification permission:', permission);
            });
        }
    }

    initializeIndicator() {
        // Check if notification link already exists in the navigation
        const nav = document.querySelector('nav');
        const existingNotificationLink = nav ? nav.querySelector('a[href*="notification_center.php"]') : null;
        
        if (existingNotificationLink) {
            // Add indicator to existing notification link if it doesn't have one
            if (!existingNotificationLink.querySelector('.notification-indicator')) {
                const indicator = document.createElement('span');
                indicator.className = 'notification-indicator';
                indicator.id = 'notification-indicator';
                indicator.style.display = 'none';
                indicator.textContent = '0';
                
                // Insert the indicator after the bell icon
                const bellIcon = existingNotificationLink.querySelector('i.fa-bell');
                if (bellIcon) {
                    bellIcon.parentNode.insertBefore(indicator, bellIcon.nextSibling);
                }
            }
        } else if (nav && !document.querySelector('.notification-indicator')) {
            // Only add new notification link if none exists
            const notificationLink = document.createElement('a');
            notificationLink.href = 'notification_center.php';
            notificationLink.className = 'notification-link';
            notificationLink.innerHTML = `
                <i class="fas fa-bell"></i>
                <span class="notification-indicator" id="notification-indicator" style="display: none;">0</span>
                Notifications
            `;
            
            // Insert before profile link
            const profileLink = nav.querySelector('a[href*="profile"]');
            if (profileLink) {
                nav.insertBefore(notificationLink, profileLink);
            } else {
                nav.appendChild(notificationLink);
            }
        }
    }

    startPolling() {
        // Poll every 10 seconds for more responsive notifications
        this.pollInterval = setInterval(() => {
            this.checkForNewNotifications();
        }, 10000);
        
        // Also check when page becomes visible
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.checkForNewNotifications();
            }
        });
    }

    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }

    checkForNewNotifications() {
        // Use the working endpoint instead of the problematic API
        fetch('check_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.notifications.length > 0) {
                    const latestNotification = data.notifications[0];
                    
                    // Check if this is a new notification
                    if (latestNotification.id > this.lastNotificationId) {
                        this.lastNotificationId = latestNotification.id;
                        this.handleNewNotification(latestNotification);
                    }
                    
                    // Update unread count
                    this.updateUnreadCount(data.unread_count);
                    this.lastUnreadCount = data.unread_count;
                }
            })
            .catch(error => {
                console.error('Error checking notifications:', error);
            });
    }

    updateUnreadCount(count = null) {
        if (count === null) {
            // Fetch current count
            fetch('api/notifications.php?action=get_unread_count')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.updateUnreadCountUI(data.unread_count);
                        this.lastUnreadCount = data.unread_count;
                    }
                })
                .catch(error => console.error('Error:', error));
        } else {
            this.updateUnreadCountUI(count);
        }
    }

    updateUnreadCountUI(count) {
        const indicator = document.getElementById('notification-indicator');
        if (indicator) {
            if (count > 0) {
                indicator.textContent = count > 99 ? '99+' : count;
                indicator.style.display = 'inline-block';
                indicator.classList.add('has-notifications');
            } else {
                indicator.style.display = 'none';
                indicator.classList.remove('has-notifications');
            }
        }
    }

    handleNewNotification(notification) {
        // Show browser notification if permission granted
        if (Notification.permission === 'granted') {
            this.showBrowserNotification(notification);
        }
        
        // Show glass notification popup
        this.showGlassNotification(notification);
        
        // Animate notification indicator
        this.animateIndicator();
    }

    showBrowserNotification(notification) {
        const browserNotification = new Notification('EduAssign - ' + notification.title, {
            body: notification.message,
            icon: '/favicon.ico',
            badge: '/favicon.ico',
            tag: 'eduassign-notification-' + notification.id,
            requireInteraction: false,
            data: notification
        });

        browserNotification.onclick = () => {
            window.focus();
            if (notification.type === 'assignment' || notification.type === 'deadline') {
                window.location.href = 'student_assignments.php';
            } else if (notification.type === 'grade') {
                window.location.href = 'student_grades.php';
            } else {
                window.location.href = 'notification_center.php';
            }
            browserNotification.close();
        };

        // Auto close after 8 seconds
        setTimeout(() => {
            browserNotification.close();
        }, 8000);
    }

    createGlassContainer() {
        // Create container for glass notifications if it doesn't exist
        if (!document.getElementById('glass-notifications-container')) {
            const container = document.createElement('div');
            container.id = 'glass-notifications-container';
            container.className = 'glass-notifications-container';
            document.body.appendChild(container);
        }
    }

    showGlassNotification(notification) {
        const container = document.getElementById('glass-notifications-container');
        if (!container) return;

        // Create glass notification element
        const glassNotif = document.createElement('div');
        glassNotif.className = 'glass-notification';
        
        // Get icon based on notification type
        let icon = 'fa-bell';
        let iconColor = '#667eea';
        switch (notification.type) {
            case 'assignment':
                icon = 'fa-tasks';
                iconColor = '#4285f4';
                break;
            case 'grade':
                icon = 'fa-star';
                iconColor = '#34a853';
                break;
            case 'deadline':
                icon = 'fa-clock';
                iconColor = '#ea4335';
                break;
            case 'general':
                icon = 'fa-info-circle';
                iconColor = '#fbbc04';
                break;
        }

        // Format time
        const timeAgo = this.getTimeAgo(notification.created_at);

        glassNotif.innerHTML = `
            <div class="glass-notification-content">
                <div class="glass-notification-header">
                    <div class="glass-notification-icon" style="color: ${iconColor}">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="glass-notification-title">${notification.title}</div>
                    <button class="glass-notification-close" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="glass-notification-message">${notification.message}</div>
                <div class="glass-notification-time">
                    <i class="fas fa-clock"></i> ${timeAgo}
                </div>
            </div>
        `;

        // Add click handler to navigate to relevant page
        glassNotif.style.cursor = 'pointer';
        glassNotif.addEventListener('click', (e) => {
            if (!e.target.closest('.glass-notification-close')) {
                // Determine URL based on notification type and related data
                let targetUrl = 'notification_center.php';
                
                if (notification.type === 'assignment' || notification.type === 'deadline') {
                    // Navigate to specific assignment if related_id exists
                    if (notification.related_id) {
                        targetUrl = 'student_view_assignment.php?id=' + notification.related_id;
                    } else {
                        targetUrl = 'student_assignments.php';
                    }
                } else if (notification.type === 'grade') {
                    // Navigate to grades page or specific assignment
                    if (notification.related_id) {
                        targetUrl = 'student_view_assignment.php?id=' + notification.related_id;
                    } else {
                        targetUrl = 'student_grades.php';
                    }
                } else if (notification.related_type === 'registration') {
                    // For admins/teachers: navigate to pending registrations
                    if (window.location.pathname.includes('admin')) {
                        targetUrl = 'admin_approve_registrations.php';
                    } else if (window.location.pathname.includes('teacher')) {
                        targetUrl = 'teacher_approve_students.php';
                    }
                } else if (notification.related_type === 'submission') {
                    // For teachers: navigate to submissions
                    if (notification.related_id) {
                        targetUrl = 'teacher_view_submission.php?id=' + notification.related_id;
                    } else {
                        targetUrl = 'teacher_submissions.php';
                    }
                }
                
                window.location.href = targetUrl;
            }
        });

        // Add to container with animation
        container.appendChild(glassNotif);
        
        // Trigger entrance animation
        setTimeout(() => {
            glassNotif.classList.add('show');
        }, 100);

        // Store reference
        this.glassNotifications.push(glassNotif);

        // Auto remove after 6 seconds
        setTimeout(() => {
            this.removeGlassNotification(glassNotif);
        }, 6000);

        // Limit to maximum 3 notifications
        if (this.glassNotifications.length > 3) {
            this.removeGlassNotification(this.glassNotifications[0]);
        }
    }

    removeGlassNotification(element) {
        if (element && element.parentNode) {
            element.classList.add('hide');
            setTimeout(() => {
                if (element.parentNode) {
                    element.parentNode.removeChild(element);
                }
                // Remove from array
                const index = this.glassNotifications.indexOf(element);
                if (index > -1) {
                    this.glassNotifications.splice(index, 1);
                }
            }, 300);
        }
    }

    getTimeAgo(dateString) {
        const now = new Date();
        const date = new Date(dateString);
        const diffInSeconds = Math.floor((now - date) / 1000);

        // Handle negative differences (future dates)
        if (diffInSeconds < 0) {
            return 'Just now';
        }

        if (diffInSeconds < 60) {
            return 'Just now';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
        } else if (diffInSeconds < 604800) { // Less than a week
            const days = Math.floor(diffInSeconds / 86400);
            return `${days} day${days !== 1 ? 's' : ''} ago`;
        } else if (diffInSeconds < 2419200) { // Less than a month
            const weeks = Math.floor(diffInSeconds / 604800);
            return `${weeks} week${weeks !== 1 ? 's' : ''} ago`;
        } else {
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            });
        }
    }

    getLastNotificationId() {
        fetch('api/notifications.php?action=get_notifications&limit=1')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.notifications.length > 0) {
                    this.lastNotificationId = data.notifications[0].id;
                }
            })
            .catch(error => console.error('Error getting last notification ID:', error));
    }

    showInPageNotification() {
        // Create and show a temporary notification popup
        const popup = document.createElement('div');
        popup.className = 'notification-popup';
        popup.innerHTML = `
            <div class="notification-popup-content">
                <i class="fas fa-bell"></i>
                <span>You have new notifications!</span>
                <button onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        
        document.body.appendChild(popup);
        
        // Auto remove after 4 seconds
        setTimeout(() => {
            if (popup.parentNode) {
                popup.parentNode.removeChild(popup);
            }
        }, 4000);
    }

    animateIndicator() {
        const indicator = document.getElementById('notification-indicator');
        if (indicator) {
            indicator.classList.add('notification-pulse');
            setTimeout(() => {
                indicator.classList.remove('notification-pulse');
            }, 2000);
        }
    }

    // Method to mark notification as read
    markAsRead(notificationId) {
        return fetch('api/notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=mark_as_read&notification_id=${notificationId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateUnreadCount(data.unread_count);
            }
            return data;
        });
    }

    // Method to mark all notifications as read
    markAllAsRead() {
        return fetch('api/notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=mark_all_read'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateUnreadCount(0);
            }
            return data;
        });
    }

    // Method to get recent notifications
    getRecentNotifications(limit = 5) {
        return fetch(`api/notifications.php?action=get_notifications&limit=${limit}`)
            .then(response => response.json());
    }

    // Cleanup method
    destroy() {
        this.stopPolling();
        this.isInitialized = false;
    }
}

// Global notification manager instance
let notificationManager = null;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if user is logged in (check for nav element)
    if (document.querySelector('nav')) {
        notificationManager = new NotificationManager();
    }
});

// Cleanup when page unloads
window.addEventListener('beforeunload', function() {
    if (notificationManager) {
        notificationManager.destroy();
    }
});

// Add CSS for notification components
const notificationStyles = `
<style>
.notification-link {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.notification-indicator {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #ff4757;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 0.7rem;
    font-weight: bold;
    min-width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: none;
}

.notification-indicator.has-notifications {
    animation: pulse 2s infinite;
}

.notification-indicator.notification-pulse {
    animation: pulse 0.5s ease-in-out 3;
}

@keyframes pulse {
    0% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(255, 71, 87, 0.7);
    }
    70% {
        transform: scale(1.1);
        box-shadow: 0 0 0 10px rgba(255, 71, 87, 0);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(255, 71, 87, 0);
    }
}

/* Glass Notifications Container */
.glass-notifications-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
    pointer-events: none;
    max-width: 400px;
    width: 100%;
}

/* Glass Notification Styles */
.glass-notification {
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 20px;
    margin-bottom: 15px;
    padding: 0;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    transform: translateX(100%);
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    pointer-events: auto;
    cursor: pointer;
    overflow: hidden;
    position: relative;
}

.glass-notification::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.6), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}

.glass-notification.show {
    transform: translateX(0);
    opacity: 1;
}

.glass-notification.hide {
    transform: translateX(100%);
    opacity: 0;
}

.glass-notification:hover {
    transform: translateX(-5px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    background: rgba(255, 255, 255, 0.35);
}

.glass-notification-content {
    padding: 20px;
    position: relative;
}

.glass-notification-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.glass-notification-icon {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 12px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.glass-notification-title {
    flex: 1;
    font-weight: 600;
    color: #000000;
    font-size: 1rem;
    line-height: 1.3;
}

.glass-notification-close {
    background: rgba(255, 255, 255, 0.3);
    border: none;
    border-radius: 8px;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #000000;
}

.glass-notification-close:hover {
    background: rgba(255, 255, 255, 0.5);
    color: #e74c3c;
    transform: scale(1.1);
}

.glass-notification-message {
    color: #000000;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 12px;
    padding-left: 52px;
}

.glass-notification-time {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #000000;
    font-size: 0.8rem;
    padding-left: 52px;
}

.glass-notification-time i {
    font-size: 0.7rem;
}

/* Old notification popup styles (kept for compatibility) */
.notification-popup {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    animation: slideInRight 0.3s ease-out;
}

.notification-popup-content {
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.notification-popup-content i {
    font-size: 1.2rem;
}

.notification-popup-content button {
    background: none;
    border: none;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    margin-left: auto;
    padding: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-popup-content button:hover {
    background: rgba(255, 255, 255, 0.2);
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Mobile responsive */
@media (max-width: 768px) {
    .glass-notifications-container {
        top: 10px;
        right: 10px;
        left: 10px;
        max-width: none;
    }
    
    .glass-notification {
        border-radius: 15px;
    }
    
    .glass-notification-content {
        padding: 15px;
    }
    
    .glass-notification-message {
        padding-left: 15px;
        font-size: 0.85rem;
    }
    
    .glass-notification-time {
        padding-left: 15px;
    }
    
    .notification-popup {
        top: 10px;
        right: 10px;
        left: 10px;
    }
    
    .notification-popup-content {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .glass-notification {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    .glass-notification-title {
        color: #000000;
    }
    
    .glass-notification-message {
        color: #000000;
    }
    
    .glass-notification-icon {
        background: rgba(255, 255, 255, 0.3);
    }
    
    .glass-notification-close {
        background: rgba(255, 255, 255, 0.3);
        color: #000000;
    }
    
    .glass-notification-close:hover {
        background: rgba(255, 255, 255, 0.5);
        color: #e74c3c;
    }
    
    .glass-notification-time {
        color: #000000;
    }
}
</style>
`;

// Inject styles into document head
document.head.insertAdjacentHTML('beforeend', notificationStyles);

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationManager;
}