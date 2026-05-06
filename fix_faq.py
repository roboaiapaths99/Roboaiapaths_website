#!/usr/bin/env python3
"""Fix the malformed Q5 in index.html second FAQ schema"""

with open('index.html', 'r', encoding='utf-8') as f:
    lines = f.readlines()

# Find and fix the line with the backtick-n issue
output = []
i = 0
while i < len(lines):
    line = lines[i]
    
    # Check if this is the broken Q5 line
    if '"name": "@id": "https://roboaiapaths.com/#faq-q5"' in line and '`n' in line:
        # Replace with correct lines
        output.append('      "@id": "https://roboaiapaths.com/#faq-q5",\n')
        output.append('      "name": "What age group is RoboAIAPaths ideal for?",\n')
        i += 1
    else:
        output.append(line)
        i += 1

# Write back
with open('index.html', 'w', encoding='utf-8') as f:
    f.writelines(output)

print("✓ Fixed malformed Q5 in second FAQ schema")
