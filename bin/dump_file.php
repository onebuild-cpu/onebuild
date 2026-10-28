<?php
/**
 * dump_file.php — Walks a project folder and writes every file's path + content
 * into a single Markdown file, suitable for pasting into a chat.
 *
 * Usage:
 *     php dump_file.php
 *     php dump_file.php --root="C:\\xampp\\htdocs\\flow" --out="project_dump.md"
 */

// Parse command-line arguments manually
$options = getopt('', ['root:', 'out:']);
$rootPath = $options['root'] ?? '.';
$outFile = $options['out'] ?? 'project_dump.md';

// Resolve root path
$root = realpath($rootPath);
if ($root === false || !is_dir($root)) {
    fwrite(STDERR, "Error: {$rootPath} does not exist or is not a directory\n");
    exit(1);
}

// Folders to skip entirely
$skipDirs = [
    '.git', 'vendor', 'node_modules', '.idea', '.vscode',
    'cache', 'tmp', 'logs', '__pycache__', '.pytest_cache',
    'dist', 'build', '.next', '.nuxt',
    'storage',
];

// Files to skip by name
$skipFiles = [
    '.DS_Store', 'Thumbs.db', '.gitkeep',
    'dump_file.php', 'dump_file.py',
];

// Files to skip by extension
$skipExts = [
    'log', 'lock', 'sqlite', 'db', 'min.js', 'min.css',
    'png', 'jpg', 'jpeg', 'gif', 'webp', 'ico', 'svg',
    'woff', 'woff2', 'ttf', 'eot', 'pdf', 'zip', 'tar',
    'gz', 'rar', '7z', 'exe', 'dll', 'so', 'dylib',
    'mp4', 'mp3', 'wav', 'webm',
];

// Files to skip for safety (secrets)
$skipSecretFiles = ['.env', '.env.local', '.env.production'];

// Max file size to include (200 KB)
$maxFileSize = 200 * 1024;

// Helper function to check if directory should be skipped
$shouldSkipDir = function(string $name) use ($skipDirs): bool {
    if (in_array($name, $skipDirs, true)) {
        return true;
    }
    // Skip hidden folders starting with a dot (except if it's the root itself)
    if (str_starts_with($name, '.')) {
        return true;
    }
    return false;
};

// Helper function to check if file should be skipped
$shouldSkipFile = function(string $name) use ($skipFiles, $skipSecretFiles, $skipExts): bool {
    if (in_array($name, $skipFiles, true) || in_array($name, $skipSecretFiles, true)) {
        return true;
    }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return in_array($ext, $skipExts, true);
};

// Helper function to safely read file content
$safeRead = function(SplFileInfo $file) use ($maxFileSize): string {
    if ($file->getSize() > $maxFileSize) {
        return "[skipped: file larger than " . ($maxFileSize // 1024) . " KB]";
    }
    try {
        $content = file_get_contents($file->getPathname());
        if ($content === false) {
            return "[could not read file]";
        }
        // Normalize line endings and handle encoding issues gracefully
        return mb_convert_encoding($content, 'UTF-8', 'UTF-8');
    } catch (\Throwable $e) {
        return "[could not read: " . $e->getMessage() . "]";
    }
};

echo "Scanning {$root} ...\n";

$files = [];

// Recursive directory iterator
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $path) {
    /** @var SplFileInfo $path */
    $subPathName = $iterator->getSubPathName();
    // Normalize path separators for cross-platform consistency
    $rel = str_replace('\\', '/', $subPathName);
    $name = $path->getFilename();

    if ($path->isDir()) {
        if ($shouldSkipDir($name)) {
            $iterator->next(); // or let the iterator handle via filter, but manual check works fine
        }
        continue;
    }

    if ($path->isFile()) {
        // Check if any parent directory in the path should be skipped
        $parts = explode('/', $rel);
        $skip = false;
        foreach ($parts as $part) {
            if ($shouldSkipDir($part)) {
                $skip = true;
                break;
            }
        }

        if ($skip || $shouldSkipFile($name)) {
            continue;
        }

        $content = $safeRead($path);
        $files[] = [$rel, $content];
    }
}

// Sort files alphabetically by relative path
usort($files, fn($a, $b) => strcasecmp($a[0], $b[0]));

echo "Found " . count($files) . " files\n";

// Build Markdown content
$lines = [];
$lines[] = "# Project dump: " . basename($root) . "\n";
$lines[] = "Total files: " . count($files) . "\n";
$lines[] = "---\n";

$lines[] = "## File tree\n";
$lines[] = "```";
foreach ($files as [$rel,]) {
    $lines[] =$rel;
}
$lines[] = "```\n";
$lines[] = "---\n";

$langMap = [
    'php' => 'php', 'js' => 'javascript', 'ts' => 'typescript',
    'html' => 'html', 'css' => 'css', 'json' => 'json',
    'md' => 'markdown', 'sql' => 'sql', 'sh' => 'bash',
    'yml' => 'yaml', 'yaml' => 'yaml', 'xml' => 'xml',
    'txt' => 'text', 'htaccess' => 'apache',
    'env' => 'bash', 'gitignore' => 'gitignore',
];

foreach ($files as [$rel, $content]) {
    $lines[] = "### {$rel}\n";
    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
    $lang = $langMap[$ext] ?? ($ext !== '' ? $ext : 'text');
    
    $lines[] = "```{$lang}";
    $lines[] = rtrim($content, "\r\n");
    $lines[] = "```\n";
}

$markdown = implode("\n", $lines);
$outPath = $root . DIRECTORY_SEPARATOR . $outFile;

file_put_contents($outPath, $markdown);
$sizeKb = filesize($outPath) / 1024;

echo "Written: {$outPath} (" . number_format($sizeKb, 1) . " KB)\n";