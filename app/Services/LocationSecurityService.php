<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationSecurityService
{
    /**
     * Detect VPN/Proxy usage via ProxyCheck.io API
     * Free tier available: https://proxycheck.io/
     */
    public function detectVpn(string $ipAddress): bool
    {
        try {
            $apiKey = config('services.proxycheck.key');
            if (!$apiKey) {
                // If API key not configured, skip VPN detection (not ideal but graceful)
                return false;
            }

            $response = Http::timeout(5)
                ->get('https://proxycheck.abuseipdb.com/v2/', [
                    'ip' => $ipAddress,
                    'key' => $apiKey,
                    'vpn' => 1,
                    'format' => 'json',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // ProxyCheck returns 'yes'/'no' strings for is_proxy and is_vpn
                // Check if IP is flagged as proxy or VPN
                if (isset($data['status']) && $data['status'] === 'ok') {
                    return ($data['is_proxy'] ?? 'no') === 'yes' 
                        || ($data['is_vpn'] ?? 'no') === 'yes';
                }
            }
        } catch (\Exception $e) {
            // Log error but don't block on service failure
            Log::warning("VPN detection failed for IP {$ipAddress}: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Get geolocation data from IP
     */
    public function getGeolocation(string $ipAddress): ?array
    {
        try {
            $apiKey = config('services.ipqualityscore.key');
            if (!$apiKey) {
                return null;
            }

            $response = Http::timeout(5)
                ->get("https://ipqualityscore.com/api/json/ip/{$ipAddress}", [
                    'key' => $apiKey,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'city' => $data['city'] ?? 'Unknown',
                    'country' => $data['country_name'] ?? 'Unknown',
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::warning("Geolocation lookup failed for IP {$ipAddress}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Check if location is anomalous (first login or suspicious jump)
     */
    public function isAnomalousLocation(User $user, ?array $newLocation): bool
    {
        // If we couldn't get location data, don't block (fail gracefully)
        if (!$newLocation) {
            return false;
        }

        // First login - always consider safe but track it
        if (!$user->last_login_at) {
            return false;
        }

        // Check if location matches a trusted location
        $trustedLocations = $user->trusted_locations ?? [];
        $locationKey = $newLocation['city'] . ', ' . $newLocation['country'];

        if (in_array($locationKey, $trustedLocations)) {
            return false; // Trusted location
        }

        // Check for impossible travel (location change too fast)
        if ($user->last_login_at && $user->last_login_at->diffInMinutes(now()) < 30) {
            // Same user logged in from different country within 30 minutes = suspicious
            if ($user->last_login_country !== $newLocation['country']) {
                return true;
            }
        }

        return false; // Location seems reasonable
    }

    /**
     * Mark location as trusted for future logins
     */
    public function trustLocation(User $user, string $city, string $country): void
    {
        $trustedLocations = $user->trusted_locations ?? [];
        $locationKey = $city . ', ' . $country;

        if (!in_array($locationKey, $trustedLocations)) {
            $trustedLocations[] = $locationKey;
            $user->update(['trusted_locations' => $trustedLocations]);
        }
    }

    /**
     * Record login location
     */
    public function recordLogin(User $user, string $ipAddress, ?array $geolocation): void
    {
        if ($geolocation) {
            $user->update([
                'last_login_ip' => $ipAddress,
                'last_login_city' => $geolocation['city'] ?? 'Unknown',
                'last_login_country' => $geolocation['country'] ?? 'Unknown',
                'last_login_at' => now(),
            ]);

            // Auto-trust first location
            if (!$user->trusted_locations || count($user->trusted_locations) === 0) {
                $this->trustLocation($user, $geolocation['city'] ?? 'Unknown', $geolocation['country'] ?? 'Unknown');
            }
        }
    }
}
