# BulldozerSystem Python Launcher

A complete Python equivalent of the Rust launcher system with all features and functionality intact.

## 🚀 Quick Start

### 1. Setup Environment

```bash
cd python_launcher
python setup.py
```

### 2. Generate Cryptographic Keys (First Time)

```bash
python generate_keys.py
```

### 3. Create Protected Executable (Recommended)

```bash
python create_protected_exe.py
```

### 4. Or Use Manual Build

```bash
# Standard build
python build.py /path/to/laravel/project /path/to/php --output ./dist

# Protected build with anti-reverse engineering
python protection/protected_build.py /path/to/laravel /path/to/php --protection high
```

## 📁 File Structure

```
python_launcher/
├── main.py                    # Main launcher application (equivalent to main.rs)
├── packager.py                # Application packager (equivalent to build.rs)
├── keygen.py                  # Activation code generator (equivalent to keygen.rs)
├── generate_keys.py           # Key pair generator (equivalent to generate_keys.rs)
├── build.py                   # Automated build system
├── setup.py                   # Environment setup
├── requirements.txt           # Python dependencies
├── create_protected_exe.py    # Quick protected executable creator
├── protection/                # Anti-reverse engineering protection
│   ├── obfuscator.py         # Source code obfuscation
│   ├── runtime_protection.py # Runtime anti-analysis
│   ├── binary_protector.py   # Binary-level protection
│   ├── protected_main.py     # Enhanced main application
│   ├── protected_build.py    # Complete protected build system
│   └── README.md            # Protection documentation
├── examples/                  # Usage examples and utilities
└── README.md                 # This file
```

## 🔧 Components Overview

### Main Application (`main.py`)

-   **GUI**: WebView-based desktop application
-   **Activation System**: Hardware-based licensing with Ed25519 signatures
-   **PHP Server**: Manages embedded PHP development server
-   **File Management**: Extracts and sets up Laravel environment
-   **Registry Management**: Obfuscated Windows registry storage
-   **Cleanup**: Automatic temporary file cleanup

### Packager (`packager.py`)

-   **Laravel Packaging**: Bundles entire Laravel application
-   **PHP Runtime**: Packages PHP executable and extensions
-   **Encryption**: AES-GCM encryption of packages
-   **Executable Creation**: PyInstaller integration

### Key Management

-   **`generate_keys.py`**: Generates Ed25519 key pairs
-   **`keygen.py`**: Creates activation codes for customers

### Build System (`build.py`)

-   **Automated Pipeline**: Complete build process
-   **Dependency Checking**: Validates requirements
-   **Documentation**: Auto-generates README and installer

## 🔐 Security Features

### ✅ Core Security

-   Hardware-based activation (motherboard UUID)
-   Ed25519 digital signatures for activation codes
-   AES-GCM encryption for application packages
-   Self-deletion on failed activation
-   Obfuscated registry storage
-   Hidden temporary directories

### 🛡️ Anti-Reverse Engineering Protection

-   **Source Code Obfuscation** - Variable/function name randomization, string encryption
-   **Runtime Protection** - Anti-debugging, VM detection, analysis tool detection
-   **Binary Protection** - UPX compression, VMProtect integration (optional)
-   **Dynamic Keys** - Runtime key derivation instead of hardcoded values
-   **Multi-layer Encryption** - Enhanced package protection
-   **Integrity Checking** - Tampering detection and response

### 🔒 Protection Levels

-   **Basic** - Source obfuscation + basic anti-debugging
-   **Medium** - + Binary compression + VM detection
-   **High** - + Advanced protection + multi-layer encryption

### ⚠️ Security Notes

-   Protection makes reverse engineering economically unfeasible
-   No protection is 100% effective against unlimited resources
-   Test thoroughly on target systems before deployment

## 📋 Requirements

### System Requirements

-   Windows 10 or later
-   Python 3.8+
-   4GB RAM minimum
-   1GB free disk space

### Python Dependencies

```
webview>=4.0.0          # GUI framework
cryptography>=41.0.0    # Encryption/signatures
pywin32>=306             # Windows API access
WMI>=1.5.1              # Hardware information
psutil>=5.9.0           # Process management
pyinstaller>=5.13.0     # Executable creation
```

## 🛠️ Usage Examples

### Generate New Keys

```bash
# Generate new Ed25519 key pair
python generate_keys.py
```

### Create Activation Code

```bash
# Run the key generator
python keygen.py
# Enter customer's motherboard UUID when prompted
```

### Package Application

```bash
# Basic packaging
python packager.py /path/to/laravel --php-runtime /path/to/php

# Create executable
python packager.py /path/to/laravel --php-runtime /path/to/php --create-exe
```

### Complete Build

```bash
# Automated build with all steps
python build.py /path/to/laravel /path/to/php --output ./dist
```

### Debug Mode

```bash
# Enable debug logging
set BULLDOZER_DEBUG=1
python main.py
```

## 🔄 Workflow

### Development Workflow

1. **Setup**: Run `setup.py` to install dependencies
2. **Keys**: Generate keys with `generate_keys.py`
3. **Develop**: Test Laravel application normally
4. **Package**: Use `packager.py` to create encrypted bundles
5. **Build**: Use `build.py` for complete executable creation

### Distribution Workflow

1. **Build**: Create executable with `build.py`
2. **Test**: Verify executable works on clean system
3. **Generate Keys**: Use `keygen.py` for customer activation codes
4. **Distribute**: Share executable + installation files

## 🐛 Troubleshooting

### Common Issues

**Missing Dependencies**

```bash
pip install -r requirements.txt
```

**PyInstaller Errors**

```bash
pip install --upgrade pyinstaller
```

**WMI Access Issues**

```bash
# Run as Administrator if WMI access fails
python main.py
```

**PHP Server Won't Start**

-   Check PHP runtime path
-   Verify php.exe exists
-   Check port 8000 availability

### Debug Logging

Enable debug mode to see detailed logs:

```bash
set BULLDOZER_DEBUG=1
python main.py
```

Log files created:

-   `launcher-debug.log` - Debug information
-   `launcher-error.log` - Error details

## 📊 Feature Comparison

| Feature            | Rust Version   | Python Version   | Status      |
| ------------------ | -------------- | ---------------- | ----------- |
| GUI Framework      | wry + tao      | webview          | ✅ Complete |
| Activation System  | Ed25519        | Ed25519          | ✅ Complete |
| Encryption         | AES-GCM        | AES-GCM          | ✅ Complete |
| Registry Storage   | winreg         | winreg           | ✅ Complete |
| PHP Server         | subprocess     | subprocess       | ✅ Complete |
| File Management    | std::fs        | pathlib + shutil | ✅ Complete |
| Self-Deletion      | batch script   | batch script     | ✅ Complete |
| Package Embedding  | include_bytes! | base64 data      | ✅ Complete |
| Hardware UUID      | WMI            | WMI              | ✅ Complete |
| Process Management | winapi         | psutil           | ✅ Complete |

## 🚀 Advanced Usage

### Custom Configuration

Create `config/launcher_config.json`:

```json
{
    "app_name": "BulldozerSystem",
    "window_title": "Custom Title",
    "window_width": 1400,
    "window_height": 900,
    "debug_mode": false,
    "auto_start_server": true,
    "default_port": 8000
}
```

### Environment Variables

-   `BULLDOZER_DEBUG=1` - Enable debug logging
-   `WEBVIEW2_USER_DATA_FOLDER` - Custom WebView2 data directory

### Command Line Options

```bash
# Packager options
python packager.py --help

# Build system options
python build.py --help
```

## 📝 License

This Python launcher maintains the same functionality and architecture as the original Rust version while providing easier maintenance and customization through Python's ecosystem.

## 🤝 Contributing

When modifying the launcher:

1. Maintain feature parity with Rust version
2. Update documentation for any changes
3. Test on clean Windows systems
4. Verify activation system works correctly

## 📞 Support

For issues or questions:

1. Check troubleshooting section
2. Enable debug logging
3. Review log files for detailed error information
