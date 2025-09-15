#!/usr/bin/env python3
"""
Activation System Test
Test the activation code generation and verification
"""

import sys
from pathlib import Path

# Add parent directory to path
sys.path.insert(0, str(Path(__file__).parent.parent))

def test_activation_system():
    """Test the complete activation system"""
    print("BulldozerSystem Activation Test")
    print("=" * 35)
    
    try:
        # Import required modules
        from main import CryptoManager, HardwareManager
        from keygen import generate_activation_code
        
        print("Step 1: Getting hardware UUID...")
        try:
            uuid = HardwareManager.get_motherboard_uuid()
            print(f"Hardware UUID: {uuid}")
        except Exception as e:
            print(f"Error getting hardware UUID: {e}")
            print("Using test UUID instead")
            uuid = "TEST-UUID-12345"
        print()
        
        print("Step 2: Generating activation code...")
        activation_code = generate_activation_code(uuid)
        if activation_code:
            print(f"Generated activation code: {activation_code}")
        else:
            print("Failed to generate activation code")
            return
        print()
        
        print("Step 3: Verifying activation code...")
        is_valid = CryptoManager.verify_activation_code(uuid, activation_code)
        print(f"Verification result: {'VALID' if is_valid else 'INVALID'}")
        print()
        
        if is_valid:
            print("✅ Activation system test PASSED")
            print("The activation system is working correctly!")
        else:
            print("❌ Activation system test FAILED")
            print("Check your key configuration")
        
        print()
        print("Additional tests:")
        
        # Test with wrong UUID
        print("Testing with wrong UUID...")
        wrong_result = CryptoManager.verify_activation_code("WRONG-UUID", activation_code)
        print(f"Wrong UUID result: {'INVALID (good!)' if not wrong_result else 'VALID (bad!)'}")
        
        # Test with wrong code
        print("Testing with wrong code...")
        wrong_code_result = CryptoManager.verify_activation_code(uuid, "wrong-code")
        print(f"Wrong code result: {'INVALID (good!)' if not wrong_code_result else 'VALID (bad!)'}")
        
    except ImportError as e:
        print(f"Import error: {e}")
        print("Make sure all dependencies are installed")
        print("Run: pip install -r requirements.txt")
    except Exception as e:
        print(f"Test failed: {e}")
        import traceback
        traceback.print_exc()

def interactive_test():
    """Interactive activation test"""
    print("\nInteractive Activation Test")
    print("-" * 30)
    
    try:
        from main import CryptoManager
        from keygen import generate_activation_code
        
        # Get UUID from user
        uuid = input("Enter customer UUID: ").strip()
        if not uuid:
            print("No UUID provided")
            return
        
        # Generate code
        print(f"\nGenerating activation code for: {uuid}")
        code = generate_activation_code(uuid)
        print(f"Activation code: {code}")
        
        # Verify
        is_valid = CryptoManager.verify_activation_code(uuid, code)
        print(f"Verification: {'VALID' if is_valid else 'INVALID'}")
        
        # Test customer workflow
        print("\n--- Customer Workflow Test ---")
        entered_code = input("Enter the activation code (as customer would): ").strip()
        customer_valid = CryptoManager.verify_activation_code(uuid, entered_code)
        print(f"Customer verification: {'SUCCESS' if customer_valid else 'FAILED'}")
        
    except Exception as e:
        print(f"Interactive test failed: {e}")

def main():
    """Main test function"""
    print("Choose test mode:")
    print("1. Automatic test")
    print("2. Interactive test")
    print("3. Both")
    
    choice = input("Enter choice (1-3): ").strip()
    
    if choice in ['1', '3']:
        test_activation_system()
    
    if choice in ['2', '3']:
        interactive_test()
    
    if choice not in ['1', '2', '3']:
        print("Invalid choice")

if __name__ == "__main__":
    main()