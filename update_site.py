import os

files = [
    "index.html",
    "about.html",
    "program.html",
    "blog.html",
    "contact.html",
    "join-as-a-trainer.html",
    "partner-with-school.html",
    "gallery.html"
]

base_path = r"c:\Users\BHASKAR JOSHI\OneDrive\Desktop\my project\public_html"

cart_item = """          <li class="nav-item">
              <a href="cart.html" class="nav-link position-relative">
                  <i class="fas fa-shopping-cart"></i>
                  <span id="cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none; font-size: 0.6rem;">
                    0
                  </span>
              </a>
          </li>
"""

script_tag = '  <script src="js/cart.js"></script>\n'

for file_name in files:
    file_path = os.path.join(base_path, file_name)
    if not os.path.exists(file_path):
        print(f"Skipping {file_name} (not found)")
        continue

    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Uncomment Kits
    if '<!-- <li class="nav-item"><a href="kits.html" class="nav-link">Kits</a></li> -->' in content:
        content = content.replace(
            '<!-- <li class="nav-item"><a href="kits.html" class="nav-link">Kits</a></li> -->',
            '<li class="nav-item"><a href="kits.html" class="nav-link">Kits</a></li>'
        )
        print(f"Uncommented Kits in {file_name}")
    
    # 2. Add Cart Icon (if not present)
    if 'cart.html' not in content:
        target = '<li class="nav-item ms-lg-3"><a href="contact.html" class="btn btn-primary btn-tech px-4">Enroll Now</a></li>'
        if target in content:
            content = content.replace(target, cart_item + target)
            print(f"Added Cart icon to {file_name}")
        else:
            print(f"Could not find insert point for Cart in {file_name}")
    
    # 3. Add Script
    if 'src="js/cart.js"' not in content:
        if '</body>' in content:
            content = content.replace('</body>', script_tag + '</body>')
            print(f"Added script to {file_name}")
        else:
            print(f"Could not find </body> in {file_name}")

    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)

print("Batch update complete.")
