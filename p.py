import os
import re
import requests
from urllib.parse import urlparse, unquote

# Set base paths
blade_dir = "resources/views"
output_assets_dir = "public/assets"
font_dir = os.path.join(output_assets_dir, "webfonts")

os.makedirs(font_dir, exist_ok=True)

# Track downloaded files to avoid duplicates
downloaded = {}

# Regex patterns
url_pattern = re.compile(r'(https?:\/\/[^\s"\')]+)')
font_face_url = re.compile(r'url\([\'"]?(https?:\/\/[^)\'"]+)[\'"]?\)')

def download_file(url, out_dir):
    try:
        parsed_url = urlparse(url)
        filename = os.path.basename(parsed_url.path.split("?")[0])
        filepath = os.path.join(out_dir, filename)
        if filename in downloaded:
            return downloaded[filename]

        print(f"Downloading: {url}")
        response = requests.get(url, timeout=15)
        response.raise_for_status()
        with open(filepath, 'wb') as f:
            f.write(response.content)
        downloaded[filename] = f"/assets/{os.path.relpath(filepath, output_assets_dir).replace(os.sep, '/')}"
        return downloaded[filename]
    except Exception as e:
        print(f"Failed to download {url}: {e}")
        return None

def process_css(content, base_dir):
    urls = font_face_url.findall(content)
    for url in urls:
        new_path = download_file(url, font_dir)
        if new_path:
            content = content.replace(url, new_path)
    return content

def rewrite_links_in_blade(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    matches = url_pattern.findall(content)
    for url in matches:
        if any(ext in url for ext in ['.css', '.js', '.woff2', '.woff', '.ttf']):
            asset_subdir = "webfonts" if any(x in url for x in ['.woff', '.ttf']) else ""
            out_dir = os.path.join(output_assets_dir, asset_subdir)
            os.makedirs(out_dir, exist_ok=True)
            local_path = download_file(url, out_dir)
            if local_path:
                content = content.replace(url, "{{ asset('" + local_path + "') }}")

    # Save updated Blade file
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)

def update_all_blades():
    for root, _, files in os.walk(blade_dir):
        for file in files:
            if file.endswith('.blade.php'):
                full_path = os.path.join(root, file)
                print(f"Processing: {full_path}")
                rewrite_links_in_blade(full_path)

if __name__ == "__main__":
    update_all_blades()
