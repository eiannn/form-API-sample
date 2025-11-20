<?php
class FileStorage {
    private $basePath;
    
    public function __construct() {
        $this->basePath = dirname(dirname(__FILE__)) . '/user_data';
        $this->createBaseDirectory();
    }
    
    private function createBaseDirectory() {
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }
    
    public function storeUserData($user_id, $username, $action, $data = []) {
        $timestamp = time();
        $date = getdate($timestamp);
        
        // Create directory structure: user_data/year/month/day/user_id/
        $userPath = $this->basePath . '/' . $date['year'] . '/' . 
                   str_pad($date['mon'], 2, '0', STR_PAD_LEFT) . '/' . 
                   str_pad($date['mday'], 2, '0', STR_PAD_LEFT) . '/' . 
                   $user_id;
        
        if (!is_dir($userPath)) {
            mkdir($userPath, 0755, true);
        }
        
        // Create filename with timestamp
        $filename = $timestamp . '_' . $action . '.json';
        $filePath = $userPath . '/' . $filename;
        
        $fileData = [
            'timestamp' => $timestamp,
            'datetime' => date('Y-m-d H:i:s', $timestamp),
            'user_id' => $user_id,
            'username' => $username,
            'action' => $action,
            'data' => $data,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        file_put_contents($filePath, json_encode($fileData, JSON_PRETTY_PRINT));
        
        return $filePath;
    }
    
    public function getUserActivity($user_id, $limit = null) {
        $activities = [];
        $years = glob($this->basePath . '/*', GLOB_ONLYDIR);
        
        foreach ($years as $yearPath) {
            $months = glob($yearPath . '/*', GLOB_ONLYDIR);
            foreach ($months as $monthPath) {
                $days = glob($monthPath . '/*', GLOB_ONLYDIR);
                foreach ($days as $dayPath) {
                    $userDirs = glob($dayPath . '/*', GLOB_ONLYDIR);
                    foreach ($userDirs as $userDir) {
                        if (basename($userDir) == $user_id) {
                            $files = glob($userDir . '/*.json');
                            foreach ($files as $file) {
                                $content = json_decode(file_get_contents($file), true);
                                if ($content) {
                                    $activities[] = $content;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Sort by timestamp descending (newest first)
        usort($activities, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        if ($limit) {
            return array_slice($activities, 0, $limit);
        }
        
        return $activities;
    }
    
    public function getAllUsersActivity($limit = null) {
        $allActivities = [];
        $years = glob($this->basePath . '/*', GLOB_ONLYDIR);
        
        foreach ($years as $yearPath) {
            $months = glob($yearPath . '/*', GLOB_ONLYDIR);
            foreach ($months as $monthPath) {
                $days = glob($monthPath . '/*', GLOB_ONLYDIR);
                foreach ($days as $dayPath) {
                    $userDirs = glob($dayPath . '/*', GLOB_ONLYDIR);
                    foreach ($userDirs as $userDir) {
                        $files = glob($userDir . '/*.json');
                        foreach ($files as $file) {
                            $content = json_decode(file_get_contents($file), true);
                            if ($content) {
                                $allActivities[] = $content;
                            }
                        }
                    }
                }
            }
        }
        
        // Sort by timestamp descending (newest first)
        usort($allActivities, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        if ($limit) {
            return array_slice($allActivities, 0, $limit);
        }
        
        return $allActivities;
    }
    
    public function getUserStats($user_id) {
        $activities = $this->getUserActivity($user_id);
        $stats = [
            'total_activities' => count($activities),
            'first_activity' => null,
            'last_activity' => null,
            'activities_by_type' => []
        ];
        
        if (!empty($activities)) {
            $stats['first_activity'] = end($activities);
            $stats['last_activity'] = reset($activities);
            
            foreach ($activities as $activity) {
                $action = $activity['action'];
                if (!isset($stats['activities_by_type'][$action])) {
                    $stats['activities_by_type'][$action] = 0;
                }
                $stats['activities_by_type'][$action]++;
            }
        }
        
        return $stats;
    }
}
?>