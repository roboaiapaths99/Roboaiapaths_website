import shutil
import os

source_dir = r"C:\Users\BHASKAR JOSHI\.gemini\antigravity\brain\tempmediaStorage"
dest_dir = r"c:\Users\BHASKAR JOSHI\OneDrive\Desktop\my project\public_html\img\new_assets"

# Ensure destination exists
os.makedirs(dest_dir, exist_ok=True)

# Map original filenames to new names
# I'll use the list I got earlier
files_to_move = {
    "media__1770634395451.png": "hero_family_1.png",
    "media__1770634399147.png": "hero_family_2.png",
    "media__1770634404884.jpg": "student_boy.jpg",
    "media__1770634408013.jpg": "student_girls.jpg",
    "media__1770634411248.jpg": "kit_showcase.jpg"
}

for src_name, dest_name in files_to_move.items():
    src_path = os.path.join(source_dir, src_name)
    dest_path = os.path.join(dest_dir, dest_name)
    
    try:
        if os.path.exists(src_path):
            shutil.copy2(src_path, dest_path)
            print(f"Copied {src_name} to {dest_name}")
        else:
            print(f"Source file not found: {src_name}")
    except Exception as e:
        print(f"Error copying {src_name}: {e}")
