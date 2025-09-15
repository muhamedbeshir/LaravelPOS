@echo off
powershell -Command "Get-WmiObject Win32_BaseBoard | ForEach-Object { $_.SerialNumber } | clip"
powershell -Command "Add-Type -AssemblyName PresentationFramework; [System.Windows.MessageBox]::Show('Motherboard Serial Number copied to clipboard!', 'Done')"
