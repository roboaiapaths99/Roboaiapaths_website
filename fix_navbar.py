import os
import re

root_dir = r"c:\Users\BHASKAR JOSHI\OneDrive\Desktop\my project\public_html"

def fix_navbar(content):
    # Case 1: Replace navbar-dark with navbar-light
    content = content.replace('navbar-dark', 'navbar-light')
    
    # Case 2: If <nav class="navbar ..."> exists but has NO navbar-light or navbar-dark
    # We should add navbar-light for visibility.
    pattern = re.compile(r'(<nav\s+class="navbar[^"]*)">')
    
    def add_theme(match):
        header = match.group(1)
        if 'navbar-light' not in header and 'navbar-dark' not in header:
            return header + ' navbar-light">'
        return match.group(0)

    content = pattern.sub(add_theme, content)
    return content

count = 0
for file in os.listdir(root_dir):
    if file.endswith(".html"):
        file_path = os.path.join(root_dir, file)
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            new_content = fix_navbar(content)
            
            if new_content != content:
                with open(file_path, 'w', encoding='utf-8') as f:
                    f.write(new_content)
                print(f"Fixed navbar in: {file}")
                count += 1
        except Exception as e:
            print(f"Error processing {file}: {e}")

print(f"Done. Fixed navbar in {count} files.")
