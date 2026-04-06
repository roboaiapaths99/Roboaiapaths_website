import os
import re

root_dir = r"c:\Users\BHASKAR JOSHI\OneDrive\Desktop\my project\public_html"
correct_name = "RoboAIAPaths"

def fix_content(content):
    # 1. Fix variations with spaces
    content = re.sub(r"Robo AI Paths", correct_name, content, flags=re.IGNORECASE)
    content = re.sub(r"Robo AIA Paths", correct_name, content, flags=re.IGNORECASE)
    content = re.sub(r"Robo AIA", correct_name, content, flags=re.IGNORECASE) # Catching "Robo AIA" logo alt texts
    
    # 2. Fix the specific "roboaipaths" (missing second A)
    # We want to replace "roboaipaths" but NOT "roboaiapaths"
    # Logic: if "roboai" is followed by "paths" but NO "a" in between.
    content = re.sub(r"roboai(?!a)paths", correct_name, content, flags=re.IGNORECASE)
    
    # 3. Fix "RoboAIPaths" (no spaces, missing A)
    content = re.sub(r"RoboAIPaths", correct_name, content, flags=re.IGNORECASE)

    # 4. Handle possible typo "RoboAIPathes"
    content = re.sub(r"RoboAIPathes", correct_name, content, flags=re.IGNORECASE)

    return content

count = 0
for root, dirs, files in os.walk(root_dir):
    if ".git" in dirs:
        dirs.remove(".git")
    for file in files:
        if file.endswith((".html", ".js", ".css", ".php", ".txt", ".xml", ".sql")):
            file_path = os.path.join(root, file)
            try:
                with open(file_path, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                new_content = fix_content(content)
                
                if "Robo AI Paths" in content or "Robo AIA Paths" in content or "roboaipaths" in content.lower():
                     pass # debugging help
                
                if new_content != content:
                    with open(file_path, 'w', encoding='utf-8') as f:
                        f.write(new_content)
                    print(f"Fixed branding in: {file_path}")
                    count += 1
            except Exception as e:
                print(f"Error processing {file_path}: {e}")

print(f"Done. Fixed {count} files.")
