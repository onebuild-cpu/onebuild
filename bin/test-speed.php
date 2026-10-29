<?php
/**
 * CLI Web Page Speed Tester
 * Usage: php test-speed.php "https://www.example.com"
 */

// 1. Validate command line arguments
if ($argc < 2) {
    echo "Usage: php test-speed.php <url>\n";
    echo "Example: php test-speed.php https://www.example.com\n";
    echo "Example: php test-speed.php www.example.com\n";
    exit(1);
}

$url = $argv[1];

// 2. Ensure URL has a scheme (http:// or https://)
if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
    $url = 'https://' . $url;
}

echo "Testing speed for: $url\n";
echo str_repeat("-", 65) . "\n";

// 3. Initialize cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,      // Follow redirects
    CURLOPT_ENCODING       => "",        // Handle gzip/deflate compression
    CURLOPT_SSL_VERIFYPEER => true,      // Verify SSL certificates
    CURLOPT_NOBODY         => false,     // We MUST download the body to measure "Fully Loaded"
    // Realistic User-Agent to avoid 403 Forbidden from strict firewalls (e.g., Cloudflare)
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
]);

// 4. Execute the request
curl_exec($ch);

// 5. Handle errors
if (curl_errno($ch)) {
    echo "❌ cURL Error: " . curl_error($ch) . "\n";
    curl_close($ch);
    exit(1);
}

// 6. Get timing metrics
$info = curl_getinfo($ch);
curl_close($ch);

// --- Helper Functions ---

// Convert seconds to milliseconds
function ms(float $seconds): string {
    return number_format($seconds * 1000, 2) . ' ms';
}

// Format bytes into human-readable KB/MB
function formatBytes(int $bytes): string {
    if ($bytes <= 0) return 'Unknown';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes, 1024));
    return number_format($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

// Get rating for time-based metrics
function getRating(float $ms, float $fastThreshold, float $moderateThreshold): string {
    if ($ms <= $fastThreshold) return "🟢 Fast";
    if ($ms <= $moderateThreshold) return "🟡 Moderate";
    return "🔴 Slow";
}

// Get rating for file size
function getSizeRating(int $bytes): string {
    $mb = $bytes / (1024 * 1024);
    if ($mb < 1.0) return "🟢 Light (< 1 MB)";
    if ($mb < 3.0) return "🟡 Moderate (1 - 3 MB)";
    return "🔴 Heavy (> 3 MB)";
}

// --- Calculations ---

$dnsTime = $info['namelookup_time'];
$tcpTime = $info['connect_time'] - $dnsTime;
$sslTime = $info['pretransfer_time'] - $info['connect_time'];
if ($sslTime < 0) $sslTime = 0; // Fallback for non-HTTPS

$ttfbMs = $info['starttransfer_time'] * 1000;
$totalMs = $info['total_time'] * 1000;

// Page size: prefer uncompressed content length, fallback to downloaded (compressed) size
$pageSize = (int) $info['download_content_length'];
if ($pageSize <= 0) {
    $pageSize = (int) $info['size_download'];
}
$downloadedSize = (int) $info['size_download'];

// 7. Output the results
echo "⏱️  TIMING METRICS:\n";
echo "  • DNS Resolution : " . ms($dnsTime) . "\n";
echo "  • TCP Connect    : " . ms($tcpTime) . "\n";
echo "  • SSL Handshake  : " . ms($sslTime) . "\n";
echo "  • TTFB           : " . ms($info['starttransfer_time']) . " " . getRating($ttfbMs, 200, 600) . "\n";
echo "  • Fully Loaded   : " . ms($info['total_time']) . " " . getRating($totalMs, 1000, 3000) . "\n";

echo str_repeat("-", 65) . "\n";
echo "📊 ADDITIONAL INFO:\n";
echo "  • HTTP Status    : " . $info['http_code'] . "\n";
echo "  • Redirects      : " . $info['redirect_count'] . "\n";
echo "  • Page Size      : " . formatBytes($pageSize) . " " . getSizeRating($pageSize) . "\n";
echo "  • Downloaded     : " . formatBytes($downloadedSize) . " (Compressed over network)\n";
echo "  • Avg Speed      : " . formatBytes((int)$info['speed_download']) . "/s\n";

echo str_repeat("-", 65) . "\n";

// 8. Overall Verdict (Plain English Summary)
echo "💡 OVERALL VERDICT:\n";
$sizeMb = $pageSize / (1024 * 1024);

if ($ttfbMs <= 200 && $totalMs <= 1000 && $sizeMb < 1.0) {
    echo "  🚀 EXCELLENT! Your page loads very fast and is well-optimized.\n";
    echo "     No immediate action required.\n";
} elseif ($ttfbMs <= 600 && $totalMs <= 3000 && $sizeMb < 3.0) {
    echo "  ⚠️  MODERATE. Your page loads acceptably, but there is room for improvement.\n";
    echo "     Consider: compressing images, enabling OPcache, or reviewing database queries.\n";
} else {
    echo "  🐌 SLOW. Your page takes too long to load or is too heavy.\n";
    echo "     Recommended actions:\n";
    echo "     - Check for slow database queries or missing indexes.\n";
    echo "     - Ensure OPcache is enabled in php.ini.\n";
    echo "     - Reduce page size (optimize images/assets).\n";
}
echo str_repeat("-", 65) . "\n";