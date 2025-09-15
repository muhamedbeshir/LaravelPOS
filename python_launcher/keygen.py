#!/usr/bin/env python3
"""
Activation Code Generator - Python equivalent of keygen.rs
Generates activation codes for customer hardware UUIDs
"""

import sys
import base64
import hashlib
from cryptography.hazmat.primitives import hashes
from cryptography.hazmat.primitives.asymmetric.ed25519 import Ed25519PrivateKey

# Private key bytes (will be updated by generate_keys.py)
PRIVATE_KEY_BYTES = bytes([249, 172, 162, 4, 211, 230, 110, 196, 144, 229, 10, 77, 60, 192, 33, 94, 51, 241, 63, 20, 100, 143, 74, 14, 24, 36, 158, 134, 180, 187, 9, 213])

def generate_activation_code(uuid: str) -> str:
    """Generate activation code for given UUID"""
    try:
        # Load private key
        private_key = Ed25519PrivateKey.from_private_bytes(PRIVATE_KEY_BYTES)
        
        # Hash the UUID (same method as main.py)
        digest = hashes.Hash(hashes.SHA256())
        digest.update(uuid.strip().encode())
        message = digest.finalize()
        
        # Sign the message
        signature = private_key.sign(message)
        
        # Return base64 encoded signature
        return base64.b64encode(signature).decode('ascii')
        
    except Exception as e:
        print(f"Error generating activation code: {e}")
        return None

def main():
    """Main entry point"""
    print("--- Bulldozer Market Key Generator ---")
    print("Enter the customer's Motherboard UUID and press Enter:")
    
    try:
        # Get UUID from user
        uuid_input = input().strip()
        
        if not uuid_input:
            print("Error: No UUID provided")
            return
        
        # Generate activation code
        activation_code = generate_activation_code(uuid_input)
        
        if activation_code:
            print("\n" + "-" * 40)
            print(f"Activation Code: {activation_code}")
            print("-" * 40)
            print(f"\nCustomer UUID: {uuid_input}")
            print("Copy the activation code to the customer.")
        else:
            print("Failed to generate activation code")
            return
    
    except KeyboardInterrupt:
        print("\nOperation cancelled")
        return
    except Exception as e:
        print(f"Error: {e}")
        return
    
    # Wait for user acknowledgment
    print("\nPress Enter to exit.")
    try:
        input()
    except KeyboardInterrupt:
        pass

if __name__ == "__main__":
    main()