<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SsrfProtectionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply SSRF protection to outgoing requests (not incoming)
        // This middleware would typically be used in service classes that make HTTP requests
        // For now, we'll implement it as a check that can be called from services
        
        return $next($request);
    }

    /**
     * Validate URL to prevent SSRF attacks
     *
     * @param string $url
     * @return bool
     */
    public static function validateUrl(string $url): bool
    {
        try {
            $parsed = parse_url($url);
            
            if ($parsed === false || !isset($parsed['host'])) {
                Log::warning("Invalid URL for SSRF check: {$url}");
                return false;
            }
            
            $host = $parsed['host'];
            
            // Check for localhost and loopback addresses
            if (self::isLocalhost($host)) {
                Log::warning("SSRF protection blocked localhost URL: {$url}");
                return false;
            }
            
            // Check for private IP ranges
            if (self::isPrivateIP($host)) {
                Log::warning("SSRF protection blocked private IP URL: {$url}");
                return false;
            }
            
            // Check for link-local addresses
            if (self::isLinkLocal($host)) {
                Log::warning("SSRF protection blocked link-local URL: {$url}");
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Error validating URL for SSRF: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Check if hostname is localhost or loopback
     */
    private static function isLocalhost(string $host): bool
    {
        $localhostPatterns = [
            'localhost',
            'localhost.localdomain',
            '127.0.0.1',
            '::1',
            '0:0:0:0:0:0:0:1'
        ];
        
        return in_array(strtolower($host), $localhostPatterns, true);
    }

    /**
     * Check if IP address is in private ranges
     */
    private static function isPrivateIP(string $host): bool
    {
        // Remove port if present
        if (strpos($host, ':') !== false) {
            $host = explode(':', $host)[0];
        }
        
        // Check if it's an IP address
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ip = ip2long($host);
            
            // Private IP ranges:
            // 10.0.0.0/8
            // 172.16.0.0/12
            // 192.168.0.0/16
            // 169.254.0.0/16 (link-local, but we'll catch it here too)
            
            $privateRanges = [
                ['start' => ip2long('10.0.0.0'), 'end' => ip2long('10.255.255.255')],
                ['start' => ip2long('172.16.0.0'), 'end' => ip2long('172.31.255.255')],
                ['start' => ip2long('192.168.0.0'), 'end' => ip2long('192.168.255.255')],
                ['start' => ip2long('169.254.0.0'), 'end' => ip2long('169.254.255.255')]
            ];
            
            foreach ($privateRanges as $range) {
                if ($ip >= $range['start'] && $ip <= $range['end']) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Check if hostname is link-local (bonjour/zeroconf)
     */
    private static function isLinkLocal(string $host): bool
    {
        // .local domain is used for multicast DNS (Bonjour/Zeroconf)
        return str_ends_with(strtolower($host), '.local');
    }
}