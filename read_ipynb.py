import json
import sys

path = 'C:\\Kuliah\\Semester 4\\Kecerdasan Mesin\\DHC\\Models\\DidactionModel_01.ipynb'
try:
    with open(path, 'r', encoding='utf-8') as f:
        data = json.load(f)
    for i, cell in enumerate(data.get('cells', [])):
        cell_type = cell.get('cell_type', '')
        source = "".join(cell.get('source', []))
        print(f"Cell {i}: {cell_type} - {source[:150].replace(chr(10), ' ')}")
except Exception as e:
    print(f"Error: {e}")
