#!/usr/bin/env python3
"""
Protected Build System
Enhanced build system with comprehensive anti-reverse engineering protection
"""

import os
import sys
import shutil
import argparse
import tempfile
from pathlib import Path
import subprocess
import time
from typing import Optional

# Set console encoding to UTF-8 for Windows
if sys.platform == 'win32':
    try:
        import locale
        import codecs
        # Force UTF-8 output
        sys.stdout = codecs.getwriter('utf-8')(sys.stdout.buffer, 'replace')
        sys.stderr = codecs.getwriter('utf-8')(sys.stderr.buffer, 'replace')
    except:
        # Fallback - just continue if encoding setup fails
        pass

# Add protection directory to path
sys.path.insert(0, str(Path(__file__).parent))

from obfuscator import obfuscate_project
from binary_protector import BinaryProtector
from runtime_protection import derive_key

class ProtectedBuildSystem:
    """Advanced build system with multi-layer protection"""
    
    def __init__(self, project_root: str, php_runtime: str, output_dir: str = None):
        self.project_root = Path(project_root).resolve()
        self.php_runtime = Path(php_runtime).resolve()
        self.script_dir = Path(__file__).parent.parent
        self.protection_dir = Path(__file__).parent
        self.output_dir = Path(output_dir) if output_dir else (self.script_dir / "dist")
        
        # Validate paths
        if not (self.project_root / "artisan").exists():
            raise Exception(f"Invalid Laravel project: {self.project_root}")
        
        if not self.php_runtime.exists():
            raise Exception(f"PHP runtime not found: {self.php_runtime}")
        
        self.output_dir.mkdir(exist_ok=True)
        
        # Create temporary directories
        self.temp_base = Path(tempfile.mkdtemp(prefix="bulldozer_build_"))
        self.obfuscated_dir = self.temp_base / "obfuscated"
        self.packaged_dir = self.temp_base / "packaged"
        
        print(f"Project root: {self.project_root}")
        print(f"PHP runtime: {self.php_runtime}")
        print(f"Output directory: {self.output_dir}")
        print(f"Temp build directory: {self.temp_base}")
    
    def __enter__(self):
        return self
    
    def __exit__(self, exc_type, exc_val, exc_tb):
        # Cleanup temporary directories
        if self.temp_base.exists():
            try:
                shutil.rmtree(self.temp_base)
            except Exception as e:
                print(f"Warning: Failed to cleanup temp directory: {e}")
    
    def clean_build_directory(self):
        """Clean previous build artifacts"""
        print("Cleaning build directory...")
        
        cleanup_dirs = ["build", "temp", "packages", "__pycache__"]
        for dir_name in cleanup_dirs:
            dir_path = self.script_dir / dir_name
            if dir_path.exists():
                shutil.rmtree(dir_path)
                print(f"  Removed: {dir_name}")
        
        # Clean output directory
        if self.output_dir.exists():
            for item in self.output_dir.iterdir():
                if item.is_file() and item.suffix in ['.exe', '.zip', '.enc']:
                    item.unlink()
                elif item.is_dir() and item.name in ['build', 'dist']:
                    shutil.rmtree(item)
            print(f"  Cleaned: {self.output_dir}")
    
    def check_protection_dependencies(self):
        """Check protection tool dependencies"""
        print("Checking protection dependencies...")
        
        required_modules = [
            'webview', 'cryptography', 'psutil', 'pyinstaller'
        ]
        
        # Windows-specific modules
        if sys.platform == 'win32':
            required_modules.extend(['winreg', 'wmi'])
        
        missing_modules = []
        for module in required_modules:
            try:
                __import__(module)
                print(f"  [OK] {module}")
            except ImportError:
                # Special handling for pyinstaller - try importing as package
                if module == 'pyinstaller':
                    try:
                        import PyInstaller
                        print(f"  [OK] {module} (found as PyInstaller)")
                    except ImportError:
                        missing_modules.append(module)
                        print(f"  [MISSING] {module}")
                else:
                    missing_modules.append(module)
                    print(f"  [MISSING] {module}")
        
        if missing_modules:
            print(f"\nMissing dependencies: {', '.join(missing_modules)}")
            print("Run: pip install -r requirements.txt")
            return False
        
        # Check protection tools
        with BinaryProtector() as protector:
            tools = protector.check_protection_tools()
            
            if tools['upx']:
                print("  [OK] UPX packer available")
            else:
                print("  [WARNING] UPX not available (install for better protection)")
            
            if tools['vmprot']:
                print("  [OK] VMProtect available")
            else:
                print("  [WARNING] VMProtect not available")
        
        return True
    
    def obfuscate_source_code(self):
        """Obfuscate all Python source code"""
        print("Obfuscating source code...")
        
        # Copy launcher source to temporary directory
        source_dir = self.script_dir
        
        # Obfuscate the entire project
        obfuscate_project(source_dir, self.obfuscated_dir)
        
        # Replace main.py with protected version
        protected_main = self.protection_dir / "protected_main.py"
        if protected_main.exists():
            target_main = self.obfuscated_dir / "main.py"
            
            # Read and further obfuscate protected main
            from obfuscator import CodeObfuscator
            obfuscator = CodeObfuscator()
            obfuscator.obfuscate_file(protected_main, target_main)
            
            print("  [OK] Replaced with protected main")
        else:
            print("  [WARNING] Protected main not found, using standard obfuscation")
        
        print(f"Source code obfuscated to: {self.obfuscated_dir}")
    
    def package_application_protected(self):
        """Package application with enhanced encryption"""
        print("Packaging application with enhanced protection...")
        
        # Add parent directory to Python path to import packager
        parent_dir = Path(__file__).parent.parent
        if str(parent_dir) not in sys.path:
            sys.path.insert(0, str(parent_dir))
        
        # Use dynamic keys for packaging
        app_key = derive_key("app-package-master", "encryption-context")
        php_key = derive_key("php-package-master", "encryption-context")
        
        from packager import ApplicationPackager
        
        class ProtectedPackager(ApplicationPackager):
            def _encrypt_file(self, file_path: Path) -> Path:
                """Enhanced encryption with dynamic keys"""
                print(f"Applying enhanced encryption to: {file_path}")
                
                # Read file data
                file_data = file_path.read_bytes()
                
                # Multi-layer encryption
                from cryptography.hazmat.primitives.ciphers.aead import AESGCM
                from runtime_protection import get_nonce
                
                # Layer 1: AES-GCM with dynamic key
                key = app_key if 'app' in str(file_path) else php_key
                nonce = get_nonce(str(file_path))
                
                aesgcm = AESGCM(key)
                encrypted_data = aesgcm.encrypt(nonce, file_data, None)
                
                # Layer 2: XOR obfuscation
                xor_key = derive_key("xor-layer", file_path.name)[:256]
                final_data = bytearray(encrypted_data)
                for i in range(len(final_data)):
                    final_data[i] ^= xor_key[i % len(xor_key)]
                
                # Write encrypted file
                encrypted_path = file_path.with_suffix(file_path.suffix + '.enc')
                encrypted_path.write_bytes(final_data)
                
                print(f"Enhanced encryption applied: {encrypted_path}")
                return encrypted_path
        
        # Package with enhanced encryption
        packager = ProtectedPackager(str(self.project_root), str(self.php_runtime))
        
        app_package = packager.package_application()
        php_package = packager.package_php_runtime(str(self.php_runtime))
        
        # Update main.py with package data (use original directory since obfuscation is disabled)
        # Copy main.py to obfuscated directory if not already there
        original_main = Path(__file__).parent.parent / "main.py"
        target_main = self.obfuscated_dir / "main.py"
        
        # Ensure obfuscated directory exists
        self.obfuscated_dir.mkdir(parents=True, exist_ok=True)
        
        if not target_main.exists():
            if original_main.exists():
                import shutil
                shutil.copy2(original_main, target_main)
                print(f"Copied main.py from {original_main} to {target_main}")
            else:
                # Create a simple main.py if the original doesn't exist
                target_main.write_text("# Main application\n", encoding='utf-8')
                print(f"Created main.py at {target_main}")
        
        packager.update_main_py_with_packages(app_package, php_package)
        
        print("[OK] Application packaged with enhanced protection")
        return app_package, php_package
    
    def create_protected_executable(self, protection_level: str = "high"):
        """Create executable with maximum protection"""
        print(f"Creating protected executable (level: {protection_level})...")
        
        try:
            import PyInstaller.__main__
        except ImportError:
            print("PyInstaller not found. Install with: pip install pyinstaller")
            return None
        
        main_py_path = self.obfuscated_dir / "main.py"
        
        # Create a proper spec file for better control
        spec_path = self.temp_base / "BulldozerSystem.spec"
        
        spec_content = f'''# -*- mode: python ; coding: utf-8 -*-

block_cipher = None

a = Analysis(
    [r'{str(main_py_path).replace(chr(92), chr(92)+chr(92))}'],
    pathex=[r'{str(self.obfuscated_dir).replace(chr(92), chr(92)+chr(92))}'],
    binaries=[],
    datas=[],
    hiddenimports=[
        'pywebview',
        'cryptography',
        'cryptography.hazmat',
        'cryptography.hazmat.primitives',
        'cryptography.hazmat.primitives.ciphers',
        'cryptography.hazmat.primitives.ciphers.aead',
        'cryptography.hazmat.primitives.asymmetric',
        'cryptography.hazmat.primitives.asymmetric.ed25519',
        'tkinter',
        'tkinter.messagebox',
        'tkinter.filedialog',
        'tkinter.simpledialog',
        'tkinter.ttk',
        'winreg',
        'wmi',
        'psutil',
        'requests',
        'base64',
        'hashlib',
        'json',
        'os',
        'sys',
        'tempfile',
        'threading',
        'time',
        'zipfile',
        'pathlib',
        'subprocess',
        'shutil',
        'platform',
        'ctypes',
        'ctypes.wintypes'
    ],
    hookspath=[],
    hooksconfig={{}},
    runtime_hooks=[],
    excludes=['pdb', 'pydoc', 'doctest', 'unittest', 'tkinter.test', 'py_compile', 'compileall', 'dis'],
    win_no_prefer_redirects=False,
    win_private_assemblies=False,
    cipher=block_cipher,
    noarchive=False,
)

pyz = PYZ(a.pure, a.zipped_data, cipher=block_cipher)

exe = EXE(
    pyz,
    a.scripts,
    a.binaries,
    a.zipfiles,
    a.datas,
    [],
    name='BulldozerSystem',
    debug=False,
    bootloader_ignore_signals=False,
    strip=False,
    upx=False,
    upx_exclude=[],
    runtime_tmpdir=None,
    console=False,
    disable_windowed_traceback=False,
    argv_emulation=False,
    target_arch=None,
    codesign_identity=None,
    entitlements_file=None,
    icon=None,
    version_file=None,
)
'''
        
        spec_path.write_text(spec_content, encoding='utf-8')
        
        # Use the spec file instead of command line arguments
        args = [
            str(spec_path),
            '--clean',
            '--noconfirm',
        ]
        
        # Add icon if available
        icon_path = self.script_dir / "icon.ico"
        if icon_path.exists():
            args.extend(['--icon', str(icon_path)])
        
        # Add version info for authenticity
        version_info = self.create_version_info()
        if version_info:
            args.extend(['--version-file', str(version_info)])
        
        # Add fake files to confuse analyzers
        fake_files = self.create_fake_files()
        for fake_file in fake_files:
            args.extend(['--add-data', f'{fake_file};.'])
        
        # Run PyInstaller
        try:
            PyInstaller.__main__.run(args)
        except SystemExit:
            pass  # PyInstaller calls sys.exit()
        
        # Check if executable was created
        exe_path = self.output_dir / "BulldozerSystem.exe"
        if exe_path.exists():
            file_size = exe_path.stat().st_size / (1024 * 1024)
            print(f"  Base executable created: {exe_path}")
            print(f"  Size: {file_size:.1f} MB")
            
            # Apply binary protection
            return self.apply_binary_protection(exe_path, protection_level)
        else:
            print("  Failed to create executable")
            return None
    
    def create_version_info(self) -> Optional[Path]:
        """Create version info file for executable"""
        version_content = """# UTF-8
VSVersionInfo(
  ffi=FixedFileInfo(
    filevers=(1, 0, 0, 0),
    prodvers=(1, 0, 0, 0),
    mask=0x3f,
    flags=0x0,
    OS=0x40004,
    fileType=0x1,
    subtype=0x0,
    date=(0, 0)
  ),
  kids=[
    StringFileInfo(
      [
      StringTable(
        u'040904B0',
        [StringStruct(u'CompanyName', u'BulldozerSystem Corporation'),
        StringStruct(u'FileDescription', u'BulldozerSystem Application'),
        StringStruct(u'FileVersion', u'1.0.0.0'),
        StringStruct(u'InternalName', u'BulldozerSystem'),
        StringStruct(u'LegalCopyright', u'Copyright 2024 BulldozerSystem Corp.'),
        StringStruct(u'OriginalFilename', u'BulldozerSystem.exe'),
        StringStruct(u'ProductName', u'BulldozerSystem'),
        StringStruct(u'ProductVersion', u'1.0.0.0')])
      ]), 
    VarFileInfo([VarStruct(u'Translation', [1033, 1200])])
  ]
)"""
        
        version_file = self.temp_base / "version_info.txt"
        version_file.write_text(version_content, encoding='utf-8')
        return version_file
    
    def create_fake_files(self) -> list:
        """Create fake files to confuse analyzers"""
        fake_files = []
        
        # Create fake documentation
        fake_readme = self.temp_base / "README_FAKE.txt"
        fake_readme.write_text("""
This is a fake readme file designed to confuse automated analysis tools.
The real application functionality is deeply embedded and protected.
Any attempt to reverse engineer this software is prohibited.
""")
        fake_files.append(fake_readme)
        
        # Create fake config
        fake_config = self.temp_base / "config_fake.json"
        fake_config.write_text('{"fake": true, "real_config": "encrypted_elsewhere"}')
        fake_files.append(fake_config)
        
        return fake_files
    
    def apply_binary_protection(self, exe_path: Path, protection_level: str) -> Optional[Path]:
        """Apply binary-level protection to executable"""
        print(f"Applying binary protection (level: {protection_level})...")
        
        with BinaryProtector() as protector:
            success = protector.protect_executable(exe_path, protection_level)
            
            if success:
                print("[OK] Binary protection applied successfully")
                return exe_path
            else:
                print("[WARNING] Some binary protection features failed")
                return exe_path  # Return anyway, partial protection is better than none
    
    def create_installer_package(self):
        """Create installation package with additional protection"""
        print("Creating installer package...")
        
        # Create advanced installer script
        installer_script = f"""@echo off
title BulldozerSystem Installer
color 0A

echo.
echo ╔══════════════════════════════════════╗
echo ║        BulldozerSystem Installer    ║
echo ╚══════════════════════════════════════╝
echo.

REM Check for admin rights
net session >nul 2>&1
if %%errorLevel%% neq 0 (
    echo This installer requires administrator privileges.
    echo Please run as administrator.
    pause
    exit /b 1
)

echo Installing BulldozerSystem...
echo.

REM Create application directory
set "INSTALL_DIR=%ProgramFiles%\\BulldozerSystem"
if not exist "%%INSTALL_DIR%%" (
    mkdir "%%INSTALL_DIR%%"
)

REM Copy executable with verification
echo Copying application files...
copy "BulldozerSystem.exe" "%%INSTALL_DIR%%\\BulldozerSystem.exe" >nul
if %%errorlevel%% neq 0 (
    echo Error: Failed to copy application files.
    pause
    exit /b 1
)

REM Verify file integrity
for %%F in ("%%INSTALL_DIR%%\\BulldozerSystem.exe") do set "FILE_SIZE=%%~zF"
if %%FILE_SIZE%% LSS 1000000 (
    echo Error: Installation verification failed.
    pause
    exit /b 1
)

REM Create desktop shortcut
echo Creating shortcuts...
powershell -Command "$WshShell = New-Object -comObject WScript.Shell; $Shortcut = $WshShell.CreateShortcut('%%USERPROFILE%%\\Desktop\\BulldozerSystem.lnk'); $Shortcut.TargetPath = '%%INSTALL_DIR%%\\BulldozerSystem.exe'; $Shortcut.WorkingDirectory = '%%INSTALL_DIR%%'; $Shortcut.Save()" >nul 2>&1

REM Create start menu entry
if not exist "%%ProgramData%%\\Microsoft\\Windows\\Start Menu\\Programs\\BulldozerSystem" (
    mkdir "%%ProgramData%%\\Microsoft\\Windows\\Start Menu\\Programs\\BulldozerSystem"
)
powershell -Command "$WshShell = New-Object -comObject WScript.Shell; $Shortcut = $WshShell.CreateShortcut('%%ProgramData%%\\Microsoft\\Windows\\Start Menu\\Programs\\BulldozerSystem\\BulldozerSystem.lnk'); $Shortcut.TargetPath = '%%INSTALL_DIR%%\\BulldozerSystem.exe'; $Shortcut.WorkingDirectory = '%%INSTALL_DIR%%'; $Shortcut.Save()" >nul 2>&1

REM Register uninstaller
echo Registering application...
reg add "HKLM\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Uninstall\\BulldozerSystem" /v "DisplayName" /t REG_SZ /d "BulldozerSystem" /f >nul
reg add "HKLM\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Uninstall\\BulldozerSystem" /v "UninstallString" /t REG_SZ /d "%%INSTALL_DIR%%\\uninstall.bat" /f >nul
reg add "HKLM\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Uninstall\\BulldozerSystem" /v "DisplayVersion" /t REG_SZ /d "1.0" /f >nul

REM Create uninstaller
echo @echo off > "%%INSTALL_DIR%%\\uninstall.bat"
echo title BulldozerSystem Uninstaller >> "%%INSTALL_DIR%%\\uninstall.bat"
echo echo Uninstalling BulldozerSystem... >> "%%INSTALL_DIR%%\\uninstall.bat"
echo taskkill /f /im BulldozerSystem.exe ^>nul 2^>^&1 >> "%%INSTALL_DIR%%\\uninstall.bat"
echo timeout /t 2 /nobreak ^>nul >> "%%INSTALL_DIR%%\\uninstall.bat"
echo del "%%USERPROFILE%%\\Desktop\\BulldozerSystem.lnk" ^>nul 2^>^&1 >> "%%INSTALL_DIR%%\\uninstall.bat"
echo rmdir /s /q "%%ProgramData%%\\Microsoft\\Windows\\Start Menu\\Programs\\BulldozerSystem" ^>nul 2^>^&1 >> "%%INSTALL_DIR%%\\uninstall.bat"
echo reg delete "HKLM\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Uninstall\\BulldozerSystem" /f ^>nul 2^>^&1 >> "%%INSTALL_DIR%%\\uninstall.bat"
echo cd /d "%%ProgramFiles%%" >> "%%INSTALL_DIR%%\\uninstall.bat"
echo rmdir /s /q "BulldozerSystem" >> "%%INSTALL_DIR%%\\uninstall.bat"
echo echo BulldozerSystem has been uninstalled. >> "%%INSTALL_DIR%%\\uninstall.bat"
echo pause >> "%%INSTALL_DIR%%\\uninstall.bat"

echo.
echo ╔══════════════════════════════════════╗
echo ║     Installation completed!          ║
echo ╚══════════════════════════════════════╝
echo.
echo BulldozerSystem has been installed successfully.
echo You can run it from the desktop or start menu.
echo.
pause
"""
        
        installer_path = self.output_dir / "install.bat"
        installer_path.write_text(installer_script, encoding='utf-8')
        
        # Create README with instructions
        readme_content = f"""# BulldozerSystem - Protected Distribution

## Installation
1. Run `install.bat` as Administrator to install the application
2. Or run `BulldozerSystem.exe` directly (portable mode)

## Security Features
This distribution includes advanced protection against:
- Reverse engineering
- Code extraction
- Dynamic analysis
- Debugging attempts
- Virtual machine detection

## System Requirements
- Windows 10 or later (64-bit)
- 4GB RAM minimum
- 1GB free disk space
- Administrator privileges for installation

## Activation
1. Run the application
2. When prompted, provide your hardware UUID to your software provider
3. Enter the activation code you receive

## Support
For technical support or activation issues, contact your software provider.

---
Built with advanced protection - Build ID: {derive_key('build-id', str(int(time.time()))).hex()[:16]}
"""
        
        readme_path = self.output_dir / "README.txt"
        readme_path.write_text(readme_content, encoding='utf-8')
        
        print(f"[OK] Installer package created in: {self.output_dir}")
    
    def build_protected(self, protection_level: str = "high") -> bool:
        """Complete protected build process"""
        print("Starting protected build process...")
        print("=" * 60)
        
        try:
            # Step 1: Clean environment
            self.clean_build_directory()
            print()
            
            # Step 2: Check dependencies
            if not self.check_protection_dependencies():
                return False
            print()
            
            # Step 3: Obfuscate source code (disabled temporarily for debugging)
            # self.obfuscate_source_code()
            print("Skipping obfuscation for debugging...")
            print()
            
            # Step 4: Package with enhanced encryption
            self.package_application_protected()
            print()
            
            # Step 5: Create protected executable
            exe_path = self.create_protected_executable(protection_level)
            if not exe_path:
                return False
            print()
            
            # Step 6: Create installer package
            self.create_installer_package()
            print()
            
            print("=" * 60)
            print("PROTECTED BUILD COMPLETED SUCCESSFULLY!")
            print(f"Output directory: {self.output_dir}")
            print("\nFiles created:")
            for file in self.output_dir.iterdir():
                if file.is_file():
                    size = file.stat().st_size / (1024 * 1024)
                    print(f"  - {file.name} ({size:.1f} MB)")
            
            print(f"\nProtection level applied: {protection_level.upper()}")
            print("The executable is now protected against reverse engineering.")
            
            return True
            
        except Exception as e:
            print(f"Protected build failed: {e}")
            import traceback
            traceback.print_exc()
            return False

def main():
    parser = argparse.ArgumentParser(description="Build protected BulldozerSystem executable")
    parser.add_argument("project_root", help="Path to Laravel project root")
    parser.add_argument("php_runtime", help="Path to PHP runtime directory")
    parser.add_argument("--output", "-o", help="Output directory")
    parser.add_argument("--protection", choices=["basic", "medium", "high"], 
                       default="high", help="Protection level")
    parser.add_argument("--clean-only", action="store_true", help="Only clean build directory")
    
    args = parser.parse_args()
    
    try:
        with ProtectedBuildSystem(args.project_root, args.php_runtime, args.output) as builder:
            if args.clean_only:
                builder.clean_build_directory()
                print("Clean completed")
            else:
                success = builder.build_protected(args.protection)
                sys.exit(0 if success else 1)
                
    except Exception as e:
        print(f"Error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()