#include <iostream>
#include <filesystem>
#include <fstream>
#include <vector>
#include <string>
#include <algorithm>
#include <unordered_set>

namespace fs = std::filesystem;

// Folders to skip entirely
const std::unordered_set<std::string> SKIP_DIRS = {
    ".git", "vendor", "node_modules", ".idea", ".vscode",
    "cache", "tmp", "logs", "__pycache__", ".pytest_cache",
    "dist", "build", ".next", ".nuxt", "storage"
};

// Files to skip by name
const std::unordered_set<std::string> SKIP_FILES = {
    ".DS_Store", "Thumbs.db", ".gitkeep", "dump_file.cpp", "dump_file.exe"
};

// Files to skip by extension
const std::unordered_set<std::string> SKIP_EXTS = {
    ".log", ".lock", ".sqlite", ".db", ".min.js", ".min.css",
    ".png", ".jpg", ".jpeg", ".gif", ".webp", ".ico", ".svg",
    ".woff", ".woff2", ".ttf", ".eot", ".pdf", ".zip", ".tar",
    ".gz", ".rar", ".7z", ".exe", ".dll", ".so", ".dylib",
    ".mp4", ".mp3", ".wav", ".webm"
};

// Files to skip for safety (secrets)
const std::unordered_set<std::string> SKIP_SECRETS = {
    ".env", ".env.local", ".env.production"
};

// Max file size to include (200 KB)
const std::size_t MAX_FILE_SIZE = 200 * 1024;

bool should_skip_dir(const std::string& name) {
    if (SKIP_DIRS.count(name)) return true;
    if (!name.empty() && name[0] == '.') return true;
    return false;
}

bool should_skip_file(const std::string& name) {
    if (SKIP_FILES.count(name) || SKIP_SECRETS.count(name)) return true;
    
    fs::path p(name);
    std::string ext = p.extension().string();
    std::transform(ext.begin(), ext.end(), ext.begin(), ::tolower);
    return SKIP_EXTS.count(ext);
}

std::string safe_read(const fs::path& path) {
    try {
        if (fs::file_size(path) > MAX_FILE_SIZE) {
            return "[skipped: file larger than 200 KB]";
        }
        std::ifstream file(path, std::ios::binary);
        if (!file) return "[could not read file]";
        std::string content((std::istreambuf_iterator<char>(file)), std::istreambuf_iterator<char>());
        return content;
    } catch (const std::exception& e) {
        return std::string("[could not read: ") + e.what() + "]";
    }
}

int main(int argc, char* argv[]) {
    std::string root_path = ".";
    std::string out_file = "project_dump.md";

    // Simple argument parsing
    for (int i = 1; i < argc; ++i) {
        std::string arg = argv[i];
        if (arg == "--root" && i + 1 < argc) {
            root_path = argv[++i];
        } else if (arg == "--out" && i + 1 < argc) {
            out_file = argv[++i];
        }
    }

    fs::path root;
    try {
        root = fs::absolute(root_path);
    } catch (...) {
        std::cerr << "Error: Invalid root path\n";
        return 1;
    }

    if (!fs::exists(root) || !fs::is_directory(root)) {
        std::cerr << "Error: " << root << " does not exist or is not a directory\n";
        return 1;
    }

    std::cout << "Scanning " << root << " ...\n";

    std::vector<std::pair<std::string, std::string>> files;

    try {
        for (auto it = fs::recursive_directory_iterator(root, fs::directory_options::skip_permission_denied);
             it != fs::recursive_directory_iterator(); ++it) {
            
            const auto& entry = *it;
            fs::path rel = fs::relative(entry.path(), root);
            
            // Check if any directory component should be skipped
            bool skip = false;
            for (const auto& part : rel) {
                std::string part_str = part.string();
                if (should_skip_dir(part_str)) {
                    skip = true;
                    if (entry.is_directory()) {
                        it.disable_recursion_pending();
                    }
                    break;
                }
            }

            if (skip) continue;

            if (entry.is_regular_file()) {
                std::string filename = entry.path().filename().string();
                if (should_skip_file(filename)) continue;

                std::string rel_str = rel.generic_string();
                std::string content = safe_read(entry.path());
                files.push_back({rel_str, content});
            }
        }
    } catch (const std::exception& e) {
        std::cerr << "Error during scan: " << e.what() << "\n";
    }

    // Sort files alphabetically by relative path
    std::sort(files.begin(), files.end(), [](const auto& a, const auto& b) {
        return a.first < b.first;
    });

    std::cout << "Found " << files.size() << " files\n";

    // Build Markdown content
    std::string md;
    md += "# Project dump: " + root.filename().string() + "\n\n";
    md += "Total files: " + std::to_string(files.size()) + "\n\n";
    md += "---\n\n## File tree\n\n```\n";
    for (const auto& f : files) {
        md += f.first + "\n";
    }
    md += "```\n\n---\n\n";

    for (const auto& [rel, content] : files) {
        md += "### " + rel + "\n\n";
        
        fs::path p(rel);
        std::string ext = p.extension().string();
        if (!ext.empty() && ext[0] == '.') ext = ext.substr(1);
        std::transform(ext.begin(), ext.end(), ext.begin(), ::tolower);
        
        std::string lang = ext;
        if (ext == "php") lang = "php";
        else if (ext == "js") lang = "javascript";
        else if (ext == "ts") lang = "typescript";
        else if (ext == "html") lang = "html";
        else if (ext == "css") lang = "css";
        else if (ext == "json") lang = "json";
        else if (ext == "md") lang = "markdown";
        else if (ext == "sql") lang = "sql";
        else if (ext == "cpp" || ext == "cc" || ext == "h") lang = "cpp";
        else if (ext == "sh") lang = "bash";
        else if (ext == "yml" || ext == "yaml") lang = "yaml";
        else if (ext == "xml") lang = "xml";
        else if (ext == "txt") lang = "text";
        else if (ext == "htaccess") lang = "apache";
        else if (ext == "env") lang = "bash";
        else if (ext == "gitignore") lang = "gitignore";
        else if (ext.empty()) lang = "text";

        md += "```" + lang + "\n";
        
        std::string trimmed_content = content;
        while (!trimmed_content.empty() && (trimmed_content.back() == '\n' || trimmed_content.back() == '\r')) {
            trimmed_content.pop_back();
        }
        md += trimmed_content + "\n```\n\n";
    }

    fs::path out_path = root / out_file;
    std::ofstream out(out_path, std::ios::binary);
    if (!out) {
        std::cerr << "Error: Could not write to " << out_path << "\n";
        return 1;
    }
    out.write(md.data(), md.size());

    double size_kb = static_cast<double>(fs::file_size(out_path)) / 1024.0;
    std::cout << "Written: " << out_path << " (" << size_kb << " KB)\n";

    return 0;
}