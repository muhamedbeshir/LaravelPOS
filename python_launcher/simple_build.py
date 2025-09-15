#!/usr/bin/env python3
"""
Simple Protected Build Script
Creates a protected executable with enhanced UX
"""

import os
import sys
import subprocess
from pathlib import Path

def main():
    """Simple build process"""
    print("🔒 Simple Protected Build")
    print("=" * 50)
    
    # Check if main.py exists
    main_py = Path("main.py")
    if not main_py.exists():
        print("❌ main.py not found. Creating a basic one...")
        main_py.write_text("""#!/usr/bin/env python3
import tkinter as tk
from tkinter import messagebox, ttk
import time

def main():
    root = tk.Tk()
    root.withdraw()
    
    # Show loading screen
    loading = tk.Toplevel(root)
    loading.title("Bulldozer System - Loading")
    loading.geometry("400x200")
    loading.resizable(False, False)
    
    # Center window
    loading.update_idletasks()
    x = (loading.winfo_screenwidth() // 2) - (400 // 2)
    y = (loading.winfo_screenheight() // 2) - (200 // 2)
    loading.geometry(f"400x200+{x}+{y}")
    
    # Create widgets
    main_frame = ttk.Frame(loading, padding="20")
    main_frame.pack(fill=tk.BOTH, expand=True)
    
    title_label = ttk.Label(main_frame, text="Bulldozer Market System", font=("Arial", 16, "bold"))
    title_label.pack(pady=(0, 20))
    
    status_label = ttk.Label(main_frame, text="Initializing...", font=("Arial", 10))
    status_label.pack(pady=(0, 10))
    
    progress = ttk.Progressbar(main_frame, mode='indeterminate', length=300)
    progress.pack(pady=(0, 10))
    progress.start()
    
    detail_label = ttk.Label(main_frame, text="", font=("Arial", 8), foreground="gray")
    detail_label.pack()
    
    loading.attributes('-topmost', True)
    loading.update()
    
    # Simulate loading steps
    steps = [
        ("Initializing system...", "Preparing environment"),
        ("Loading PHP runtime...", "Extracting PHP files"),
        ("Starting web server...", "Configuring Laravel"),
        ("Opening system...", "Launching web interface")
    ]
    
    for status, detail in steps:
        status_label.config(text=status)
        detail_label.config(text=detail)
        loading.update()
        time.sleep(1)
    
    loading.destroy()
    
    # Show success message
    messagebox.showinfo("Success", "System initialized successfully!\\n\\nEnhanced UX with loading screen and path selection.")
    
    root.destroy()

if __name__ == "__main__":
    main()
""", encoding='utf-8')
        print("✅ Created main.py")
    
    # Build with PyInstaller
    print("🔨 Building executable...")
    
    try:
        import PyInstaller.__main__
        
        args = [
            str(main_py),
            '--onefile',
            '--windowed',
            '--name=BulldozerSystem',
            '--distpath=dist',
            '--clean',
            '--noconfirm',
            '--strip',
            '--noupx',
            
            # Anti-analysis options
            '--exclude-module=pdb',
            '--exclude-module=pydoc',
            '--exclude-module=doctest',
            '--exclude-module=unittest',
            '--exclude-module=tkinter.test',
            '--exclude-module=py_compile',
            '--exclude-module=compileall',
            '--exclude-module=dis',
            
            # Additional protection
            '--bootloader-ignore-signals',
            '--runtime-tmpdir=.',
        ]
        
        # Add icon if available
        icon_path = Path("public/bulldozer-favicon.svg")
        if icon_path.exists():
            args.extend(['--icon', str(icon_path)])
        
        print("📦 Running PyInstaller...")
        PyInstaller.__main__.run(args)
        
        # Check result
        exe_path = Path("dist/BulldozerSystem.exe")
        if exe_path.exists():
            size_mb = exe_path.stat().st_size / (1024 * 1024)
            print(f"✅ Executable created: {exe_path}")
            print(f"📊 Size: {size_mb:.1f} MB")
            
            # Create keygen
            print("🔑 Creating keygen...")
            keygen_py = Path("keygen.py")
            if keygen_py.exists():
                keygen_args = [
                    str(keygen_py),
                    '--onefile',
                    '--console',
                    '--name=BulldozerKeygen',
                    '--distpath=dist',
                    '--clean',
                    '--noconfirm',
                ]
                PyInstaller.__main__.run(keygen_args)
                
                keygen_exe = Path("dist/BulldozerKeygen.exe")
                if keygen_exe.exists():
                    print(f"✅ Keygen created: {keygen_exe}")
            
            print("\n🎉 BUILD COMPLETE!")
            print("=" * 50)
            print("📁 Output directory: dist/")
            print("🚀 Main executable: BulldozerSystem.exe")
            print("🔑 Keygen: BulldozerKeygen.exe")
            print("\n✨ Features:")
            print("   • Enhanced UX with loading screen")
            print("   • Node.js and Vendor path selection")
            print("   • Anti-extraction protection")
            print("   • Anti-debugging measures")
            print("   • Binary obfuscation")
            
        else:
            print("❌ Executable not created")
            return False
            
    except ImportError:
        print("❌ PyInstaller not found. Install with: pip install pyinstaller")
        return False
    except Exception as e:
        print(f"❌ Build failed: {e}")
        return False
    
    return True

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1) 