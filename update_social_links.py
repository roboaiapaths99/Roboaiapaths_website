import os

# List of files to update (excluding contact.html which we did manually)
files = [
    "index.html",
    "about.html",
    "program.html",
    "blog.html",
    "join-as-a-trainer.html",
    "partner-with-school.html",
    "gallery.html",
    "cart.html",
    "checkout.html",
    "kits.html",
    "hello-world.html"
]

base_path = r"c:\Users\BHASKAR JOSHI\OneDrive\Desktop\my project\public_html"

# The target string to replace (based on index.html)
# We use a unique part of it to be safe, but minimal context
target_block = """<a href="#"><img src="img/facebookLogo.png" class="social-icon" alt=""></a>
              <a href="#"><img src="img/instagramLogo.png" class="social-icon" alt=""></a>"""

# The replacement block
replacement_block = """<a href="https://www.facebook.com/profile.php?id=61561157124167&mibextid=ZbWKwL" target="_blank"><img src="img/facebookLogo.png" class="social-icon" alt=""></a>
              <a href="https://www.instagram.com/roboaiapaths?igsh=MWdmNXZnYXY0ejdhYw==" target="_blank"><img src="img/instagramLogo.png" class="social-icon" alt=""></a>"""

for file_name in files:
    file_path = os.path.join(base_path, file_name)
    if not os.path.exists(file_path):
        print(f"Skipping {file_name} (not found)")
        continue

    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Normalize newlines just in case, though sticking to read/write mode usually preserves
    # We will try to replace. If exact match fails, we might need to be more flexible.
    # Let's try simple string replacement first.
    
    # We strip whitespace for the check to be more robust? No, better to match exact if possible.
    # But files might have different indentation.
    # Let's target the core `href="#"` part of these specific images.
    
    updated_content = content.replace(
        '<a href="#"><img src="img/facebookLogo.png"',
        '<a href="https://www.facebook.com/profile.php?id=61561157124167&mibextid=ZbWKwL" target="_blank"><img src="img/facebookLogo.png"'
    ).replace(
        '<a href="#"><img src="img/instagramLogo.png"',
        '<a href="https://www.instagram.com/roboaiapaths?igsh=MWdmNXZnYXY0ejdhYw==" target="_blank"><img src="img/instagramLogo.png"'
    )

    if content != updated_content:
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(updated_content)
        print(f"Updated {file_name}")
    else:
        print(f"No changes made to {file_name} (already updated or pattern not found)")
