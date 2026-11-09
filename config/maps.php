<?php
/**
 * Google Maps Platform Configuration
 * Online Tourism Portal
 */

define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY') ?: 'AIzaSyCsemFb2FW9TxSJbYU2tpsA7kxxXLxdmDQ');
define('GOOGLE_MAPS_MAP_ID', getenv('GOOGLE_MAPS_MAP_ID') ?: 'DEMO_MAP_ID');

if (!function_exists('getDestinationCoordinates')) {
    /**
     * Return coordinate for destination with graceful fallback
     */
    function getDestinationCoordinates(array $dest): array {
        if (!empty($dest['latitude']) && !empty($dest['longitude'])) {
            return [(float)$dest['latitude'], (float)$dest['longitude']];
        }
        $name = strtolower(trim($dest['name'] ?? ''));
        $lookup = [
            'manali' => [32.2432, 77.1892],
            'goa' => [15.2993, 74.1240],
            'jaipur' => [26.9124, 75.7873],
            'kashmir' => [34.0837, 74.7973],
            'rishikesh' => [30.0869, 78.2676],
            'ooty' => [11.4102, 76.6950],
            'darjeeling' => [27.0410, 88.2663],
            'varanasi' => [25.3176, 82.9739],
            'udaipur' => [24.5854, 73.7125],
            'munnar' => [10.0889, 77.0595],
        ];
        return $lookup[$name] ?? [21.7679, 78.8718];
    }
}
