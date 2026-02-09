import os

files = [
    "index.html",
    "about.html",
    "program.html",
    "blog.html",
    "contact.html",
    "join-as-a-trainer.html",
    "partner-with-school.html",
    "gallery.html",
    "kits.html",
    "cart.html",
    "checkout.html"
]

base_path = r"c:\Users\BHASKAR JOSHI\OneDrive\Desktop\my project\public_html"

target_str = '<p class="small text-muted">Copyright © 2026 roboaiapaths.com</p>'
replacement_str = '<p class="small text-muted">Copyright © 2026 roboaiapaths.com | Aegis of AGPK Academy</p>'

for file_name in files:
    file_path = os.path.join(base_path, file_name)
    if not os.path.exists(file_path):
        print(f"Skipping {file_name} (not found)")
        continue

    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    if target_str in content:
        content = content.replace(target_str, replacement_str)
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated footer in {file_name}")
    elif "Aegis of AGPK Academy" in content:
        print(f"Already updated {file_name}")
    else:
        print(f"Could not find copyright string in {file_name}")

print("Footer update complete.")
