#!/usr/bin/env python3
"""
Key Pair Generator - Python equivalent of generate_keys.rs
Generates new Ed25519 key pairs and updates source files
"""

import os
import sys
from pathlib import Path
from cryptography.hazmat.primitives.asymmetric.ed25519 import Ed25519PrivateKey

def update_key_in_file(file_path: Path, key_name: str, new_key: bytes) -> bool:
    """Update key bytes in Python source file"""
    try:
        # Read file content
        content = file_path.read_text(encoding='utf-8')
        
        # Convert key bytes to Python list representation
        key_bytes_str = str(list(new_key))
        
        # Find and replace key line
        lines = content.split('\n')
        key_found = False
        
        for i, line in enumerate(lines):
            stripped = line.strip()
            # Look for lines that define the key constant
            if (stripped.startswith(f'{key_name} = bytes([') or 
                stripped.startswith(f'PRIVATE_KEY_BYTES = bytes([') or
                stripped.startswith(f'PUBLIC_KEY_BYTES = bytes([')):
                
                # Replace with new key
                indent = len(line) - len(line.lstrip())
                new_line = ' ' * indent + f'{key_name} = bytes({key_bytes_str})'
                lines[i] = new_line
                key_found = True
                print(f"Updated {key_name} in {file_path}")
                break
        
        if key_found:
            # Write updated content
            updated_content = '\n'.join(lines)
            file_path.write_text(updated_content, encoding='utf-8')
            return True
        else:
            print(f"Warning: Key '{key_name}' not found in {file_path}")
            return False
            
    except Exception as e:
        print(f"Error updating {file_path}: {e}")
        return False

def generate_new_keypair():
    """Generate new Ed25519 key pair"""
    print("Generating new Ed25519 key pair...")
    
    # Generate private key
    private_key = Ed25519PrivateKey.generate()
    public_key = private_key.public_key()
    
    # Get raw bytes
    private_key_bytes = private_key.private_bytes_raw()
    public_key_bytes = public_key.public_bytes_raw()
    
    print("Successfully generated new key pair")
    print(f"Private key: {len(private_key_bytes)} bytes")
    print(f"Public key: {len(public_key_bytes)} bytes")
    
    return private_key_bytes, public_key_bytes

def update_source_files(private_key_bytes: bytes, public_key_bytes: bytes):
    """Update source files with new key pair"""
    script_dir = Path(__file__).parent
    
    # Update keygen.py with private key
    keygen_path = script_dir / "keygen.py"
    if keygen_path.exists():
        if update_key_in_file(keygen_path, "PRIVATE_KEY_BYTES", private_key_bytes):
            print(f"Updated private key in {keygen_path}")
        else:
            print(f"Failed to update private key in {keygen_path}")
    else:
        print(f"Warning: {keygen_path} not found")
    
    # Update main.py with public key
    main_path = script_dir / "main.py"
    if main_path.exists():
        # Need to update the load_public_key method in CryptoManager
        try:
            content = main_path.read_text(encoding='utf-8')
            
            # Find the public key bytes line in load_public_key method
            lines = content.split('\n')
            for i, line in enumerate(lines):
                if 'public_key_bytes = bytes([' in line:
                    # Replace with new public key
                    indent = len(line) - len(line.lstrip())
                    key_list_str = str(list(public_key_bytes))
                    new_line = ' ' * indent + f'public_key_bytes = bytes({key_list_str})'
                    lines[i] = new_line
                    
                    # Handle multi-line key if needed
                    j = i + 1
                    while j < len(lines) and not lines[j].strip().endswith('])'):
                        lines[j] = ''  # Clear continuation lines
                        j += 1
                    if j < len(lines) and lines[j].strip().endswith('])'):
                        lines[j] = ''  # Clear final line
                    
                    updated_content = '\n'.join(lines)
                    main_path.write_text(updated_content, encoding='utf-8')
                    print(f"Updated public key in {main_path}")
                    break
            else:
                print(f"Warning: Could not find public key bytes in {main_path}")
                
        except Exception as e:
            print(f"Error updating {main_path}: {e}")
    else:
        print(f"Warning: {main_path} not found")

def create_public_key_file(public_key_bytes: bytes):
    """Create standalone public key file (equivalent to public_key.rs)"""
    script_dir = Path(__file__).parent
    public_key_path = script_dir / "public_key.py"
    
    key_list_str = str(list(public_key_bytes))
    
    content = f'''# This file is auto-generated by generate_keys.py
# It contains the public half of the Ed25519 key-pair used for activation
# verification. DO NOT edit manually.

PUBLIC_KEY_BYTES = bytes({key_list_str})
'''
    
    public_key_path.write_text(content, encoding='utf-8')
    print(f"Created {public_key_path}")

def main():
    """Main entry point"""
    print("Ed25519 Key Pair Generator for BulldozerSystem")
    print("=" * 50)
    
    # Confirm action
    response = input("This will generate new keys and update source files. Continue? (y/N): ")
    if response.lower() != 'y':
        print("Operation cancelled")
        return
    
    try:
        # Generate new key pair
        private_key_bytes, public_key_bytes = generate_new_keypair()
        
        # Update source files
        print("\nUpdating source files...")
        update_source_files(private_key_bytes, public_key_bytes)
        
        # Create standalone public key file
        create_public_key_file(public_key_bytes)
        
        print("\nKey generation complete!")
        print("=" * 50)
        print("IMPORTANT NOTES:")
        print("1. The private key has been updated in keygen.py")
        print("2. The public key has been updated in main.py")
        print("3. A public_key.py file has been created")
        print("4. Keep the private key secure and do not distribute it")
        print("5. You can now use keygen.py to generate activation codes")
        print("6. Recompile/repackage the application to use the new keys")
        
    except Exception as e:
        print(f"Error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()