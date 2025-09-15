#!/usr/bin/env python3
"""
Complete Build Example
Shows how to build the complete BulldozerSystem from start to finish
"""

import os
import sys
from pathlib import Path

# Add parent directory to path
sys.path.insert(0, str(Path(__file__).parent.parent))

def main():
    """Complete build example"""
    print("BulldozerSystem Complete Build Example")
    print("=" * 40)
    
    # Configuration - UPDATE THESE PATHS
    LARAVEL_PROJECT = r"C:\Users\Muhamed beshir\Desktop\Bulldozer-Market"
    PHP_RUNTIME = r"C:\php"  # Path to your PHP installation
    OUTPUT_DIR = Path(__file__).parent.parent / "dist"
    
    print(f"Laravel Project: {LARAVEL_PROJECT}")
    print(f"PHP Runtime: {PHP_RUNTIME}")
    print(f"Output Directory: {OUTPUT_DIR}")
    print()
    
    # Verify paths exist
    if not Path(LARAVEL_PROJECT).exists():
        print(f"Error: Laravel project not found at {LARAVEL_PROJECT}")
        print("Update the LARAVEL_PROJECT path in this script")
        return
    
    if not Path(PHP_RUNTIME).exists():
        print(f"Error: PHP runtime not found at {PHP_RUNTIME}")
        print("Update the PHP_RUNTIME path in this script")
        return
    
    if not (Path(LARAVEL_PROJECT) / "artisan").exists():
        print(f"Error: Not a valid Laravel project (artisan not found)")
        return
    
    # Step 1: Generate new keys (optional)
    print("Step 1: Generate cryptographic keys")
    response = input("Generate new cryptographic keys? (y/N): ")
    if response.lower() == 'y':
        try:
            from generate_keys import main as generate_keys_main
            generate_keys_main()
        except Exception as e:
            print(f"Key generation failed: {e}")
            return
    else:
        print("Using existing keys")
    print()
    
    # Step 2: Build the application
    print("Step 2: Building application")
    try:
        from build import BuildSystem
        
        builder = BuildSystem(LARAVEL_PROJECT, PHP_RUNTIME, str(OUTPUT_DIR))
        
        if builder.build():
            print("\n" + "=" * 40)
            print("BUILD COMPLETED SUCCESSFULLY!")
            print(f"Output files in: {OUTPUT_DIR}")
            
            # List output files
            print("\nFiles created:")
            for file in OUTPUT_DIR.iterdir():
                size = file.stat().st_size / (1024 * 1024) if file.is_file() else 0
                print(f"  - {file.name} ({size:.1f} MB)")
            
            print("\nNext steps:")
            print("1. Test the executable on your system")
            print("2. Use keygen.py to generate activation codes for customers")
            print("3. Distribute the files to customers")
            
        else:
            print("Build failed - check error messages above")
            
    except Exception as e:
        print(f"Build failed: {e}")
        import traceback
        traceback.print_exc()

if __name__ == "__main__":
    main()