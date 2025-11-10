<?php
/**
 * Time utility functions for consistent timestamp handling
 */

class TimeHelper {
    
    /**
     * Format time difference in a human-readable way
     */
    public static function formatTimeAgo($datetime_string) {
        try {
            // Ensure timezone consistency
            date_default_timezone_set('Asia/Kolkata');
            
            // Create DateTime objects
            $notification_time = new DateTime($datetime_string);
            $current_time = new DateTime();
            $time_diff = $current_time->getTimestamp() - $notification_time->getTimestamp();
            
            // Handle negative time differences (future dates)
            if ($time_diff < 0) {
                return "Just now";
            }
            
            // Format based on time difference
            if ($time_diff < 60) {
                return "Just now";
            } elseif ($time_diff < 3600) {
                $minutes = floor($time_diff / 60);
                return $minutes . " minute" . ($minutes != 1 ? "s" : "") . " ago";
            } elseif ($time_diff < 86400) {
                $hours = floor($time_diff / 3600);
                return $hours . " hour" . ($hours != 1 ? "s" : "") . " ago";
            } elseif ($time_diff < 604800) { // Less than a week
                $days = floor($time_diff / 86400);
                return $days . " day" . ($days != 1 ? "s" : "") . " ago";
            } elseif ($time_diff < 2419200) { // Less than a month (4 weeks)
                $weeks = floor($time_diff / 604800);
                return $weeks . " week" . ($weeks != 1 ? "s" : "") . " ago";
            } else {
                return $notification_time->format('M j, Y g:i A');
            }
            
        } catch (Exception $e) {
            // Fallback to simple format if DateTime fails
            return date('M j, Y g:i A', strtotime($datetime_string));
        }
    }
    
    /**
     * Get current timestamp in consistent format
     */
    public static function getCurrentTimestamp() {
        date_default_timezone_set('Asia/Kolkata');
        return date('Y-m-d H:i:s');
    }
    
    /**
     * Format datetime for display
     */
    public static function formatDateTime($datetime_string, $format = 'M j, Y g:i A') {
        try {
            date_default_timezone_set('Asia/Kolkata');
            $date = new DateTime($datetime_string);
            return $date->format($format);
        } catch (Exception $e) {
            return date($format, strtotime($datetime_string));
        }
    }
    
    /**
     * Check if a timestamp is today
     */
    public static function isToday($datetime_string) {
        try {
            date_default_timezone_set('Asia/Kolkata');
            $date = new DateTime($datetime_string);
            $today = new DateTime();
            return $date->format('Y-m-d') === $today->format('Y-m-d');
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if a timestamp is within the last 24 hours
     */
    public static function isRecent($datetime_string, $hours = 24) {
        try {
            date_default_timezone_set('Asia/Kolkata');
            $notification_time = new DateTime($datetime_string);
            $current_time = new DateTime();
            $time_diff = $current_time->getTimestamp() - $notification_time->getTimestamp();
            return $time_diff < ($hours * 3600);
        } catch (Exception $e) {
            return false;
        }
    }
}
?>