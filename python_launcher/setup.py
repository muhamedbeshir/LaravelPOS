#!/usr/bin/env python3
"""
Setup script for BulldozerSystem Python Launcher
"""

import os
import sys
import subprocess
from pathlib import Path

def install_dependencies():
    """Install required dependencies"""
    print("Installing dependencies...")
    
    requirements_file = Path(__file__).parent / "requirements.txt"
    if not requirements_file.exists():
        print("Error: requirements.txt not found")
        return False
    
    try:
        subprocess.check_call([
            sys.executable, "-m", "pip", "install", "-r", str(requirements_file)
        ])
        print("Dependencies installed successfully")
        return True
    except subprocess.CalledProcessError as e:
        print(f"Error installing dependencies: {e}")
        return False

def setup_environment():
    """Setup development environment"""
    print("Setting up development environment...")
    
    # Create directories
    dirs_to_create = [
        "dist",
        "build", 
        "temp",
        "packages"
    ]
    
    for dir_name in dirs_to_create:
        dir_path = Path(__file__).parent / dir_name
        dir_path.mkdir(exist_ok=True)
        print(f"Created directory: {dir_name}")
    
    print("Environment setup complete")

def check_system_requirements():
    """Check system requirements"""
    print("Checking system requirements...")
    
    # Check Python version
    if sys.version_info < (3, 8):
        print("Error: Python 3.8 or higher is required")
        return False
    
    print(f"Python version: {sys.version}")
    
    # Check if on Windows (required for some features)
    if os.name != 'nt':
        print("Warning: Some features are Windows-specific")
    
    # Check for Visual C++ components (needed for some cryptography packages)
    try:
        import winreg
        print("Windows registry access: Available")
    except ImportError:
        print("Windows registry access: Not available")
    
    return True

def create_sample_config():
    """Create sample configuration files"""
    config_dir = Path(__file__).parent / "config"
    config_dir.mkdir(exist_ok=True)
    
    # Sample launcher config
    launcher_config = {
        "app_name": "BulldozerSystem",
        "window_title": "BulldozerSystem",
        "window_width": 1200,
        "window_height": 800,
        "debug_mode": False,
        "auto_start_server": True,
        "default_port": 8000
    }
    
    import json
    config_file = config_dir / "launcher_config.json"
    with open(config_file, 'w') as f:
        json.dump(launcher_config, f, indent=2)
    
    print(f"Created sample config: {config_file}")

def main():
    """Main setup routine"""
    print("BulldozerSystem Python Launcher Setup")
    print("=" * 40)
    
    # Check system requirements
    if not check_system_requirements():
        print("System requirements not met")
        sys.exit(1)
    
    # Install dependencies
    if not install_dependencies():
        print("Failed to install dependencies")
        sys.exit(1)
    
    # Setup environment
    setup_environment()
    
    # Create sample configuration
    create_sample_config()
    
    print("\nSetup completed successfully!")
    print("\nNext steps:")
    print("1. Run 'python generate_keys.py' to generate cryptographic keys")
    print("2. Configure your Laravel project path")
    print("3. Run 'python packager.py <project_path> --php-runtime <php_path>' to package")
    print("4. Run 'python main.py' to test the launcher")
    print("5. Use packager.py with --create-exe to build standalone executable")

if __name__ == "__main__":
    main()