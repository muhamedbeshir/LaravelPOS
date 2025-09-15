#!/usr/bin/env python3
"""
Customer UUID Utility
Helps customers get their hardware UUID for activation
"""

import sys
from pathlib import Path

# Add parent directory to path
sys.path.insert(0, str(Path(__file__).parent.parent))

def get_system_info():
    """Get comprehensive system information"""
    print("System Information")
    print("=" * 20)
    
    import platform
    import socket
    
    print(f"Computer Name: {socket.gethostname()}")
    print(f"Operating System: {platform.system()} {platform.release()}")
    print(f"Architecture: {platform.architecture()[0]}")
    print(f"Processor: {platform.processor()}")
    print()

def get_motherboard_uuid():
    """Get motherboard UUID using different methods"""
    print("Hardware Identification")
    print("=" * 25)
    
    methods = []
    
    # Method 1: Using WMI (Windows)
    try:
        from main import HardwareManager
        uuid = HardwareManager.get_motherboard_uuid()
        methods.append(("WMI Query", uuid))
        print(f"✅ Motherboard UUID (WMI): {uuid}")
    except Exception as e:
        print(f"❌ WMI method failed: {e}")
    
    # Method 2: Using wmic command
    try:
        import subprocess
        result = subprocess.run([
            'wmic', 'baseboard', 'get', 'serialnumber', '/value'
        ], capture_output=True, text=True, timeout=10)
        
        for line in result.stdout.split('\n'):
            if line.startswith('SerialNumber='):
                uuid = line.split('=', 1)[1].strip()
                if uuid:
                    methods.append(("WMIC Command", uuid))
                    print(f"✅ Motherboard UUID (WMIC): {uuid}")
                break
        else:
            print("❌ WMIC method failed: No serial number found")
    except Exception as e:
        print(f"❌ WMIC method failed: {e}")
    
    # Method 3: Alternative WMI query
    try:
        import wmi
        c = wmi.WMI()
        for board in c.Win32_BaseBoard():
            if hasattr(board, 'SerialNumber') and board.SerialNumber:
                uuid = board.SerialNumber.strip()
                methods.append(("Direct WMI", uuid))
                print(f"✅ Motherboard UUID (Direct WMI): {uuid}")
                break
    except Exception as e:
        print(f"❌ Direct WMI method failed: {e}")
    
    print()
    
    # Show results
    if methods:
        print("Available UUIDs:")
        for i, (method, uuid) in enumerate(methods, 1):
            print(f"{i}. {method}: {uuid}")
        
        if len(set(uuid for _, uuid in methods)) == 1:
            print("\n✅ All methods agree - UUID is consistent")
            return methods[0][1]
        else:
            print("\n⚠️  Different methods returned different UUIDs")
            print("Use the first successful UUID for activation")
            return methods[0][1] if methods else None
    else:
        print("❌ Could not retrieve motherboard UUID")
        print("This system may not support hardware identification")
        return None

def create_customer_info_file(uuid):
    """Create a file with customer information"""
    if not uuid:
        return
    
    info_file = Path(__file__).parent / "customer_info.txt"
    
    content = f"""BulldozerSystem Customer Information
=====================================

Customer UUID: {uuid}

Instructions:
1. Copy the Customer UUID above
2. Send it to your software provider
3. You will receive an activation code
4. Enter the activation code when prompted by the software

System Information:
- Generated on: {Path(__file__).name}
- Please keep this file for your records

Contact your software provider if you need assistance.
"""
    
    info_file.write_text(content)
    print(f"Customer information saved to: {info_file}")

def main():
    """Main function"""
    print("BulldozerSystem Customer UUID Tool")
    print("=" * 40)
    print()
    
    # Get system info
    get_system_info()
    
    # Get UUID
    uuid = get_motherboard_uuid()
    
    if uuid:
        print(f"\n{'='*40}")
        print("CUSTOMER UUID FOR ACTIVATION")
        print(f"{'='*40}")
        print(f"{uuid}")
        print(f"{'='*40}")
        print()
        print("Instructions for customer:")
        print("1. Copy the UUID above")
        print("2. Send it to your software provider")  
        print("3. You will receive an activation code")
        print("4. Enter the code when the software prompts you")
        print()
        
        # Create info file
        create_customer_info_file(uuid)
        
        # Copy to clipboard if possible
        try:
            import pyperclip
            pyperclip.copy(uuid)
            print("✅ UUID copied to clipboard")
        except ImportError:
            print("💡 Install pyperclip to auto-copy UUID: pip install pyperclip")
        except Exception:
            pass
    
    else:
        print("\n❌ Could not retrieve customer UUID")
        print("Possible solutions:")
        print("1. Run as Administrator")
        print("2. Install WMI: pip install WMI")
        print("3. Contact technical support")
    
    print("\nPress Enter to exit...")
    input()

if __name__ == "__main__":
    main()