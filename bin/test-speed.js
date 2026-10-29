// Open https://www.example-url.com in Chrome.
// Press F12 (or Right-Click -> Inspect) to open Developer Tools.
// Click on the Console tab.
// Copy the code below, paste it into the console, and press Enter.

(function() {
    // Wait 500ms to ensure the 'load' event has fully settled
    setTimeout(() => {
        const navData = performance.getEntriesByType('navigation')[0];
        
        if (!navData) {
            console.warn('⚠️ Performance Navigation Timing API not supported.');
            return;
        }

        // Calculate core metrics
        const metrics = {
            'DNS Lookup': (navData.domainLookupEnd - navData.domainLookupStart).toFixed(2) + ' ms',
            'TCP Connection': (navData.connectEnd - navData.connectStart).toFixed(2) + ' ms',
            'TLS/SSL Handshake': navData.secureConnectionStart > 0 
                ? (navData.connectEnd - navData.secureConnectionStart).toFixed(2) + ' ms' 
                : 'N/A (HTTP)',
            'TTFB (Server Response)': (navData.responseStart - navData.requestStart).toFixed(2) + ' ms',
            'DOM Interactive': (navData.domInteractive - navData.startTime).toFixed(2) + ' ms',
            'DOM Content Loaded': (navData.domContentLoadedEventEnd - navData.startTime).toFixed(2) + ' ms',
            'Fully Loaded (Window Load)': (navData.loadEventEnd - navData.startTime).toFixed(2) + ' ms'
        };

        console.clear();
        console.log('%c🚀 Page Performance Metrics', 'color: #00ff00; font-size: 16px; font-weight: bold;');
        console.log('%cTarget: ' + window.location.href, 'color: #00bfff; font-size: 12px;');
        console.table(metrics);

        // Bonus: Resource Summary
        const resources = performance.getEntriesByType('resource');
        const totalRequests = resources.length + 1; // +1 for the main HTML document
        const totalTransferSize = resources.reduce((acc, curr) => acc + (curr.transferSize || curr.encodedBodySize || 0), 0);
        
        console.log('%c📦 Resource Summary', 'color: #ffa500; font-size: 14px; font-weight: bold;');
        console.log(`Total Network Requests: ${totalRequests}`);
        console.log(`Estimated Transfer Size: ${(totalTransferSize / 1024).toFixed(2)} KB`);
        
        console.log('%c💡 Tip: For accurate "First Visit" metrics, open an Incognito window, paste this script, and press Enter immediately.', 'color: #888; font-style: italic;');

    }, 500);
})();