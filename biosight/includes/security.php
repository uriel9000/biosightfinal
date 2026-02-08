<?php
/**
 * BioSight AI: Production Security Headers
 */

// Prevent Clickjacking
header('X-Frame-Options: DENY');

// Prevent MIME-sniffing
header('X-Content-Type-Options: nosniff');

// Cross-Site Scripting Protection
header('X-XSS-Protection: 1; mode=block');

// Referrer Policy
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content Security Policy (CSP)
// Restricts scripts to self and trusted Google Fonts/Gemini APIs
$csp = "default-src 'self'; ";
$csp .= "script-src 'self' 'unsafe-inline'; "; // unsafe-inline for the manifest/sw registration
$csp .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; ";
$csp .= "font-src 'self' https://fonts.gstatic.com; ";
$csp .= "img-src 'self' data: blob:; ";
$csp .= "connect-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com; ";
$csp .= "worker-src 'self'; ";
$csp .= "manifest-src 'self';";

header("Content-Security-Policy: $csp");

// Strict Transport Security (HSTS) - 1 year
// Only enable this if the server is definitely running on HTTPS
// header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
