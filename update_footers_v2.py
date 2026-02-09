import os

# Define the standard footer with corrected links
standard_footer = """    <footer id="footer">
      <div class="container text-start">
        <div class="row g-4 mb-5">
          <div class="col-lg-3 col-md-6">
            <img src="img/logo.jpeg" class="w-50 mb-4 rounded" alt="Robo AIA">
            <p class="small">Introduction to the exciting fields of robotics coding and AI through comprehensive
              courses.
            </p>
          </div>
          <div class="col-lg-3 col-md-6">
            <h5 class="fw-bold mb-4">Quick Link</h5>
            <ul class="list-unstyled small">
              <li><a href="contact.html">Demo Test</a></li>
              <li><a href="gallery.html">Gallery</a></li>
              <li><a href="index.html#testimonials">Testimonials</a></li>
              <li><a href="contact.html">Contact</a></li>
            </ul>
          </div>
          <div class="col-lg-3 col-md-6">
            <h5 class="fw-bold mb-4">Courses</h5>
            <ul class="list-unstyled small">
              <li><a href="program.html#junior">Junior Level</a></li>
              <li><a href="program.html#expert">Robo Expert</a></li>
              <li><a href="program.html#advance">Robo Advance</a></li>
              <li><a href="program.html#coding">Coding</a></li>
            </ul>
          </div>
          <div class="col-lg-3 col-md-6">
            <h5 class="fw-bold mb-4">Get in touch</h5>
            <p class="small mb-1 text-muted">S7, RPS Savana, Sector 88, Faridabad</p>
            <p class="small mb-1 text-muted">+91 9990911093</p>
            <p class="small mb-3 text-muted">roboaiapaths@gmail.com</p>
            <div class="d-flex">
              <a href="https://www.facebook.com/profile.php?id=61561157124167&mibextid=ZbWKwL" target="_blank"><img
                  src="img/facebookLogo.png" class="social-icon" alt=""></a>
              <a href="https://www.instagram.com/roboaiapaths?igsh=MWdmNXZnYXY0ejdhYw==" target="_blank"><img
                  src="img/instagramLogo.png" class="social-icon" alt=""></a>
            </div>
          </div>
        </div>
        <hr class="opacity-10">
        <p class="text-center small text-muted mb-0">Copyright © 2026 roboaiapaths.com | Aegis of AGPK Academy. All
          Rights Reserved.</p>
      </div>
    </footer>"""

# List of files to update
files_to_update = [
    "index.html",
    "program.html",
    "about.html",
    "kits.html",
    "blog.html",
    "contact.html",
    "gallery.html",
    "partner-with-school.html",
    "hello-world.html",
    "best-platform.html",
    "career-opportunities.html",
    "cart.html",
    "checkout.html"
]

base_dir = r"c:\Users\BHASKAR JOSHI\OneDrive\Desktop\my project\public_html"

def update_footers():
    for filename in files_to_update:
        filepath = os.path.join(base_dir, filename)
        if not os.path.exists(filepath):
            print(f"Skipping {filename}: File not found")
            continue

        try:
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()

            # Find the footer content block using regex or string manipulation
            # We'll use start and end markers for the footer
            start_marker = '<footer id="footer">'
            end_marker = '</footer>'
            
            start_index = content.find(start_marker)
            end_index = content.find(end_marker)
            
            if start_index != -1 and end_index != -1:
                end_index += len(end_marker)
                
                # Replace the old footer with the new standard footer
                new_content = content[:start_index] + standard_footer + content[end_index:]
                
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(new_content)
                
                print(f"Updated footer in {filename}")
            else:
                print(f"Footer markers not found in {filename}")

        except Exception as e:
            print(f"Error processing {filename}: {e}")

if __name__ == "__main__":
    update_footers()
