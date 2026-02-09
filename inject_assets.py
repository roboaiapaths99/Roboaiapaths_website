import os

files = [f for f in os.listdir(r"c:\Users\BHASKAR JOSHI\OneDrive\Desktop\my project\public_html") if f.endswith(".html")]
base_path = r"c:\Users\BHASKAR JOSHI\OneDrive\Desktop\my project\public_html"

css_link = '<link rel="stylesheet" href="css/style.css">'
aos_css = '<link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />'
aos_js = '<script src="https://unpkg.com/aos@next/dist/aos.js"></script>\n  <script>AOS.init({duration: 1000, once: true});</script>'

for file_name in files:
    file_path = os.path.join(base_path, file_name)
    
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    updated = False
    
    # Inject CSS in Head
    if '</head>' in content:
        if 'css/style.css' not in content:
            content = content.replace('</head>', f'  {css_link}\n  {aos_css}\n</head>')
            updated = True
        elif 'aos.css' not in content: # In case style.css is there but AOS is not
             content = content.replace('</head>', f'  {aos_css}\n</head>')
             updated = True

    # Inject JS in Body
    if '</body>' in content:
        if 'aos.js' not in content:
             content = content.replace('</body>', f'  {aos_js}\n</body>')
             updated = True

    if updated:
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {file_name}")
    else:
        print(f"No changes needed for {file_name}")

print("Injection complete.")
