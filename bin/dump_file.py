"""
dump_file.py — Walks a project folder and writes every file's path + content
into a single Markdown file, suitable for pasting into a chat.

Usage:
    python dump_file.py
    python dump_file.py --root C:\\xampp\\htdocs\\flow --out project_dump.md
"""

import argparse
import sys
from pathlib import Path

# Folders to skip entirely
SKIP_DIRS = {
    '.git', 'vendor', 'node_modules', '.idea', '.vscode',
    'cache', 'tmp', 'logs', '__pycache__', '.pytest_cache',
    'dist', 'build', '.next', '.nuxt',
    'storage',
}

# Files to skip by name
SKIP_FILES = {
    '.DS_Store', 'Thumbs.db', '.gitkeep',
    'dump_file.py',
}

# Files to skip by extension
SKIP_EXTS = {
    '.log', '.lock', '.sqlite', '.db', '.min.js', '.min.css',
    '.png', '.jpg', '.jpeg', '.gif', '.webp', '.ico', '.svg',
    '.woff', '.woff2', '.ttf', '.eot', '.pdf', '.zip', '.tar',
    '.gz', '.rar', '.7z', '.exe', '.dll', '.so', '.dylib',
    '.mp4', '.mp3', '.wav', '.webm',
}

# Files to skip for safety (secrets)
SKIP_SECRET_FILES = {'.env', '.env.local', '.env.production'}

# Max file size to include
MAX_FILE_SIZE = 200 * 1024  # 200 KB


def should_skip_dir(name: str) -> bool:
    return name in SKIP_DIRS or name.startswith('.')


def should_skip_file(name: str) -> bool:
    if name in SKIP_FILES or name in SKIP_SECRET_FILES:
        return True
    return Path(name).suffix.lower() in SKIP_EXTS


def safe_read(path: Path):
    try:
        if path.stat().st_size > MAX_FILE_SIZE:
            return f"[skipped: file larger than {MAX_FILE_SIZE // 1024} KB]"
        return path.read_text(encoding='utf-8', errors='replace')
    except Exception as e:
        return f"[could not read: {e}]"


def dump_tree(root: Path):
    results = []

    def walk(folder: Path):
        try:
            entries = sorted(folder.iterdir(), key=lambda p: (p.is_file(), p.name.lower()))
        except PermissionError:
            return
        for entry in entries:
            if entry.is_dir():
                if should_skip_dir(entry.name):
                    continue
                walk(entry)
            elif entry.is_file():
                if should_skip_file(entry.name):
                    continue
                rel = entry.relative_to(root).as_posix()
                content = safe_read(entry)
                if content is not None:
                    results.append((rel, content))

    walk(root)
    return results


def build_markdown(root: Path, files):
    lines = []
    lines.append(f"# Project dump: {root.name}\n")
    lines.append(f"Total files: {len(files)}\n")
    lines.append("---\n")

    lines.append("## File tree\n")
    lines.append("```")
    for rel, _ in files:
        lines.append(rel)
    lines.append("```\n")
    lines.append("---\n")

    for rel, content in files:
        lines.append(f"### {rel}\n")
        ext = Path(rel).suffix.lstrip('.').lower() or 'text'
        lang_map = {
            'php': 'php', 'js': 'javascript', 'ts': 'typescript',
            'html': 'html', 'css': 'css', 'json': 'json',
            'md': 'markdown', 'sql': 'sql', 'sh': 'bash',
            'yml': 'yaml', 'yaml': 'yaml', 'xml': 'xml',
            'txt': 'text', 'htaccess': 'apache',
            'env': 'bash', 'gitignore': 'gitignore',
        }
        lang = lang_map.get(ext, ext)
        lines.append(f"```{lang}")
        lines.append(content.rstrip('\n'))
        lines.append("```\n")

    return '\n'.join(lines)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--root', default='.', help='Project root folder')
    parser.add_argument('--out', default='project_dump.md', help='Output markdown file')
    args = parser.parse_args()

    root = Path(args.root).resolve()
    if not root.exists():
        print(f"Error: {root} does not exist", file=sys.stderr)
        sys.exit(1)

    print(f"Scanning {root} ...")
    files = dump_tree(root)
    print(f"Found {len(files)} files")

    md = build_markdown(root, files)
    out_path = root / args.out
    out_path.write_text(md, encoding='utf-8')
    size_kb = out_path.stat().st_size / 1024
    print(f"Written: {out_path} ({size_kb:.1f} KB)")


if __name__ == '__main__':
    main()