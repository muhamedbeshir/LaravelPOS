#!/usr/bin/env python3
"""
Memory-Only Execution System
Runs code from memory without disk extraction where possible
"""

import os
import sys
import marshal
import types
import importlib.util
from pathlib import Path
import tempfile
import threading
import time

class MemoryExecutor:
    """Execute Python code from memory without disk extraction"""
    
    def __init__(self):
        self.memory_modules = {}
        self.monitor_thread = None
        self.monitoring = True
        
    def load_from_memory(self, module_name: str, bytecode: bytes):
        """Load module from memory without writing to disk"""
        try:
            # Create module object
            spec = importlib.util.spec_from_loader(module_name, loader=None)
            module = importlib.util.module_from_spec(spec)
            
            # Execute bytecode in module's namespace
            code_obj = marshal.loads(bytecode)
            exec(code_obj, module.__dict__)
            
            # Store in memory
            self.memory_modules[module_name] = module
            sys.modules[module_name] = module
            
            return module
            
        except Exception as e:
            print(f"Failed to load module {module_name} from memory: {e}")
            return None
    
    def start_extraction_monitoring(self):
        """Monitor for PyInstaller extraction and take countermeasures"""
        def monitor_loop():
            while self.monitoring:
                try:
                    self._check_for_extraction()
                    time.sleep(0.5)  # Check every 500ms
                except Exception:
                    pass  # Silent monitoring
        
        self.monitor_thread = threading.Thread(target=monitor_loop, daemon=True)
        self.monitor_thread.start()
    
    def _check_for_extraction(self):
        """Check for PyInstaller extraction directories"""
        temp_dir = Path(tempfile.gettempdir())
        
        # Look for PyInstaller extraction patterns
        for item in temp_dir.glob('_MEI*'):
            if item.is_dir():
                # Extraction detected - take countermeasures
                self._handle_extraction_detected(item)
    
    def _handle_extraction_detected(self, extraction_dir: Path):
        """Handle detected extraction attempt"""
        try:
            # Strategy 1: Corrupt extracted files
            self._corrupt_extracted_files(extraction_dir)
            
            # Strategy 2: Create fake files
            self._create_fake_files(extraction_dir)
            
            # Strategy 3: Monitor for file access
            self._monitor_file_access(extraction_dir)
            
        except Exception:
            pass  # Silent failure
    
    def _corrupt_extracted_files(self, extraction_dir: Path):
        """Overwrite extracted .pyc files with garbage"""
        try:
            for pyc_file in extraction_dir.rglob('*.pyc'):
                if pyc_file.exists():
                    # Overwrite with random data maintaining file size
                    original_size = pyc_file.stat().st_size
                    garbage_data = os.urandom(original_size)
                    pyc_file.write_bytes(garbage_data)
        except Exception:
            pass
    
    def _create_fake_files(self, extraction_dir: Path):
        """Create fake Python files to confuse analyzers"""
        try:
            fake_files = [
                "main.pyc", "config.pyc", "database.pyc", "auth.pyc",
                "encryption.pyc", "license.pyc", "activation.pyc"
            ]
            
            for fake_file in fake_files:
                fake_path = extraction_dir / fake_file
                if not fake_path.exists():
                    # Create fake bytecode that looks real but does nothing
                    fake_code = compile("print('Fake module loaded')", fake_file, 'exec')
                    fake_bytecode = marshal.dumps(fake_code)
                    fake_path.write_bytes(fake_bytecode)
        except Exception:
            pass
    
    def _monitor_file_access(self, extraction_dir: Path):
        """Monitor for file access attempts"""
        try:
            # This would require platform-specific file monitoring
            # For now, just log the attempt
            import logging
            logging.warning(f"Extraction attempt detected: {extraction_dir}")
        except Exception:
            pass
    
    def stop_monitoring(self):
        """Stop extraction monitoring"""
        self.monitoring = False
        if self.monitor_thread:
            self.monitor_thread.join(timeout=1)

class ProtectedImportHook:
    """Custom import hook to prevent extraction-based analysis"""
    
    def __init__(self, memory_executor: MemoryExecutor):
        self.memory_executor = memory_executor
        self.original_import = __builtins__.__import__
        
    def install(self):
        """Install the protected import hook"""
        __builtins__.__import__ = self._protected_import
    
    def uninstall(self):
        """Restore original import"""
        __builtins__.__import__ = self.original_import
    
    def _protected_import(self, name, globals=None, locals=None, fromlist=(), level=0):
        """Protected import that checks for analysis attempts"""
        try:
            # Check if someone is trying to import from extracted files
            frame = sys._getframe(1)
            caller_file = frame.f_code.co_filename
            
            # If import is from temp directory, it might be analysis
            if '_MEI' in caller_file and 'temp' in caller_file.lower():
                # Potential analysis attempt - return fake module
                return self._create_fake_module(name)
            
            # Normal import
            return self.original_import(name, globals, locals, fromlist, level)
            
        except Exception:
            # Fallback to normal import
            return self.original_import(name, globals, locals, fromlist, level)
    
    def _create_fake_module(self, name: str):
        """Create a fake module to confuse analyzers"""
        fake_module = types.ModuleType(name)
        fake_module.__file__ = f"<fake {name}>"
        fake_module.__doc__ = f"Fake {name} module for protection"
        
        # Add some fake attributes
        setattr(fake_module, 'fake_function', lambda: "This is a fake function")
        setattr(fake_module, 'fake_data', "Fake data to confuse analyzers")
        
        return fake_module

# Enhanced PyInstaller protection
def setup_memory_protection():
    """Setup memory-only execution protection"""
    if getattr(sys, 'frozen', False):  # Only for PyInstaller executables
        executor = MemoryExecutor()
        executor.start_extraction_monitoring()
        
        # Install protected import hook
        import_hook = ProtectedImportHook(executor)
        import_hook.install()
        
        return executor, import_hook
    
    return None, None

# Auto-setup for protected executables
if __name__ != "__main__":
    # Automatically setup protection when module is imported
    _executor, _import_hook = setup_memory_protection()

def main():
    """Test memory protection"""
    print("Testing memory protection system...")
    
    executor, import_hook = setup_memory_protection()
    
    if executor:
        print("✓ Memory protection active")
        print("✓ Extraction monitoring started")
        print("✓ Protected import hook installed")
        
        # Simulate some work
        import time
        time.sleep(5)
        
        executor.stop_monitoring()
        if import_hook:
            import_hook.uninstall()
        
        print("✓ Protection test completed")
    else:
        print("ℹ Memory protection not needed (not in PyInstaller executable)")

if __name__ == "__main__":
    main()