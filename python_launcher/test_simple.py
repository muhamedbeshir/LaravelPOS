#!/usr/bin/env python3
"""
Simple test application to verify PyInstaller works
"""

import sys
import tkinter as tk
from tkinter import messagebox

def main():
    """Simple test application"""
    try:
        # Create a simple GUI window
        root = tk.Tk()
        root.withdraw()  # Hide the main window
        
        # Show a test message
        messagebox.showinfo("Test", "BulldozerSystem Test Application\n\nThis is a test to verify the executable works correctly.")
        
        # Exit cleanly
        root.destroy()
        sys.exit(0)
        
    except Exception as e:
        # Show error in GUI
        try:
            messagebox.showerror("Error", f"Application error: {e}")
        except:
            print(f"Error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main() 