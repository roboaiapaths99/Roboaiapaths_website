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
    "cart.html",
    "checkout.html"
]

base_path = r"c:\Users\BHASKAR JOSHI\OneDrive\Desktop\my project\public_html"

# Common substring to target
target_str = "Copyright © 2026 roboaiapaths.com"
# What we want it to look like (we will replace the target with target + suffix)
suffix = " | Aegis of AGPK Academy"

for file_name in files:
    file_path = os.path.join(base_path, file_name)
    if not os.path.exists(file_path):
        print(f"Skipping {file_name} (not found)")
        continue

    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check if already updated
    if "Aegis of AGPK Academy" in content:
        print(f"Skipping {file_name} (already updated)")
        continue

    if target_str in content:
        # Replace the first occurrence (or all, though likely only one in footer)
        new_content = content.replace(target_str, target_str + suffix)
        
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {file_name}")
    else:
        print(f"WARNING: Could not find copyright string in {file_name}")

print("Footer update complete.")
