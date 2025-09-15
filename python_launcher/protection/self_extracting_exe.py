#!/usr/bin/env python3
"""
Self-Extracting Executable Creator
Creates executables that run without disk extraction
"""

import os
import sys
import base64
import zlib
from pathlib import Path

def create_self_extracting_exe(python_code: str, output_path: Path):
    """Create a self-extracting executable that runs from memory"""
    
    # Compress and encode the Python code
    compressed = zlib.compress(python_code.encode('utf-8'))
    encoded = base64.b64encode(compressed).decode('ascii')
    
    # Create self-extracting stub
    stub_code = f'''
import base64
import zlib
import sys

# Embedded compressed Python code
EMBEDDED_CODE = """{encoded}"""

def run_from_memory():
    """Execute Python code from memory without disk extraction"""
    try:
        # Decode and decompress
        compressed_code = base64.b64decode(EMBEDDED_CODE)
        python_code = zlib.decompress(compressed_code).decode('utf-8')
        
        # Execute in memory
        exec(python_code, {{'__name__': '__main__'}})
        
    except Exception as e:
        print(f"Error: {{e}}")
        sys.exit(1)

if __name__ == "__main__":
    run_from_memory()
'''
    
    # Write the self-extracting Python file
    output_path.write_text(stub_code, encoding='utf-8')
    print(f"Self-extracting script created: {output_path}")

def main():
    """Test self-extracting executable creation"""
    # Example Python code to embed
    test_code = '''
print("Hello from memory-only execution!")
print("This code was never written to disk as .pyc files")

import tempfile
import os
from pathlib import Path

# Check if we're running from extracted files
temp_dir = Path(tempfile.gettempdir())
extraction_dirs = list(temp_dir.glob('_MEI*'))

if extraction_dirs:
    print("WARNING: PyInstaller extraction detected!")
    print(f"Extraction directories: {extraction_dirs}")
else:
    print("SUCCESS: Running without disk extraction")

input("Press Enter to exit...")
'''
    
    output_file = Path("test_memory_exe.py")
    create_self_extracting_exe(test_code, output_file)
    
    print(f"Test file created: {output_file}")
    print("You can now run: python test_memory_exe.py")
    print("Or compile with: pyinstaller --onefile test_memory_exe.py")

if __name__ == "__main__":
    main()