#!/usr/bin/env python3
"""
Runtime Protection System
Provides anti-debugging, anti-analysis, and runtime security measures
"""

import os
import sys
import time
import threading
import hashlib
import random
import subprocess
import ctypes
from pathlib import Path
from typing import Optional, Callable

class RuntimeProtector:
    """Advanced runtime protection system"""
    
    def __init__(self, exit_callback: Optional[Callable] = None):
        self.exit_callback = exit_callback or self._default_exit
        self.protection_active = True
        self.monitor_thread = None
        self.last_check_time = time.time()
        
    def _default_exit(self):
        """Default exit behavior"""
        os._exit(1)
    
    def start_protection(self):
        """Start runtime protection monitoring"""
        print("Starting runtime protection...")
        
        # Initial checks
        self.check_debugger()
        self.check_vm_environment()
        self.check_analysis_tools()
        self.check_integrity()
        
        # Start monitoring thread
        self.monitor_thread = threading.Thread(target=self._monitor_loop, daemon=True)
        self.monitor_thread.start()
        
        print("✓ Runtime protection active")
    
    def stop_protection(self):
        """Stop runtime protection"""
        self.protection_active = False
        if self.monitor_thread:
            self.monitor_thread.join(timeout=1)
    
    def _monitor_loop(self):
        """Continuous monitoring loop"""
        while self.protection_active:
            try:
                # Randomize check intervals
                sleep_time = random.uniform(0.5, 3.0)
                time.sleep(sleep_time)
                
                # Perform checks
                if random.random() < 0.3:  # 30% chance each cycle
                    self.check_debugger()
                
                if random.random() < 0.1:  # 10% chance each cycle
                    self.check_analysis_tools()
                
                if random.random() < 0.05:  # 5% chance each cycle
                    self.check_vm_environment()
                    
                # Update last check time
                self.last_check_time = time.time()
                
            except Exception:
                # Silent failure to avoid revealing protection
                pass
    
    def check_debugger(self) -> bool:
        """Check for debugger presence"""
        try:
            # Python debugger check
            if hasattr(sys, 'gettrace') and sys.gettrace() is not None:
                self._handle_threat("Debugger detected")
                return False
            
            # Windows-specific debugger checks
            if sys.platform == 'win32':
                try:
                    # Check IsDebuggerPresent
                    kernel32 = ctypes.windll.kernel32
                    if kernel32.IsDebuggerPresent():
                        self._handle_threat("Windows debugger detected")
                        return False
                    
                    # Check remote debugger
                    is_remote_debugger = ctypes.c_bool()
                    if kernel32.CheckRemoteDebuggerPresent(
                        kernel32.GetCurrentProcess(),
                        ctypes.byref(is_remote_debugger)
                    ) and is_remote_debugger.value:
                        self._handle_threat("Remote debugger detected")
                        return False
                        
                except Exception:
                    pass
            
            # Timing-based debugger detection
            start_time = time.perf_counter()
            for _ in range(1000):
                pass  # Simple loop
            elapsed = time.perf_counter() - start_time
            
            if elapsed > 0.01:  # Too slow, might be stepping
                self._handle_threat("Timing anomaly detected")
                return False
            
            return True
            
        except Exception:
            return True  # Assume no debugger if check fails
    
    def check_analysis_tools(self) -> bool:
        """Check for analysis and reverse engineering tools"""
        try:
            # List of analysis tools to detect
            analysis_tools = [
                'ida', 'ida64', 'idaq', 'idaq64',
                'ollydbg', 'ollyice', 'x32dbg', 'x64dbg',
                'windbg', 'cdb', 'ntsd',
                'cheat engine', 'cheatengine',
                'processhacker', 'procexp', 'procmon',
                'wireshark', 'tcpview', 'fiddler',
                'apispyxx', 'apimonitor',
                'pestudio', 'peview', 'peid',
                'reshacker', 'resource hacker',
                'hxd', 'hex workshop',
                'python', 'pythonw',  # Detect Python debuggers
                'pycharm', 'vscode', 'sublime'
            ]
            
            # Get running processes
            if sys.platform == 'win32':
                try:
                    result = subprocess.run(
                        ['tasklist', '/fo', 'csv'],
                        capture_output=True,
                        text=True,
                        timeout=5,
                        creationflags=subprocess.CREATE_NO_WINDOW
                    )
                    
                    if result.returncode == 0:
                        process_list = result.stdout.lower()
                        
                        for tool in analysis_tools:
                            if tool in process_list:
                                self._handle_threat(f"Analysis tool detected: {tool}")
                                return False
                except Exception:
                    pass
            
            # Check for suspicious modules
            suspicious_modules = [
                'pydevd', 'pdb', 'bdb', 'trace',
                'debugpy', 'pydbg'
            ]
            
            for module_name in suspicious_modules:
                if module_name in sys.modules:
                    self._handle_threat(f"Debug module detected: {module_name}")
                    return False
            
            return True
            
        except Exception:
            return True
    
    def check_vm_environment(self) -> bool:
        """Check for virtual machine environment"""
        try:
            vm_indicators = []
            
            # Check system information
            if sys.platform == 'win32':
                try:
                    # Check system info
                    result = subprocess.run(
                        ['systeminfo'],
                        capture_output=True,
                        text=True,
                        timeout=10,
                        creationflags=subprocess.CREATE_NO_WINDOW
                    )
                    
                    if result.returncode == 0:
                        system_info = result.stdout.upper()
                        
                        vm_signatures = [
                            'VMWARE', 'VIRTUALBOX', 'VBOX', 'QEMU',
                            'HYPER-V', 'HYPERV', 'KVM', 'XEN',
                            'PARALLELS', 'SANDBOXIE'
                        ]
                        
                        for signature in vm_signatures:
                            if signature in system_info:
                                vm_indicators.append(signature)
                
                except Exception:
                    pass
            
            # Check hardware characteristics
            try:
                # Check number of processors (VMs often have low count)
                cpu_count = os.cpu_count()
                if cpu_count and cpu_count < 2:
                    vm_indicators.append("LOW_CPU_COUNT")
                
                # Check available memory (simplified)
                if sys.platform == 'win32':
                    try:
                        import psutil
                        memory = psutil.virtual_memory()
                        if memory.total < 2 * 1024 * 1024 * 1024:  # Less than 2GB
                            vm_indicators.append("LOW_MEMORY")
                    except ImportError:
                        pass
            
            except Exception:
                pass
            
            # VM detected behavior
            if vm_indicators:
                # Don't exit immediately, just slow down analysis
                delay = random.uniform(5, 30)
                print(f"System analysis delay: {delay:.1f}s")
                time.sleep(delay)
                
                # Add some confusion
                self._add_vm_confusion()
            
            return True
            
        except Exception:
            return True
    
    def _add_vm_confusion(self):
        """Add confusing behavior for VM analysis"""
        try:
            # Create fake files and registry entries
            temp_files = []
            for i in range(random.randint(5, 15)):
                fake_file = Path(os.environ.get('TEMP', '.')) / f"fake_{random.randint(1000, 9999)}.tmp"
                fake_data = os.urandom(random.randint(100, 1000))
                fake_file.write_bytes(fake_data)
                temp_files.append(fake_file)
            
            # Wait a bit then clean up
            time.sleep(random.uniform(1, 3))
            for temp_file in temp_files:
                try:
                    temp_file.unlink()
                except:
                    pass
                    
        except Exception:
            pass
    
    def check_integrity(self) -> bool:
        """Check application integrity"""
        try:
            # Check if current executable has been modified
            current_exe = Path(sys.executable if getattr(sys, 'frozen', False) else __file__)
            
            if current_exe.exists():
                # Simple integrity check
                file_size = current_exe.stat().st_size
                current_time = time.time()
                file_mtime = current_exe.stat().st_mtime
                
                # Check if file was modified very recently (might indicate tampering)
                if current_time - file_mtime < 300:  # 5 minutes
                    self._handle_threat("Recent file modification detected")
                    return False
                
                # Check for unusual file size (very basic check)
                if file_size < 1000:  # Too small to be real
                    self._handle_threat("Suspicious file size")
                    return False
            
            return True
            
        except Exception:
            return True
    
    def _handle_threat(self, threat_type: str):
        """Handle detected threat"""
        print(f"Security threat detected: {threat_type}")
        
        # Add some delay to confuse analyzers
        time.sleep(random.uniform(0.1, 1.0))
        
        # Random chance to not exit immediately (confuse automated analysis)
        if random.random() < 0.1:  # 10% chance to continue
            return
        
        # Call exit callback
        self.exit_callback()
    
    def derive_runtime_key(self, base_key: str, context: str = "") -> bytes:
        """Derive encryption key at runtime"""
        try:
            # Gather system-specific information
            system_info = []
            
            # Current executable path and size
            try:
                exe_path = Path(sys.executable if getattr(sys, 'frozen', False) else __file__)
                if exe_path.exists():
                    system_info.append(str(exe_path.stat().st_size))
                    system_info.append(str(exe_path.stat().st_mtime))
            except:
                pass
            
            # System characteristics
            system_info.extend([
                str(os.cpu_count() or 0),
                str(hash(os.environ.get('COMPUTERNAME', 'unknown'))),
                str(hash(os.environ.get('USERNAME', 'unknown'))),
                context,
                base_key
            ])
            
            # Current date (changes daily)
            system_info.append(str(int(time.time() / 86400)))
            
            # Create deterministic but system-specific key
            key_material = '|'.join(system_info).encode('utf-8')
            derived_key = hashlib.sha256(key_material).digest()
            
            return derived_key
            
        except Exception:
            # Fallback to simple key derivation
            fallback_material = (base_key + context + str(int(time.time() / 86400))).encode()
            return hashlib.sha256(fallback_material).digest()
    
    def get_dynamic_nonce(self, seed: str = "") -> bytes:
        """Generate dynamic nonce based on runtime state"""
        try:
            # Use runtime characteristics for nonce
            nonce_material = [
                str(int(time.time() / 3600)),  # Changes hourly
                str(os.getpid()),
                str(threading.get_ident()),
                seed,
                str(random.getrandbits(32))
            ]
            
            nonce_data = '|'.join(nonce_material).encode()
            nonce_hash = hashlib.sha256(nonce_data).digest()
            
            # Return first 12 bytes for AES-GCM
            return nonce_hash[:12]
            
        except Exception:
            # Fallback nonce
            fallback = (seed + str(int(time.time()))).encode()
            return hashlib.md5(fallback).digest()[:12]

# Global protector instance
_protector_instance = None

def initialize_protection(exit_callback: Optional[Callable] = None):
    """Initialize runtime protection"""
    global _protector_instance
    
    if _protector_instance is None:
        _protector_instance = RuntimeProtector(exit_callback)
        _protector_instance.start_protection()
    
    return _protector_instance

def get_protector() -> Optional[RuntimeProtector]:
    """Get current protector instance"""
    return _protector_instance

def shutdown_protection():
    """Shutdown runtime protection"""
    global _protector_instance
    
    if _protector_instance:
        _protector_instance.stop_protection()
        _protector_instance = None

def derive_key(base_key: str, context: str = "") -> bytes:
    """Convenience function to derive keys"""
    protector = get_protector()
    if protector:
        return protector.derive_runtime_key(base_key, context)
    else:
        # Fallback if protection not initialized
        material = (base_key + context + str(int(time.time() / 86400))).encode()
        return hashlib.sha256(material).digest()

def get_nonce(seed: str = "") -> bytes:
    """Convenience function to get dynamic nonce"""
    protector = get_protector()
    if protector:
        return protector.get_dynamic_nonce(seed)
    else:
        # Fallback if protection not initialized
        material = (seed + str(int(time.time()))).encode()
        return hashlib.md5(material).digest()[:12]

if __name__ == "__main__":
    # Test runtime protection
    print("Testing runtime protection...")
    
    protector = initialize_protection()
    
    print("Protection initialized. Testing key derivation...")
    
    # Test key derivation
    key1 = derive_key("test_key", "context1")
    key2 = derive_key("test_key", "context2")
    
    print(f"Key 1: {key1.hex()[:16]}...")
    print(f"Key 2: {key2.hex()[:16]}...")
    print(f"Keys different: {key1 != key2}")
    
    # Test nonce generation
    nonce1 = get_nonce("test")
    nonce2 = get_nonce("test")
    
    print(f"Nonce 1: {nonce1.hex()}")
    print(f"Nonce 2: {nonce2.hex()}")
    
    print("Runtime protection test completed")
    time.sleep(2)
    shutdown_protection()