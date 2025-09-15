#!/usr/bin/env python3
"""
Build Script for BulldozerSystem Python Launcher
Automates the complete packaging and executable creation process
"""

import os
import sys
import shutil
import argparse
from pathlib import Path
import subprocess

class BuildSystem:
    """Automated build system for the Python launcher"""
    
    def __init__(self, project_root: str, php_runtime: str, output_dir: str = None):
        self.project_root = Path(project_root).resolve()
        self.php_runtime = Path(php_runtime).resolve()
        self.script_dir = Path(__file__).parent
        self.output_dir = Path(output_dir) if output_dir else (self.script_dir / "dist")
        
        # Validate paths
        if not (self.project_root / "artisan").exists():
            raise Exception(f"Invalid Laravel project: {self.project_root}")
        
        if not self.php_runtime.exists():
            raise Exception(f"PHP runtime not found: {self.php_runtime}")
        
        self.output_dir.mkdir(exist_ok=True)
        
        print(f"Project root: {self.project_root}")
        print(f"PHP runtime: {self.php_runtime}")
        print(f"Output directory: {self.output_dir}")
    
    def clean_build_directory(self):
        """Clean previous build artifacts"""
        print("Cleaning build directory...")
        
        cleanup_dirs = ["build", "temp", "packages"]
        cleanup_files = ["*.enc", "*.zip", "launcher-*.log"]
        
        for dir_name in cleanup_dirs:
            dir_path = self.script_dir / dir_name
            if dir_path.exists():
                shutil.rmtree(dir_path)
                print(f"  Removed: {dir_name}")
        
        # Clean output directory
        if self.output_dir.exists():
            for item in self.output_dir.iterdir():
                if item.is_file():
                    item.unlink()
                elif item.is_dir():
                    shutil.rmtree(item)
            print(f"  Cleaned: {self.output_dir}")
    
    def check_dependencies(self):
        """Check that all required dependencies are installed"""
        print("Checking dependencies...")
        
        required_modules = [
            'webview',
            'cryptography', 
            'winreg',
            'wmi',
            'psutil'
        ]
        
        missing_modules = []
        for module in required_modules:
            try:
                __import__(module)
                print(f"  ✓ {module}")
            except ImportError:
                missing_modules.append(module)
                print(f"  ✗ {module} (missing)")
        
        if missing_modules:
            print(f"\nMissing dependencies: {', '.join(missing_modules)}")
            print("Run: pip install -r requirements.txt")
            return False
        
        return True
    
    def verify_keys(self):
        """Verify that cryptographic keys are properly set up"""
        print("Verifying cryptographic keys...")
        
        # Check if keygen.py has valid private key
        keygen_path = self.script_dir / "keygen.py"
        if keygen_path.exists():
            content = keygen_path.read_text()
            if "PRIVATE_KEY_BYTES = bytes([10, 161, 155," in content:
                print("  ⚠ Using default private key - consider generating new keys")
            else:
                print("  ✓ Custom private key detected")
        else:
            print("  ✗ keygen.py not found")
            return False
        
        # Check if main.py has valid public key
        main_path = self.script_dir / "main.py"
        if main_path.exists():
            content = main_path.read_text()
            if "public_key_bytes = bytes([114, 91, 103," in content:
                print("  ⚠ Using default public key - consider generating new keys")
            else:
                print("  ✓ Custom public key detected")
        else:
            print("  ✗ main.py not found")
            return False
        
        return True
    
    def package_application(self):
        """Package the Laravel application and PHP runtime"""
        print("Packaging application...")
        
        # Import packager
        sys.path.insert(0, str(self.script_dir))
        from packager import ApplicationPackager
        
        try:
            packager = ApplicationPackager(str(self.project_root), str(self.script_dir))
            
            # Package Laravel application
            app_package = packager.package_application()
            print(f"  Application packaged: {app_package}")
            
            # Package PHP runtime
            php_package = packager.package_php_runtime(str(self.php_runtime))
            print(f"  PHP runtime packaged: {php_package}")
            
            # Update main.py with package data
            packager.update_main_py_with_packages(app_package, php_package)
            print("  Package data embedded in main.py")
            
            return True
            
        except Exception as e:
            print(f"  Error packaging: {e}")
            return False
    
    def create_executable(self):
        """Create standalone executable using PyInstaller"""
        print("Creating standalone executable...")
        
        try:
            import PyInstaller.__main__
        except ImportError:
            print("  PyInstaller not found. Install with: pip install pyinstaller")
            return False
        
        main_py_path = self.script_dir / "main.py"
        
        # Prepare PyInstaller arguments
        args = [
            str(main_py_path),
            '--onefile',
            '--windowed',
            '--name=BulldozerSystem',
            f'--distpath={self.output_dir}',
            f'--workpath={self.script_dir / "build"}',
            '--clean',
            '--noconfirm',
            '--add-data', f'{self.script_dir / "requirements.txt"};.',
        ]
        
        # Add icon if available
        icon_path = self.script_dir / "icon.ico"
        if icon_path.exists():
            args.extend(['--icon', str(icon_path)])
            print("  Using custom icon")
        
        # Add version info if available
        version_path = self.script_dir / "version_info.txt"
        if version_path.exists():
            args.extend(['--version-file', str(version_path)])
            print("  Using version info")
        
        # Run PyInstaller
        try:
            PyInstaller.__main__.run(args)
        except SystemExit:
            pass  # PyInstaller calls sys.exit()
        
        # Check if executable was created
        exe_path = self.output_dir / "BulldozerSystem.exe"
        if exe_path.exists():
            file_size = exe_path.stat().st_size / (1024 * 1024)
            print(f"  Executable created: {exe_path}")
            print(f"  Size: {file_size:.1f} MB")
            return True
        else:
            print("  Failed to create executable")
            return False
    
    def create_installer_script(self):
        """Create a simple installer batch script"""
        print("Creating installer script...")
        
        installer_script = f"""@echo off
echo BulldozerSystem Installer
echo ========================

echo.
echo Installing BulldozerSystem...

REM Create application directory
if not exist "%ProgramFiles%\\BulldozerSystem" (
    mkdir "%ProgramFiles%\\BulldozerSystem"
)

REM Copy executable
copy "BulldozerSystem.exe" "%ProgramFiles%\\BulldozerSystem\\BulldozerSystem.exe"

REM Create desktop shortcut
echo Creating desktop shortcut...
powershell "$WshShell = New-Object -comObject WScript.Shell; $Shortcut = $WshShell.CreateShortcut('%USERPROFILE%\\Desktop\\BulldozerSystem.lnk'); $Shortcut.TargetPath = '%ProgramFiles%\\BulldozerSystem\\BulldozerSystem.exe'; $Shortcut.Save()"

REM Create start menu entry
if not exist "%ProgramData%\\Microsoft\\Windows\\Start Menu\\Programs\\BulldozerSystem" (
    mkdir "%ProgramData%\\Microsoft\\Windows\\Start Menu\\Programs\\BulldozerSystem"
)
powershell "$WshShell = New-Object -comObject WScript.Shell; $Shortcut = $WshShell.CreateShortcut('%ProgramData%\\Microsoft\\Windows\\Start Menu\\Programs\\BulldozerSystem\\BulldozerSystem.lnk'); $Shortcut.TargetPath = '%ProgramFiles%\\BulldozerSystem\\BulldozerSystem.exe'; $Shortcut.Save()"

echo.
echo Installation completed!
echo You can now run BulldozerSystem from the desktop or start menu.
pause
"""
        
        installer_path = self.output_dir / "install.bat"
        installer_path.write_text(installer_script)
        print(f"  Installer script created: {installer_path}")
    
    def create_documentation(self):
        """Create documentation files"""
        print("Creating documentation...")
        
        readme_content = f"""# BulldozerSystem Distribution

## About
This is a packaged distribution of the BulldozerSystem Laravel application.

## Installation
1. Run `install.bat` as Administrator to install the application
2. Or simply run `BulldozerSystem.exe` directly

## Activation
1. The application will prompt for activation on first run
2. Contact your software provider with your hardware UUID
3. Enter the provided activation code

## Requirements
- Windows 10 or later
- At least 4GB RAM
- 1GB free disk space

## Support
For technical support, contact your software provider.

## Files
- `BulldozerSystem.exe` - Main application executable
- `install.bat` - Installation script (optional)
- `README.txt` - This file

## Build Information
- Built on: {os.uname().sysname if hasattr(os, 'uname') else 'Windows'}
- Project: {self.project_root}
- PHP Runtime: {self.php_runtime}
"""
        
        readme_path = self.output_dir / "README.txt"
        readme_path.write_text(readme_content)
        print(f"  README created: {readme_path}")
    
    def build(self):
        """Complete build process"""
        print("Starting build process...")
        print("=" * 50)
        
        try:
            # Step 1: Clean
            self.clean_build_directory()
            print()
            
            # Step 2: Check dependencies
            if not self.check_dependencies():
                return False
            print()
            
            # Step 3: Verify keys
            if not self.verify_keys():
                return False
            print()
            
            # Step 4: Package application
            if not self.package_application():
                return False
            print()
            
            # Step 5: Create executable
            if not self.create_executable():
                return False
            print()
            
            # Step 6: Create installer
            self.create_installer_script()
            print()
            
            # Step 7: Create documentation
            self.create_documentation()
            print()
            
            print("=" * 50)
            print("BUILD COMPLETED SUCCESSFULLY!")
            print(f"Output directory: {self.output_dir}")
            print("\nFiles created:")
            for file in self.output_dir.iterdir():
                print(f"  - {file.name}")
            
            return True
            
        except Exception as e:
            print(f"Build failed: {e}")
            return False

def main():
    parser = argparse.ArgumentParser(description="Build BulldozerSystem executable")
    parser.add_argument("project_root", help="Path to Laravel project root")
    parser.add_argument("php_runtime", help="Path to PHP runtime directory")
    parser.add_argument("--output", "-o", help="Output directory")
    parser.add_argument("--clean-only", action="store_true", help="Only clean build directory")
    
    args = parser.parse_args()
    
    try:
        builder = BuildSystem(args.project_root, args.php_runtime, args.output)
        
        if args.clean_only:
            builder.clean_build_directory()
            print("Clean completed")
        else:
            success = builder.build()
            sys.exit(0 if success else 1)
            
    except Exception as e:
        print(f"Error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()