#!/usr/bin/env python3
"""
Code Obfuscation System
Obfuscates Python source code to prevent reverse engineering
"""

import ast
import os
import sys
import base64
import random
import string
import marshal
import zlib
from pathlib import Path
from typing import Dict, List, Set

class CodeObfuscator:
    """Advanced Python code obfuscator"""
    
    def __init__(self):
        self.name_mapping = {}
        self.string_mapping = {}
        self.encrypted_strings = {}
        self.random_names = set()
        
    def generate_random_name(self, prefix: str = "") -> str:
        """Generate random variable/function names"""
        while True:
            name = prefix + ''.join(random.choices(string.ascii_letters, k=random.randint(8, 16)))
            if name not in self.random_names and not name.startswith('__'):
                self.random_names.add(name)
                return name
    
    def encrypt_string(self, text: str) -> str:
        """Encrypt strings to hide sensitive data"""
        if text in self.encrypted_strings:
            return self.encrypted_strings[text]
        
        # Simple XOR encryption with random key
        key = random.randint(1, 255)
        encrypted = bytes([ord(c) ^ key for c in text])
        encoded = base64.b64encode(encrypted).decode()
        
        var_name = self.generate_random_name("_s")
        decryption_code = f"({repr(encoded)}, {key})"
        
        self.encrypted_strings[text] = f"_decrypt_str{decryption_code}"
        return self.encrypted_strings[text]
    
    def obfuscate_names(self, source_code: str) -> str:
        """Obfuscate variable and function names"""
        try:
            tree = ast.parse(source_code)
            
            class NameObfuscator(ast.NodeTransformer):
                def __init__(self, obfuscator):
                    self.obfuscator = obfuscator
                    self.protected_names = {
                        'main', '__name__', '__main__', '__file__', '__doc__',
                        'print', 'input', 'len', 'str', 'int', 'float', 'bool',
                        'list', 'dict', 'tuple', 'set', 'range', 'enumerate',
                        'open', 'close', 'read', 'write', 'join', 'split',
                        'import', 'from', 'as', 'def', 'class', 'if', 'else',
                        'try', 'except', 'finally', 'with', 'for', 'while',
                        'return', 'yield', 'break', 'continue', 'pass',
                        # Keep important module names
                        'os', 'sys', 'subprocess', 'pathlib', 'Path',
                        'webview', 'tkinter', 'winreg', 'wmi', 'psutil'
                    }
                
                def visit_Name(self, node):
                    if (node.id not in self.protected_names and 
                        not node.id.startswith('_') and
                        len(node.id) > 2):
                        
                        if node.id not in self.obfuscator.name_mapping:
                            self.obfuscator.name_mapping[node.id] = self.obfuscator.generate_random_name()
                        node.id = self.obfuscator.name_mapping[node.id]
                    
                    return self.generic_visit(node)
                
                def visit_FunctionDef(self, node):
                    if (node.name not in self.protected_names and
                        not node.name.startswith('_') and
                        len(node.name) > 2):
                        
                        if node.name not in self.obfuscator.name_mapping:
                            self.obfuscator.name_mapping[node.name] = self.obfuscator.generate_random_name()
                        node.name = self.obfuscator.name_mapping[node.name]
                    
                    return self.generic_visit(node)
                
                def visit_ClassDef(self, node):
                    if (node.name not in self.protected_names and
                        not node.name.startswith('_')):
                        
                        if node.name not in self.obfuscator.name_mapping:
                            self.obfuscator.name_mapping[node.name] = self.obfuscator.generate_random_name()
                        node.name = self.obfuscator.name_mapping[node.name]
                    
                    return self.generic_visit(node)
            
            obfuscated_tree = NameObfuscator(self).visit(tree)
            
            # Use ast.unparse if available (Python 3.9+), otherwise use ast_unparse
            try:
                return ast.unparse(obfuscated_tree)
            except AttributeError:
                # Fallback for Python < 3.9
                try:
                    import ast_unparse
                    return ast_unparse.unparse(obfuscated_tree)
                except ImportError:
                    # Final fallback - return original code
                    print("Warning: ast.unparse not available, skipping name obfuscation")
                    return source_code
            
        except Exception as e:
            print(f"Name obfuscation failed: {e}")
            return source_code
    
    def obfuscate_strings(self, source_code: str) -> str:
        """Obfuscate string literals"""
        lines = source_code.split('\n')
        obfuscated_lines = []
        
        for line in lines:
            # Skip import lines and comments
            if line.strip().startswith(('import ', 'from ', '#')):
                obfuscated_lines.append(line)
                continue
            
            # Find string literals and obfuscate them
            import re
            
            # Find string patterns
            string_patterns = [
                r'"([^"\\]|\\.)*"',  # Double quoted strings
                r"'([^'\\]|\\.)*'"   # Single quoted strings
            ]
            
            modified_line = line
            for pattern in string_patterns:
                matches = re.finditer(pattern, modified_line)
                for match in reversed(list(matches)):
                    original_string = match.group()
                    string_content = original_string[1:-1]  # Remove quotes
                    
                    # Skip very short strings and special cases
                    if (len(string_content) < 4 or 
                        string_content.startswith(('__', 'utf-', 'cp125')) or
                        string_content in ['r', 'w', 'a', 'rb', 'wb']):
                        continue
                    
                    encrypted_call = self.encrypt_string(string_content)
                    modified_line = (modified_line[:match.start()] + 
                                   encrypted_call + 
                                   modified_line[match.end():])
            
            obfuscated_lines.append(modified_line)
        
        return '\n'.join(obfuscated_lines)
    
    def add_decryption_function(self, source_code: str) -> str:
        """Add string decryption function"""
        decryption_func = '''
import base64

def _decrypt_str(encrypted_data, key):
    """Decrypt obfuscated strings"""
    try:
        encoded, xor_key = encrypted_data
        decoded = base64.b64decode(encoded)
        return ''.join(chr(b ^ xor_key) for b in decoded)
    except:
        return ""

'''
        return decryption_func + source_code
    
    def add_control_flow_obfuscation(self, source_code: str) -> str:
        """Add control flow obfuscation"""
        # Add dummy code blocks and fake functions
        dummy_code = f'''
# Control flow obfuscation
import random
import time

def {self.generate_random_name()}():
    """Fake function to confuse analyzers"""
    x = random.randint(1, 1000)
    for i in range(x % 10):
        if i % 2 == 0:
            time.sleep(0.001)
    return x

def {self.generate_random_name()}():
    """Another fake function"""
    data = [random.randint(1, 100) for _ in range(50)]
    return sum(data) % 1000

# Create fake variables
{self.generate_random_name()} = {random.randint(1000, 9999)}
{self.generate_random_name()} = "{self.generate_random_name()}"
{self.generate_random_name()} = [1, 2, 3, 4, 5]

'''
        return dummy_code + source_code
    
    def obfuscate_file(self, input_file: Path, output_file: Path):
        """Obfuscate a single Python file"""
        print(f"Obfuscating {input_file}...")
        
        # Read source code
        source_code = input_file.read_text(encoding='utf-8')
        
        # Apply obfuscation layers
        obfuscated = source_code
        
        # 1. Add decryption function first
        obfuscated = self.add_decryption_function(obfuscated)
        
        # 2. Obfuscate strings
        obfuscated = self.obfuscate_strings(obfuscated)
        
        # 3. Obfuscate names
        obfuscated = self.obfuscate_names(obfuscated)
        
        # 4. Add control flow obfuscation
        obfuscated = self.add_control_flow_obfuscation(obfuscated)
        
        # 5. Add anti-debugging
        obfuscated = self.add_anti_debugging(obfuscated)
        
        # Write obfuscated code
        output_file.parent.mkdir(parents=True, exist_ok=True)
        output_file.write_text(obfuscated, encoding='utf-8')
        
        print(f"Obfuscated file saved to {output_file}")
    
    def add_anti_debugging(self, source_code: str) -> str:
        """Add anti-debugging and anti-analysis measures"""
        anti_debug_code = f'''
# Anti-debugging and analysis protection
import os
import sys
import time
import threading
import subprocess
from pathlib import Path

def {self.generate_random_name()}():
    """Anti-debugging checks"""
    try:
        # Check for debugger
        if hasattr(sys, 'gettrace') and sys.gettrace() is not None:
            os._exit(1)
        
        # Check for common analysis tools
        analysis_tools = ['ida', 'ollydbg', 'x64dbg', 'windbg', 'cheat engine']
        for proc in os.popen('tasklist').read().lower():
            for tool in analysis_tools:
                if tool in proc:
                    os._exit(1)
        
        # Timing check
        start_time = time.time()
        sum(range(1000))
        if time.time() - start_time > 0.1:  # Too slow, might be debugging
            os._exit(1)
        
        # VM detection
        vm_indicators = [
            'VBOX', 'VMWARE', 'VIRTUALBOX', 'QEMU'
        ]
        system_info = os.popen('systeminfo').read().upper()
        for indicator in vm_indicators:
            if indicator in system_info:
                time.sleep(random.randint(5, 30))  # Slow down analysis
        
    except:
        pass

def {self.generate_random_name()}():
    """Continuous anti-debugging monitor"""
    while True:
        try:
            if hasattr(sys, 'gettrace') and sys.gettrace() is not None:
                os._exit(1)
            time.sleep(random.uniform(1, 5))
        except:
            break

# Start anti-debugging thread
{self.generate_random_name()}()
threading.Thread(target={self.generate_random_name()}, daemon=True).start()

'''
        return anti_debug_code + source_code

def obfuscate_project(source_dir: Path, output_dir: Path):
    """Obfuscate entire project"""
    obfuscator = CodeObfuscator()
    
    # Create output directory
    output_dir.mkdir(parents=True, exist_ok=True)
    
    # Copy non-Python files
    for item in source_dir.rglob('*'):
        if item.is_file() and item.suffix not in ['.py', '.pyc', '.pyo']:
            relative_path = item.relative_to(source_dir)
            output_file = output_dir / relative_path
            output_file.parent.mkdir(parents=True, exist_ok=True)
            
            try:
                if item.suffix in ['.txt', '.md', '.json', '.yaml', '.yml']:
                    # Copy text files as-is
                    output_file.write_text(item.read_text(encoding='utf-8'), encoding='utf-8')
                else:
                    # Copy binary files
                    output_file.write_bytes(item.read_bytes())
            except Exception as e:
                print(f"Warning: Failed to copy {item}: {e}")
    
    # Obfuscate Python files
    python_files = list(source_dir.rglob('*.py'))
    for py_file in python_files:
        relative_path = py_file.relative_to(source_dir)
        output_file = output_dir / relative_path
        
        try:
            obfuscator.obfuscate_file(py_file, output_file)
        except Exception as e:
            print(f"Failed to obfuscate {py_file}: {e}")
            # Copy original file if obfuscation fails
            output_file.parent.mkdir(parents=True, exist_ok=True)
            output_file.write_text(py_file.read_text(encoding='utf-8'), encoding='utf-8')

def main():
    """Main obfuscation function"""
    import argparse
    
    parser = argparse.ArgumentParser(description="Obfuscate Python source code")
    parser.add_argument("source", help="Source directory or file")
    parser.add_argument("output", help="Output directory or file")
    
    args = parser.parse_args()
    
    source_path = Path(args.source)
    output_path = Path(args.output)
    
    if source_path.is_file():
        # Single file obfuscation
        obfuscator = CodeObfuscator()
        obfuscator.obfuscate_file(source_path, output_path)
    else:
        # Directory obfuscation
        obfuscate_project(source_path, output_path)
    
    print("Obfuscation completed!")

if __name__ == "__main__":
    main()