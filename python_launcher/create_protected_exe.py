#!/usr/bin/env python3
"""
Quick Protected Executable Creator
Simple script to create a fully protected BulldozerSystem executable
"""

import os
import sys
from pathlib import Path

def main():
    print("[PROTECTED] BulldozerSystem Protected Executable Creator")
    print("=" * 55)
    print()
    
    # Check if we're in the right directory
    if not Path("protection/protected_build.py").exists():
        print("[ERROR] Run this script from the python_launcher directory")
        print("   cd python_launcher")
        print("   python create_protected_exe.py")
        return
    
    # Get paths from user
    print("[CONFIG] Configuration")
    print("-" * 20)
    
    # Laravel project path
    while True:
        laravel_path = input("Enter Laravel project path: ").strip().strip('"')
        if not laravel_path:
            print("[ERROR] Path cannot be empty")
            continue
        
        laravel_path = Path(laravel_path)
        if not laravel_path.exists():
            print(f"[ERROR] Directory not found: {laravel_path}")
            continue
        
        if not (laravel_path / "artisan").exists():
            print(f"[ERROR] Not a Laravel project (artisan file not found)")
            continue
        
        print(f"[OK] Laravel project: {laravel_path}")
        break
    
    # PHP runtime path
    while True:
        php_path = input("Enter PHP runtime path: ").strip().strip('"')
        if not php_path:
            print("[ERROR] Path cannot be empty")
            continue
        
        php_path = Path(php_path)
        if not php_path.exists():
            print(f"[ERROR] Directory not found: {php_path}")
            continue
        
        # Look for php.exe
        php_exe_found = False
        for root, dirs, files in os.walk(php_path):
            if "php.exe" in files:
                php_exe_found = True
                break
        
        if not php_exe_found:
            print(f"[ERROR] php.exe not found in {php_path}")
            continue
        
        print(f"[OK] PHP runtime: {php_path}")
        break
    
    # Protection level
    print()
    print("[PROTECT] Protection Level")
    print("-" * 20)
    print("1. Basic   - Source obfuscation, basic anti-debugging")
    print("2. Medium  - + UPX compression, VM detection")
    print("3. High    - + VMProtect, multi-layer encryption (recommended)")
    
    while True:
        choice = input("Select protection level (1-3) [3]: ").strip()
        if not choice:
            choice = "3"
        
        if choice in ["1", "2", "3"]:
            protection_levels = {"1": "basic", "2": "medium", "3": "high"}
            protection_level = protection_levels[choice]
            print(f"[OK] Protection level: {protection_level}")
            break
        else:
            print("[ERROR] Invalid choice. Enter 1, 2, or 3")
    
    # Output directory
    output_dir = Path("dist")
    print(f"[OK] Output directory: {output_dir.absolute()}")
    print()
    
    # Confirmation
    print("[SUMMARY] Build Summary")
    print("-" * 20)
    print(f"Laravel Project: {laravel_path}")
    print(f"PHP Runtime: {php_path}")
    print(f"Protection Level: {protection_level.upper()}")
    print(f"Output Directory: {output_dir.absolute()}")
    print()
    
    confirm = input("Start protected build? (y/N): ").strip().lower()
    if confirm != 'y':
        print("[CANCEL] Build cancelled")
        return
    
    print()
    print("[BUILD] Starting Protected Build Process...")
    print("=" * 55)
    
    # Run the protected build
    try:
        import subprocess
        import sys
        
        cmd = [
            sys.executable,
            "protection/protected_build.py",
            str(laravel_path),
            str(php_path),
            "--output", str(output_dir),
            "--protection", protection_level
        ]
        
        print(f"Running: {' '.join(cmd)}")
        print()
        
        # Run with real-time output
        process = subprocess.Popen(
            cmd,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            bufsize=1,
            universal_newlines=True
        )
        
        # Print output in real-time
        for line in iter(process.stdout.readline, ''):
            print(line.rstrip())
        
        process.wait()
        
        if process.returncode == 0:
            print()
            print("[SUCCESS] Build Complete!")
            print("=" * 55)
            print("Your protected executable has been created!")
            print()
            print(f"[OUTPUT] Files location: {output_dir.absolute()}")
            print()
            
            # List created files
            if output_dir.exists():
                print("[FILES] Created files:")
                for file in output_dir.iterdir():
                    if file.is_file():
                        size = file.stat().st_size / (1024 * 1024)
                        print(f"   - {file.name} ({size:.1f} MB)")
            
            print()
            print("[PROTECT] Protection Features Applied:")
            print("   [OK] Source code obfuscation")
            print("   [OK] String encryption") 
            print("   [OK] Anti-debugging protection")
            print("   [OK] VM detection")
            print("   [OK] Runtime integrity checking")
            print("   [OK] Self-deletion on tampering")
            
            if protection_level in ["medium", "high"]:
                print("   [OK] Binary compression (UPX)")
            
            if protection_level == "high":
                print("   [OK] Advanced binary protection")
                print("   [OK] Multi-layer encryption")
            
            print()
            print("[NEXT] Next Steps:")
            print("   1. Test the executable on your system")
            print("   2. Use keygen.py to generate activation codes")
            print("   3. Distribute to customers")
            print("   4. Monitor for any compatibility issues")
            
        else:
            print()
            print("[FAILED] BUILD FAILED")
            print("=" * 55)
            print("The build process encountered errors.")
            print("Please check the output above for details.")
            print()
            print("Common solutions:")
            print("   - Install missing dependencies: pip install -r requirements.txt")
            print("   - Check file paths are correct")
            print("   - Run as Administrator if needed")
            print("   - Install UPX for better protection")
        
    except Exception as e:
        print(f"[ERROR] Error running build: {e}")
        print()
        print("Make sure you have all dependencies installed:")
        print("   pip install -r requirements.txt")

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n[CANCEL] Build cancelled by user")
    except Exception as e:
        print(f"\n[ERROR] Unexpected error: {e}")
        import traceback
        traceback.print_exc()