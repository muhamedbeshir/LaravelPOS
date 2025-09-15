#!/usr/bin/env python3
"""
Protected Main Application
Enhanced version of main.py with anti-reverse engineering protection
"""

# Initialize protection BEFORE any other imports
import sys
import os
from pathlib import Path

# Add protection directory to path
protection_dir = Path(__file__).parent
if str(protection_dir) not in sys.path:
    sys.path.insert(0, str(protection_dir))

# Initialize runtime protection immediately
from runtime_protection import initialize_protection, derive_key, get_nonce
from memory_execution import setup_memory_protection

# Setup memory protection for PyInstaller executables
memory_executor, import_hook = setup_memory_protection()

# Custom exit handler for security
def secure_exit():
    """Secure exit that cleans up and exits"""
    try:
        # Clear sensitive data from memory
        import gc
        gc.collect()
        
        # Overwrite key variables
        globals().clear()
        
    except:
        pass
    finally:
        os._exit(1)

# Start protection
protector = initialize_protection(secure_exit)

# Now import the rest normally
import json
import time
import base64
import shutil
import zipfile
import tempfile
import hashlib
import subprocess
import threading
import socket
import logging
import io
from typing import Optional, Dict, Any, List
import tkinter as tk
from tkinter import messagebox, filedialog, simpledialog

# Third-party imports with protection
try:
    import webview  # This is pywebview package
    from cryptography.hazmat.primitives.ciphers.aead import AESGCM
    from cryptography.hazmat.primitives import hashes, serialization
    from cryptography.hazmat.primitives.asymmetric.ed25519 import Ed25519PrivateKey, Ed25519PublicKey
    import winreg
    import wmi
    import psutil
except ImportError as e:
    print(f"Missing required dependency: {e}")
    print("Install with: pip install webview cryptography pywin32 WMI psutil")
    secure_exit()

# Application constants with dynamic key derivation
APP_NAME = "BulldozerSystem"

# Dynamic encryption - keys derived at runtime
def get_encryption_key() -> bytes:
    """Get encryption key derived at runtime"""
    return derive_key("bulldozer-encryption-master-key", "package-encryption")

def get_encryption_nonce(context: str = "default") -> bytes:
    """Get dynamic nonce for encryption"""
    return get_nonce(f"bulldozer-nonce-{context}")

# Registry configuration (same obfuscation as original)
REG_PARENT = r"Software\BMTLauncher"
RANDOM_ID_VALUE = "R"
PROJECT_ROOT_KEY = "P"
ACTIVATION_KEY = "A"
TEMP_PATH_KEY = "T"

# Application data (will be set by packager)
APP_PKG_DATA = None  # Will contain encrypted Laravel app
PHP_PKG_DATA = None  # Will contain encrypted PHP runtime

class ProtectedCryptoManager:
    """Enhanced crypto manager with runtime protection"""
    
    @staticmethod
    def encrypt_data(data: bytes, context: str = "default") -> bytes:
        """Encrypt data using dynamic keys"""
        key = get_encryption_key()
        nonce = get_encryption_nonce(context)
        
        aesgcm = AESGCM(key)
        return aesgcm.encrypt(nonce, data, None)
    
    @staticmethod
    def decrypt_data(encrypted_data: bytes, context: str = "default") -> bytes:
        """Decrypt data using dynamic keys"""
        key = get_encryption_key()
        nonce = get_encryption_nonce(context)
        
        aesgcm = AESGCM(key)
        return aesgcm.decrypt(nonce, encrypted_data, None)
    
    @staticmethod
    def load_public_key() -> Ed25519PublicKey:
        """Load the public key for activation verification"""
        # Dynamic key loading with obfuscation
        key_data = derive_key("public-key-material", "activation-system")
        
        # The actual public key bytes (obfuscated)
        obfuscated_key = bytes([114, 91, 103, 41, 165, 241, 251, 129, 233, 119, 131, 134, 3, 126, 205, 53, 
                               165, 133, 105, 75, 213, 191, 24, 222, 106, 75, 92, 58, 142, 130, 19, 170])
        
        # XOR deobfuscation
        deobfuscated_key = bytes([
            obfuscated_key[i] ^ key_data[i % len(key_data)] 
            for i in range(len(obfuscated_key))
        ])
        
        return Ed25519PublicKey.from_public_bytes(deobfuscated_key)
    
    @staticmethod
    def verify_activation_code(uuid: str, code: str) -> bool:
        """Verify activation code against hardware UUID"""
        try:
            # Additional integrity check
            if not protector.check_integrity():
                return False
            
            public_key = ProtectedCryptoManager.load_public_key()
            signature_bytes = base64.b64decode(code.strip())
            
            # Hash the UUID with salt
            digest = hashes.Hash(hashes.SHA256())
            digest.update(uuid.strip().encode())
            digest.update(derive_key("uuid-salt", "activation")[:16])  # Add salt
            message = digest.finalize()
            
            public_key.verify(signature_bytes, message)
            return True
        except Exception as e:
            # Enhanced logging for debugging (only in debug mode)
            if os.getenv('BULLDOZER_DEBUG') == '1':
                print(f"Activation verification failed: {e}")
            return False

class ProtectedLogger:
    """Protected logging system"""
    _instance = None
    
    def __init__(self):
        self.debug_enabled = False
        self.log_file = None
        
        # Check if debug features are enabled
        if os.getenv('BULLDOZER_DEBUG') == '1':
            self.debug_enabled = True
            exe_dir = Path(sys.executable).parent if getattr(sys, 'frozen', False) else Path(__file__).parent
            self.log_file = exe_dir / "launcher-debug.log"
            
            # Clear previous log
            if self.log_file.exists():
                self.log_file.unlink()
    
    @classmethod
    def get_instance(cls):
        if cls._instance is None:
            cls._instance = cls()
        return cls._instance
    
    @classmethod
    def debug(cls, message: str):
        """Log debug message with protection"""
        # Check for debugging attempts
        if hasattr(sys, 'gettrace') and sys.gettrace() is not None:
            secure_exit()
        
        instance = cls.get_instance()
        if instance.debug_enabled and instance.log_file:
            try:
                # Obfuscate sensitive information in logs
                safe_message = message
                sensitive_patterns = [
                    'key', 'password', 'token', 'secret', 'hash',
                    'uuid', 'activation', 'encrypt', 'decrypt'
                ]
                
                for pattern in sensitive_patterns:
                    if pattern in safe_message.lower():
                        safe_message = safe_message.replace(pattern, '*' * len(pattern))
                
                with open(instance.log_file, 'a', encoding='utf-8') as f:
                    f.write(f"[{time.strftime('%Y-%m-%d %H:%M:%S')}] {safe_message}\n")
            except Exception:
                pass  # Silent fail for logging

# Use protected logger
Logger = ProtectedLogger

class ProtectedRegistryManager:
    """Enhanced registry manager with additional obfuscation"""
    
    @staticmethod
    def _get_app_registry_path() -> str:
        """Get or create obfuscated registry path"""
        try:
            # Additional obfuscation layer
            parent_key = REG_PARENT + derive_key("registry-obf", "path").hex()[:8]
            
            # Open parent key
            with winreg.OpenKey(winreg.HKEY_CURRENT_USER, parent_key, 0, winreg.KEY_ALL_ACCESS) as parent_key_handle:
                try:
                    # Check if we already have an ID
                    random_id, _ = winreg.QueryValueEx(parent_key_handle, RANDOM_ID_VALUE)
                    return f"{parent_key}\\{random_id}"
                except FileNotFoundError:
                    # Generate new 4-char ID
                    import random
                    import string
                    random_id = ''.join(random.choices(string.ascii_letters + string.digits, k=4))
                    winreg.SetValueEx(parent_key_handle, RANDOM_ID_VALUE, 0, winreg.REG_SZ, random_id)
                    return f"{parent_key}\\{random_id}"
        except FileNotFoundError:
            # Create parent key
            parent_key = REG_PARENT + derive_key("registry-obf", "path").hex()[:8]
            winreg.CreateKey(winreg.HKEY_CURRENT_USER, parent_key)
            return ProtectedRegistryManager._get_app_registry_path()
    
    @staticmethod
    def get_value(key: str) -> Optional[str]:
        """Get registry value with decryption"""
        try:
            path = ProtectedRegistryManager._get_app_registry_path()
            with winreg.OpenKey(winreg.HKEY_CURRENT_USER, path, 0, winreg.KEY_READ) as reg_key:
                encrypted_value, _ = winreg.QueryValueEx(reg_key, key)
                
                # Decrypt the value
                try:
                    encrypted_bytes = base64.b64decode(encrypted_value)
                    decrypted_bytes = ProtectedCryptoManager.decrypt_data(encrypted_bytes, f"registry-{key}")
                    return decrypted_bytes.decode('utf-8')
                except:
                    # Fallback for unencrypted values (backward compatibility)
                    return encrypted_value
        except Exception:
            return None
    
    @staticmethod
    def set_value(key: str, value: str) -> bool:
        """Set registry value with encryption"""
        try:
            path = ProtectedRegistryManager._get_app_registry_path()
            with winreg.OpenKey(winreg.HKEY_CURRENT_USER, path, 0, winreg.KEY_ALL_ACCESS) as reg_key:
                # Encrypt the value
                value_bytes = value.encode('utf-8')
                encrypted_bytes = ProtectedCryptoManager.encrypt_data(value_bytes, f"registry-{key}")
                encrypted_value = base64.b64encode(encrypted_bytes).decode('ascii')
                
                winreg.SetValueEx(reg_key, key, 0, winreg.REG_SZ, encrypted_value)
                return True
        except Exception as e:
            Logger.debug(f"Failed to set registry value: {e}")
            return False
    
    @staticmethod
    def delete_value(key: str):
        """Delete registry value"""
        try:
            path = ProtectedRegistryManager._get_app_registry_path()
            with winreg.OpenKey(winreg.HKEY_CURRENT_USER, path, 0, winreg.KEY_ALL_ACCESS) as reg_key:
                winreg.DeleteValue(reg_key, key)
        except Exception:
            pass  # Silent fail

# Replace original managers with protected versions
RegistryManager = ProtectedRegistryManager
CryptoManager = ProtectedCryptoManager

# Import the rest of the original main.py functionality
# (The rest would be identical to main.py but using the protected versions)

# For brevity, I'll import the original classes and modify key methods
import sys
sys.path.insert(0, str(Path(__file__).parent.parent))

try:
    from main import (
        HardwareManager, ActivationManager, FileManager, 
        PHPServerManager, ApplicationLauncher
    )
    
    # Monkey patch critical methods to use protected versions
    ActivationManager.check_activation = lambda uuid: (
        ProtectedRegistryManager.get_value(ACTIVATION_KEY) and
        ProtectedCryptoManager.verify_activation_code(
            uuid, ProtectedRegistryManager.get_value(ACTIVATION_KEY)
        )
    )
    
    ActivationManager.prompt_for_activation = lambda uuid: (
        _protected_activation_prompt(uuid)
    )
    
except ImportError:
    # If main.py is not available, we need to implement everything here
    Logger.debug("Main module not found, using embedded implementation")
    secure_exit()

def _protected_activation_prompt(uuid: str) -> bool:
    """Protected activation prompt"""
    # Additional security checks
    if not protector.check_debugger():
        secure_exit()
    
    root = tk.Tk()
    root.withdraw()  # Hide main window
    
    # Multiple prompts to confuse automated analysis
    for attempt in range(3):
        code = simpledialog.askstring(
            "Application Activation",
            "Please enter your activation code:",
            show='*'
        )
        
        if code and ProtectedCryptoManager.verify_activation_code(uuid, code):
            ProtectedRegistryManager.set_value(ACTIVATION_KEY, code.strip())
            messagebox.showinfo("Activation Successful", "Thank you for activating!")
            return True
        elif attempt < 2:
            messagebox.showwarning("Invalid Code", "Please try again.")
        else:
            messagebox.showerror("Activation Failed", "The activation code is incorrect. The application will now close.")
    
    # Failed activation - secure exit
    _secure_cleanup_and_exit()
    return False

def _secure_cleanup_and_exit():
    """Secure cleanup and exit"""
    try:
        # Clear sensitive data
        globals().clear()
        
        # Self-delete (same as original but enhanced)
        exe_path = Path(sys.executable if getattr(sys, 'frozen', False) else __file__)
        exe_dir = exe_path.parent
        
        # Enhanced self-deletion with obfuscation
        batch_script = f"""@echo off
timeout /t {random.randint(1, 5)} /nobreak > nul
:CHECK_RUNNING
tasklist /FI "IMAGENAME eq {exe_path.name}" | find /i "{exe_path.name}" > nul
if %ERRORLEVEL% EQU 0 (
timeout /t 1 /nobreak > nul
goto :CHECK_RUNNING
)
del /f /q "{exe_path}"
reg delete "HKCU\\Software\\BMTLauncher" /f 2>nul
for /l %%i in (1,1,{random.randint(5, 15)}) do (
  echo Cleaning system... %%i
  timeout /t 1 /nobreak > nul
)
del "%~f0"
"""
        
        batch_file = exe_dir / f"cleanup_{random.randint(1000, 9999)}.bat"
        batch_file.write_text(batch_script)
        
        # Execute deletion script
        subprocess.Popen([
            "cmd.exe", "/C", "start", "/min", "", str(batch_file)
        ], creationflags=subprocess.CREATE_NO_WINDOW)
        
    except Exception:
        pass
    finally:
        secure_exit()

def main():
    """Protected main entry point"""
    try:
        # Final security check
        if not protector.check_debugger():
            secure_exit()
        
        # Initialize application with protection
        launcher = ApplicationLauncher()
        launcher.run()
        
    except Exception as e:
        Logger.debug(f"Fatal error: {e}")
        if os.getenv('BULLDOZER_DEBUG') == '1':
            messagebox.showerror("Fatal Error", f"Application failed to start:\n\n{e}")
        secure_exit()

if __name__ == "__main__":
    main()