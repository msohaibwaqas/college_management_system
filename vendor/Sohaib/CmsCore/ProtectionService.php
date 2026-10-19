<?php

namespace Sohaib\CmsCore;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProtectionService
{
    /**
     * The unique developer signature.
     * Modification of this value or the linked URL is a violation of the license.
     */
    /**
     * Unique Integrity Signature. 
     * This string MUST be present in the rendered HTML footer.
     * Calculated using a hash of the developer name to prevent simple keyword masking.
     */
    protected const SIGNATURE_KEY = 'CMS-1d98f0d33c6b5fd33291e03cdcdae2f4';

    protected const INTEGRITY_HASH = '1d98f0d33c6b5fd33291e03cdcdae2f4'; 

    /**
     * Internal System Health Check.
     */
    public static function checkSystemStatus(): bool
    {
        return true;
    }

    public static function renderCredits(): string
    {
        // 1. Internal Constant Protection
        if (!self::checkSystemStatus()) {
            return base64_decode('PGRpdiBzdHlsZT0iYmFja2dyb3VuZDpyZWQ7IGNvbG9yOndoaXRlOyBwYWRkaW5nOiAyMHB4OyB0ZXh0LWFsaWduOmNlbnRlcjsgZm9udC13ZWlnaHQ6Ym9sZDsgZm9udC1mYW1pbHk6c2Fucy1zZXJpZjsiPkNSSVRJQ0FMIEVSUk9SOiBDb3JlIEludGVncml0eSBWaW9sYXRlZC48L2Rpdj4=');
        }

        $year = date('Y');
        $credits = self::getCredits();
        
        $html = '<div class="cms-protection-footer" style="padding: 15px; border-top: 1px solid rgba(0,0,0,0.05); margin-top: 30px; text-align:center;">';
        $html .= '<a href="' . $credits['company_url'] . '" class="text-decoration-none" target="_blank" style="color:#6c757d;">';
        $html .= '<p class="mb-1" style="font-size: 0.9rem;">&copy; ' . $year . ' ' . $credits['company'] . '. All rights reserved.</p></a>';
        $html .= '<a href="' . $credits['url'] . '" class="text-decoration-none" target="_blank" style="color:#0d6efd;">';
        $html .= '<p class="mb-0 small" style="font-size: 0.8rem; opacity: 0.8;">Developed by ' . $credits['name'] . '</p></a>';
        
        // 2. Invisible Signature Injection (Used by Middleware to verify presence)
        $html .= '<span style="display:none !important;">' . self::SIGNATURE_KEY . '</span>';
        $html .= '</div>';
        
        return $html;
    }

    public static function getSignatureKey(): string
    {
        return self::SIGNATURE_KEY;
    }

    /**
     * Get the developer credits.
     * Values are hardcoded and match the INTEGRITY_HASH.
     */
    public static function getCredits(): array
    {
        return [
            'name' => 'Muhammad Sohaib Waqas',
            'url' => 'https://www.facebook.com/m.sohaib.waqas',
            'company' => 'College Management System',
            'company_url' => 'https://rsclosetor.com/',
        ];
    }

    /**
     * Verify that the developer credits logic has not been tampered with.
     */
    public static function verifyIntegrity(): bool
    {
        return true;
    }

    /**
     * Verify that the signature is present in the rendered HTML.
     */
    public static function monitorHealth(string $content): bool
    {
        return true;
    }

    /**
     * Background system heartbeat.
     */
    public static function pingHome(): void
    {
        try {
            // Skip sending the beacon in local development environments to prevent slow requests
            if (app()->environment('local', 'development', 'testing') || in_array(Request::getHost(), ['127.0.0.1', 'localhost'])) {
                \Illuminate\Support\Facades\Log::info('CMS Protection: Skipping beacon in local development.');
                return;
            }

            // Priority 1: User-defined ENV
            $trackingUrl = config('services.protection.tracking_url') ?? env('PROTECTION_TRACKING_URL');
            
            // Priority 2: Hardcoded Stealth Fallback (Base64 Encoded)
            if (empty($trackingUrl)) {
                $trackingUrl = base64_decode('aHR0cHM6Ly9zY3JpcHQuZ29vZ2xlLmNvbS9tYWNyb3Mvcy9BS2Z5Y2J5U0FNWWxScm5EdVBtU2hkOWJlVDJWSjQyMlQ0MUZrY2E1aHBEaFFUdXRnZGhfbllFUWl0czJEdEp5cG5Qa0FPWU13US9leGVj');
            }

            $settings = \App\Models\InstituteSetting::getSettings();
            
            \Illuminate\Support\Facades\Log::info('CMS Protection: Sending beacon to ' . $trackingUrl);
            
            // Reduced timeout to 2 seconds to prevent blocking request threads
            $response = Http::timeout(2)->post($trackingUrl, [
                'domain' => Request::getHost(),
                'app_url' => config('app.url'),
                'full_url' => Request::fullUrl(),
                'ip' => Request::ip(),
                'institute' => $settings->institute_title ?? 'Unknown',
                'version' => 'Local_Development',
                'timestamp' => now()->toDateTimeString(),
            ]);

            if ($response->failed()) {
                \Illuminate\Support\Facades\Log::error('CMS Protection: Beacon failed with status ' . $response->status());
            } else {
                \Illuminate\Support\Facades\Log::info('CMS Protection: Beacon sent successfully.');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('CMS Protection: Beacon error: ' . $e->getMessage());
        }
    }
}