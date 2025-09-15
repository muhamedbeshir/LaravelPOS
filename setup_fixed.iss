;-------------------------------------------------------------------
; Bulldozer Market :: Inno Setup Installer
; Compatible with Inno Setup 6
;-------------------------------------------------------------------

#define AppName "Bulldozer Market"
#define AppVersion "1.0.0"
#define AppPublisher "Your Company"
#define AppGUID "{A1234567-B890-C123-D456-E7890ABCDEF1}"
#define SourceDir "cache"
#define ContactInfo "مهندس/ أحمد تهامي السيد - 01500001487"
#define CacheID "bmt12345"

[Setup]
; Application metadata
AppId={{#AppGUID}}
AppName={#AppName}
AppVersion={#AppVersion}
AppPublisher={#AppPublisher}

; Install location
DefaultDirName={autopf}\{#AppName}
DefaultGroupName={#AppName}
DisableDirPage=no
DisableProgramGroupPage=yes
AlwaysShowDirOnReadyPage=no

; Output file
OutputBaseFilename=BulldozerMarket_Setup
OutputDir=.\

; Compression
Compression=lzma2
SolidCompression=yes

; Permissions and architecture
PrivilegesRequired=admin
ArchitecturesInstallIn64BitMode=x64

; Enable logging
SetupLogging=yes

; Password protection
Password=5897455987

; Allow component selection
AllowNoIcons=yes
AlwaysShowComponentsList=yes
DisableReadyPage=no
DisableReadyMemo=no

; UI settings - minimize details shown
ShowTasksTreeLines=no
SetupMutex=BulldozerMarketSetupMutex
DisableStartupPrompt=yes
DisableFinishedPage=no
DisableWelcomePage=no
AppendDefaultDirName=no

[Types]
Name: "full"; Description: "Full installation"
Name: "custom"; Description: "Custom installation"; Flags: iscustom

[Components]
Name: "app"; Description: "Bulldozer Market Application"; Types: full custom; Flags: fixed
Name: "mysql"; Description: "MySQL Database Server"; Types: full custom; Flags: fixed
Name: "vcredist"; Description: "Microsoft Visual C++ Redistributable"; Types: full custom; Flags: fixed
Name: "extract"; Description: "Extract Dependencies (Recommended)"; Types: full custom; Flags: fixed
Name: "migrations"; Description: "Run Database Migrations"; Types: full custom

[Files]
; Main launcher executable
Source: "{#SourceDir}\rust_launcher.exe"; DestDir: "{app}"; Flags: ignoreversion; Components: app

; Prerequisites (installed during setup)
Source: "{#SourceDir}\mysql-installer.msi"; DestDir: "{tmp}"; Flags: deleteafterinstall; Components: mysql
Source: "{#SourceDir}\VC_redist.x64.exe"; DestDir: "{tmp}"; Flags: deleteafterinstall; Components: vcredist

; Compressed application files (will be extracted during installation)
Source: "{#SourceDir}\vendor.zip"; DestDir: "{tmp}"; Flags: ignoreversion; Components: extract
Source: "{#SourceDir}\node_modules.zip"; DestDir: "{tmp}"; Flags: ignoreversion; Components: extract
Source: "{#SourceDir}\php_runtime.zip"; DestDir: "{tmp}"; Flags: ignoreversion; Components: extract
Source: "{#SourceDir}\artisan.zip"; DestDir: "{tmp}"; Flags: ignoreversion; Components: extract
Source: "{#SourceDir}\migrations.zip"; DestDir: "{tmp}"; Flags: ignoreversion; Components: migrations

[Icons]
Name: "{group}\View Installation Log"; Filename: "notepad.exe"; Parameters: """{app}\install_log.txt"""; Flags: createonlyiffileexists

[Run]
; Install VC++ Runtime
Filename: "{tmp}\VC_redist.x64.exe"; Parameters: "/quiet /norestart"; \
  StatusMsg: "Installing Visual C++ Redistributable..."; \
  Flags: runhidden waituntilterminated shellexec; Components: vcredist

; Install MySQL Installer first
Filename: "msiexec.exe"; Parameters: "/i ""{tmp}\mysql-installer.msi"" /qn"; \
  StatusMsg: "Installing MySQL Installer..."; \
  Flags: waituntilterminated runhidden shellexec; Components: mysql

; Then use MySQL Installer to install MySQL Server silently
Filename: "{pf}\MySQL\MySQL Installer - Community\MySQLInstallerConsole.exe"; \
  Parameters: "community install server;8.0.32;x64:*:type=config;openfirewall=true;generallog=true;binlog=true;serverid=1;enable_tcpip=true;port=3306;rootpasswd=ahmed -silent"; \
  StatusMsg: "Installing MySQL Server..."; \
  Flags: waituntilterminated shellexec; Components: mysql

; Configure MySQL with our script
Filename: "{cmd}"; Parameters: "/C ""{tmp}\db_setup.bat"""; \
  StatusMsg: "Configuring Database..."; \
  Flags: runhidden waituntilterminated; Components: mysql

; Run migrations if selected
Filename: "{app}\run_migrations.bat"; Description: "Run database migrations"; \
  StatusMsg: "Running Database Migrations..."; \
  Flags: nowait postinstall skipifsilent; Components: migrations

[UninstallRun]
; Stop and remove MySQL service
Filename: "{cmd}"; Parameters: "/C ""sc stop MySQL80 & sc delete MySQL80"""; \
  Flags: runhidden

; Remove cache folder
Filename: "{cmd}"; Parameters: "/C ""rd /s /q ""{app}\Cache"""""; \
  Flags: runhidden

[Messages]
; Generic messages without paths
PasswordLabel=Please enter the installation password to continue:
PasswordTitle=Password Required
BadPassword=The password you entered is incorrect. Please try again.
StatusExtractFiles=Installing files...
StatusExtractingLabel=Installing files. Please wait...
ExtractionProgress=Installing...
SetupAppTitle=Install
SetupWindowTitle={#AppName} Setup
WizardInstalling=Installing
WizardInstallingSubCaption=Please wait while {#AppName} is being installed.
SelectDirDesc=Select Destination Location
SelectStartMenuFolderDesc=Select Start Menu Folder
StatusRunProgram=Finishing installation...
SetupLdrStartupMessage=This will install {#AppName}. Do you wish to continue?
WelcomeLabel1=Welcome to the {#AppName} Setup Wizard
WelcomeLabel2=This will install {#AppName} on your computer.%n%nIt is recommended that you close all other applications before continuing.
FinishedHeadingLabel=Completing the {#AppName} Setup Wizard
ClickFinish=Click Finish to exit Setup.
StatusCreateUninstaller=Creating uninstaller...
SelectDirLabel3=Setup will install {#AppName} into the following folder.

[Dirs]
Name: "{app}\Cache"; Permissions: everyone-modify
Name: "{app}\Cache\{#CacheID}"; Permissions: everyone-modify

[Code]
var
  LogFile: string;
  ContactHeaderLabel: TNewStaticText;

{ Log a message to our custom log }
procedure LogMessage(Message: string);
var
  LogFileContents: TStringList;
begin
  // Set up the log file path if not already set
  if LogFile = '' then
    LogFile := ExpandConstant('{app}\install_log.txt');
  
  // Create or append to the log file
  LogFileContents := TStringList.Create;
  try
    // Add header if new file
    if not FileExists(LogFile) then
    begin
      LogFileContents.Add('=== Bulldozer Market Installation Log ===');
      LogFileContents.Add('Date: ' + GetDateTimeString('yyyy-mm-dd hh:nn:ss', '-', ':'));
      LogFileContents.Add('');
    end
    else
    begin
      try
        LogFileContents.LoadFromFile(LogFile);
      except
        // Just continue if we can't load existing file
      end;
    end;
    
    // Add the new log entry
    LogFileContents.Add(GetDateTimeString('hh:nn:ss', '-', ':') + ' - ' + Message);
    
    // Save the updated log
    try
      LogFileContents.SaveToFile(LogFile);
    except
      // Continue even if we can't write the log
    end;
  finally
    LogFileContents.Free;
  end;
end;

{ Create batch file for MySQL setup }
procedure CreateDatabaseBatchFile;
var
  Contents: TStringList;
begin
  LogMessage('Creating database setup script');
  Contents := TStringList.Create;
  try
    Contents.Add('@echo off');
    Contents.Add('echo ========================================');
    Contents.Add('echo Bulldozer Market - Database Setup');
    Contents.Add('echo ========================================');
    Contents.Add('echo.');
    
    // Wait for MySQL to be ready
    Contents.Add('echo Waiting for MySQL service to start...');
    Contents.Add('timeout /t 15 /nobreak >nul');
    
    // Try to find MySQL in potential installation locations
    Contents.Add('set MYSQL_CMD=""');
    Contents.Add('if exist "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" (');
    Contents.Add('  set MYSQL_CMD="C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"');
    Contents.Add(') else if exist "%ProgramFiles%\MySQL\MySQL Server 8.0\bin\mysql.exe" (');
    Contents.Add('  set MYSQL_CMD="%ProgramFiles%\MySQL\MySQL Server 8.0\bin\mysql.exe"');
    Contents.Add(') else if exist "%ProgramFiles(x86)%\MySQL\MySQL Server 8.0\bin\mysql.exe" (');
    Contents.Add('  set MYSQL_CMD="%ProgramFiles(x86)%\MySQL\MySQL Server 8.0\bin\mysql.exe"');
    Contents.Add(')');
    
    // Create database if MySQL was found
    Contents.Add('if not %MYSQL_CMD% == "" (');
    Contents.Add('  echo Creating database using %MYSQL_CMD%');
    Contents.Add('  %MYSQL_CMD% -u root -pahmed -e "CREATE DATABASE IF NOT EXISTS ahmed275; GRANT ALL ON ahmed275.* TO ''root''@''localhost''; FLUSH PRIVILEGES;" >nul 2>&1');
    Contents.Add('  if %ERRORLEVEL% EQU 0 (');
    Contents.Add('    echo Database created successfully.');
    Contents.Add('  ) else (');
    Contents.Add('    echo Warning: Could not create database. It might already exist.');
    Contents.Add('  )');
    Contents.Add(') else (');
    Contents.Add('  echo ERROR: MySQL not found. Please check your MySQL installation.');
    Contents.Add('  echo The database will need to be created manually.');
    Contents.Add(')');
    
    Contents.Add('echo Database setup completed.');
    Contents.Add('exit /b 0');
    Contents.SaveToFile(ExpandConstant('{tmp}\db_setup.bat'));
  finally
    Contents.Free;
  end;
end;

{ Check if a file exists }
function IsValidFile(FilePath: string): Boolean;
begin
  Result := FileExists(FilePath);
end;

{ Extract a file using PowerShell - using a completely different approach without SaveStringToFile }
procedure ExtractFile(ZipFile, DestDir: string);
var
  ResultCode: Integer;
  TempBatchFile: string;
  TempBatch: TStringList;
  PSCmd: string;
begin
  // Make sure we have a valid destination path with a drive letter
  if Copy(DestDir, 2, 1) <> ':' then
    DestDir := ExpandConstant('{app}\Cache\{#CacheID}');
  
  // Create destination directory if it doesn't exist
  ForceDirectories(DestDir);
  
  // Log the extraction
  LogMessage('Extracting ' + ExtractFileName(ZipFile) + ' to ' + DestDir);
  
  // Create a simple PowerShell command instead of a script file
  PSCmd := 'Add-Type -AssemblyName System.IO.Compression.FileSystem; ' +
           '[System.IO.Compression.ZipFile]::ExtractToDirectory(''' + ZipFile + ''', ''' + DestDir + ''')';
  
  // Create a batch file that will run the PowerShell command
  TempBatchFile := ExpandConstant('{tmp}\extract.bat');
  TempBatch := TStringList.Create;
  try
    TempBatch.Add('@echo off');
    TempBatch.Add('powershell -ExecutionPolicy Bypass -Command "' + PSCmd + '"');
    TempBatch.Add('if %ERRORLEVEL% neq 0 (');
    TempBatch.Add('  echo Attempting individual file extraction...');
    TempBatch.Add('  powershell -ExecutionPolicy Bypass -Command "');
    TempBatch.Add('    $zip = [System.IO.Compression.ZipFile]::OpenRead(''' + ZipFile + ''');');
    TempBatch.Add('    foreach ($entry in $zip.Entries) {');
    TempBatch.Add('      $entryPath = [System.IO.Path]::Combine(''' + DestDir + ''', $entry.FullName);');
    TempBatch.Add('      $entryDir = [System.IO.Path]::GetDirectoryName($entryPath);');
    TempBatch.Add('      if (!(Test-Path $entryDir)) {');
    TempBatch.Add('        New-Item -ItemType Directory -Path $entryDir -Force | Out-Null;');
    TempBatch.Add('      }');
    TempBatch.Add('      if (!$entry.FullName.EndsWith(''/'')) {');
    TempBatch.Add('        try { [System.IO.Compression.ZipFileExtensions]::ExtractToFile($entry, $entryPath, $true); }');
    TempBatch.Add('        catch { }');
    TempBatch.Add('      }');
    TempBatch.Add('    }');
    TempBatch.Add('    $zip.Dispose();');
    TempBatch.Add('  "');
    TempBatch.Add('  if %ERRORLEVEL% neq 0 (');
    TempBatch.Add('    exit /b 2');
    TempBatch.Add('  )');
    TempBatch.Add(') else (');
    TempBatch.Add('  exit /b 0');
    TempBatch.Add(')');
    TempBatch.SaveToFile(TempBatchFile);
  finally
    TempBatch.Free;
  end;
  
  // Run the batch file
  if not Exec(ExpandConstant('{cmd}'), '/c "' + TempBatchFile + '"', '', SW_HIDE, ewWaitUntilTerminated, ResultCode) then
    LogMessage('Warning: Extraction failed to execute for ' + ZipFile);
    
  // If PowerShell method fails, try using the Shell.Application object
  if ResultCode <> 0 then begin
    LogMessage('Error: PowerShell extraction failed for ' + ZipFile + ' - trying Windows Shell method');
    
    TempBatch := TStringList.Create;
    try
      TempBatch.Add('@echo off');
      TempBatch.Add('echo Set shell = CreateObject("Shell.Application") > "%TEMP%\extract.vbs"');
      TempBatch.Add('echo Set source = shell.NameSpace("' + ZipFile + '").Items >> "%TEMP%\extract.vbs"');
      TempBatch.Add('echo Set target = shell.NameSpace("' + DestDir + '") >> "%TEMP%\extract.vbs"');
      TempBatch.Add('echo target.CopyHere source, 16 >> "%TEMP%\extract.vbs"');
      TempBatch.Add('cscript //nologo "%TEMP%\extract.vbs"');
      TempBatch.Add('del "%TEMP%\extract.vbs"');
      TempBatch.SaveToFile(TempBatchFile);
    finally
      TempBatch.Free;
    end;
    
    if Exec(ExpandConstant('{cmd}'), '/c "' + TempBatchFile + '"', '', SW_HIDE, ewWaitUntilTerminated, ResultCode) then
      LogMessage('Shell extraction completed for ' + ZipFile)
    else
      LogMessage('Fatal: All extraction methods failed for ' + ZipFile);
  end
  else
    LogMessage('Successfully extracted ' + ZipFile);
    
  // Clean up the batch file
  DeleteFile(TempBatchFile);
end;

{ Initialize setup and create log file }
function InitializeSetup(): Boolean;
begin
  // Initialize the log file path
  LogFile := ExpandConstant('{tmp}\install_temp.log');
  
  // Start the log
  LogMessage('Setup initialized');
  
  Result := True;
end;

{ Override the default messages in wizard forms }
procedure InitializeWizard;
begin
  // Set explicit directory value with drive letter to avoid error
  WizardForm.DirEdit.Text := ExpandConstant('{autopf}\{#AppName}');
  
  // Hide paths in all visible text areas
  WizardForm.DiskSpaceLabel.Visible := False;
  
  // Create persistent contact info header
  ContactHeaderLabel := TNewStaticText.Create(WizardForm);
  ContactHeaderLabel.Parent := WizardForm;
  ContactHeaderLabel.Caption := '{#ContactInfo}';
  ContactHeaderLabel.Font.Size := 10;
  ContactHeaderLabel.Font.Style := [fsBold];
  ContactHeaderLabel.Font.Color := clNavy;
  ContactHeaderLabel.AutoSize := False;
  ContactHeaderLabel.Width := WizardForm.ClientWidth - 20;
  ContactHeaderLabel.Height := ScaleY(24);
  ContactHeaderLabel.Top := 0;
  ContactHeaderLabel.Left := 10;
  // Text alignment - center
  ContactHeaderLabel.ShowAccelChar := False;
  ContactHeaderLabel.WordWrap := True;
  
  // Adjust wizard form to accommodate header
  WizardForm.InnerNotebook.Top := WizardForm.InnerNotebook.Top + ContactHeaderLabel.Height;
  WizardForm.Bevel.Top := WizardForm.Bevel.Top + ContactHeaderLabel.Height;
  WizardForm.OuterNotebook.ClientHeight := WizardForm.OuterNotebook.ClientHeight - ContactHeaderLabel.Height;
  
  // Make sure components are visible
  WizardForm.ComponentsList.Visible := True;
end;

{ Keep contact header visible on all pages }
procedure CurPageChanged(CurPageID: Integer);
begin
  // Ensure header is always on top
  if Assigned(ContactHeaderLabel) then
    ContactHeaderLabel.BringToFront;
    
  // Hide components list details on Ready page
  if CurPageID = wpReady then
    WizardForm.ReadyMemo.Visible := False;

  // Ensure directory has drive letter on directory page
  if CurPageID = wpSelectDir then
    WizardForm.DirEdit.Text := ExpandConstant('{autopf}\{#AppName}');
end;

{ Override the extraction UI messages }
procedure CurInstallProgressChanged(CurProgress, MaxProgress: Integer);
begin
  // Keep a generic message during installation, regardless of what's happening
  WizardForm.StatusLabel.Caption := 'Installing...';
  WizardForm.FilenameLabel.Caption := '';  // Hide filename
  
  // Keep header visible
  if Assigned(ContactHeaderLabel) then
    ContactHeaderLabel.BringToFront;
end;

{ After install, copy log to permanent location }
procedure DeinitializeSetup();
var
  TempLog, FinalLog: string;
  LogContents: TStringList;
begin
  // Copy log from temp location to final location
  TempLog := ExpandConstant('{tmp}\install_temp.log');
  FinalLog := ExpandConstant('{app}\install_log.txt');
  
  if FileExists(TempLog) then
  begin
    LogContents := TStringList.Create;
    try
      LogContents.LoadFromFile(TempLog);
      LogContents.Add('');
      LogContents.Add('Setup completed at: ' + GetDateTimeString('yyyy-mm-dd hh:nn:ss', '-', ':'));
      LogContents.SaveToFile(FinalLog);
    finally
      LogContents.Free;
    end;
  end;
end;

{ Create the migration script that will run Laravel migrations }
procedure CreateMigrationScript(AppPath, CachePath: string);
var
  Contents: TStringList;
  PhpPath: string;
  MigrationDir: string;
begin
  LogMessage('Creating database migration script');
  Contents := TStringList.Create;
  
  // PHP will be in the cache path
  PhpPath := CachePath + '\php_runtime\php.exe';
  
  // Create temp directory for migrations
  MigrationDir := ExpandConstant('{tmp}\migrations_extracted');
  
  try
    Contents.Add('@echo off');
    Contents.Add('echo ========================================');
    Contents.Add('echo Bulldozer Market - Database Migrations');
    Contents.Add('echo ========================================');
    Contents.Add('echo.');
    
    // Output debug information to help diagnose issues
    Contents.Add('echo Debug information:');
    Contents.Add('echo Working directory: %CD%');
    Contents.Add('echo App path: ' + AppPath);
    Contents.Add('echo Cache path: ' + CachePath);
    Contents.Add('echo PHP path: ' + PhpPath);
    Contents.Add('echo Migration temp dir: ' + MigrationDir);
    Contents.Add('echo.');
    
    // Check if PHP exists
    Contents.Add('if not exist "' + PhpPath + '" (');
    Contents.Add('  echo ERROR: PHP executable not found at: ' + PhpPath);
    Contents.Add('  echo Checking for PHP in PATH...');
    Contents.Add('  where php >nul 2>&1');
    Contents.Add('  if %ERRORLEVEL% equ 0 (');
    Contents.Add('    echo Found PHP in PATH, using that instead.');
    Contents.Add('    set PHP_EXE=php');
    Contents.Add('  ) else (');
    Contents.Add('    echo ERROR: PHP not found in PATH either.');
    Contents.Add('    echo Cannot proceed with migrations.');
    Contents.Add('    pause >nul');
    Contents.Add('    exit /b 1');
    Contents.Add('  )');
    Contents.Add(') else (');
    Contents.Add('  set PHP_EXE="' + PhpPath + '"');
    Contents.Add(')');
    
    // Check if migrations.zip was extracted
    Contents.Add('if not exist "' + MigrationDir + '\database\migrations" (');
    Contents.Add('  echo ERROR: Migration files not found.');
    Contents.Add('  echo Please make sure migrations.zip was included in the cache directory.');
    Contents.Add('  pause >nul');
    Contents.Add('  exit /b 1');
    Contents.Add(')');
    
    // Create a temp working directory to avoid permission issues
    Contents.Add('echo Setting up temporary working directory...');
    Contents.Add('cd /d "%TEMP%\bulldozer-migrations"');
    
    // Use .env from migrations.zip if available, otherwise create one
    Contents.Add('if exist "' + MigrationDir + '\.env" (');
    Contents.Add('  echo Using existing .env file...');
    Contents.Add('  copy "' + MigrationDir + '\.env" .env >nul');
    Contents.Add(') else (');
    Contents.Add('  echo Creating default .env file...');
    Contents.Add('  (');
    Contents.Add('  echo APP_NAME="Bulldozer Market"');
    Contents.Add('  echo APP_ENV=local');
    Contents.Add('  echo APP_DEBUG=true');
    Contents.Add('  echo APP_URL=http://localhost');
    Contents.Add('  echo.');
    Contents.Add('  echo DB_CONNECTION=mysql');
    Contents.Add('  echo DB_HOST=127.0.0.1');
    Contents.Add('  echo DB_PORT=3306');
    Contents.Add('  echo DB_DATABASE=ahmed275');
    Contents.Add('  echo DB_USERNAME=root');
    Contents.Add('  echo DB_PASSWORD=ahmed');
    Contents.Add('  echo.');
    Contents.Add('  echo CACHE_DRIVER=file');
    Contents.Add('  echo SESSION_DRIVER=file');
    Contents.Add('  echo QUEUE_DRIVER=sync');
    Contents.Add('  ) > .env');
    Contents.Add(')');
    
    // Copy artisan file and set up directories
    Contents.Add('echo Setting up Laravel structure...');
    Contents.Add('mkdir app 2>nul');
    Contents.Add('mkdir bootstrap 2>nul');
    Contents.Add('mkdir config 2>nul');
    Contents.Add('mkdir database 2>nul');
    Contents.Add('mkdir database\\migrations 2>nul');
    Contents.Add('mkdir database\\seeders 2>nul');
    
    // Copy folders from the extracted migrations.zip
    Contents.Add('echo Copying migration files from extracted zip...');
    Contents.Add('if exist "' + MigrationDir + '\app" xcopy /E /Y /I "' + MigrationDir + '\app\*" "app\" >nul');
    Contents.Add('if exist "' + MigrationDir + '\bootstrap" xcopy /E /Y /I "' + MigrationDir + '\bootstrap\*" "bootstrap\" >nul');
    Contents.Add('if exist "' + MigrationDir + '\config" xcopy /E /Y /I "' + MigrationDir + '\config\*" "config\" >nul');
    Contents.Add('if exist "' + MigrationDir + '\database\migrations" xcopy /E /Y /I "' + MigrationDir + '\database\migrations\*" "database\migrations\" >nul');
    Contents.Add('if exist "' + MigrationDir + '\database\seeders" xcopy /E /Y /I "' + MigrationDir + '\database\seeders\*" "database\seeders\" >nul');
    
    // Copy artisan file
    Contents.Add('echo Copying artisan file...');
    Contents.Add('copy "' + MigrationDir + '\artisan" artisan 2>nul');
    Contents.Add('if not exist "artisan" copy "' + CachePath + '\artisan" artisan 2>nul');
    Contents.Add('if not exist "artisan" (');
    Contents.Add('  echo ERROR: artisan file not found!');
    Contents.Add('  pause >nul');
    Contents.Add('  exit /b 1');
    Contents.Add(')');
    
    // Ensure vendor path is properly configured
    Contents.Add('echo Setting up environment variables...');
    Contents.Add('set COMPOSER_VENDOR_DIR=' + CachePath + '\vendor');
    Contents.Add('set PATH=%PATH%;' + CachePath + '\php_runtime');
    
    // Create symbolic link to vendor directory to ensure Laravel can find it
    Contents.Add('if exist "' + CachePath + '\vendor" (');
    Contents.Add('  echo Creating vendor symlink...');
    Contents.Add('  mklink /D vendor "' + CachePath + '\vendor" >nul 2>&1');
    Contents.Add('  if %ERRORLEVEL% neq 0 (');
    Contents.Add('    echo Could not create symlink, copying essential files instead...');
    Contents.Add('    mkdir vendor\laravel 2>nul');
    Contents.Add('  )');
    Contents.Add(')');
    
    // Show database connection info
    Contents.Add('echo.');
    Contents.Add('echo Using the following database connection:');
    Contents.Add('echo Host: 127.0.0.1');
    Contents.Add('echo Database: ahmed275');
    Contents.Add('echo Username: root');
    Contents.Add('echo Password: ahmed');
    Contents.Add('echo.');
    Contents.Add('echo Make sure MySQL is running and the database exists.');
    Contents.Add('echo Press any key to continue...');
    Contents.Add('pause >nul');
    
    // Run the migrations
    Contents.Add('echo.');
    Contents.Add('echo Running migrations...');
    Contents.Add('if exist artisan (');
    Contents.Add('  %PHP_EXE% artisan migrate --force');
    Contents.Add(') else (');
    Contents.Add('  echo ERROR: artisan file not found!');
    Contents.Add(')');
    
    // Option to fresh migrate and seed
    Contents.Add('echo.');
    Contents.Add('echo Would you like to reset the database and run all migrations with seed data?');
    Contents.Add('echo WARNING: This will delete all existing data!');
    Contents.Add('set /p FRESH_OPTION="Type YES to continue or anything else to skip: "');
    Contents.Add('if /i "%FRESH_OPTION%"=="YES" (');
    Contents.Add('  echo Running fresh migrations with seeding...');
    Contents.Add('  if exist artisan (');
    Contents.Add('    %PHP_EXE% artisan migrate:fresh --seed --force');
    Contents.Add('  ) else (');
    Contents.Add('    echo ERROR: artisan file not found!');
    Contents.Add('  )');
    Contents.Add(') else (');
    Contents.Add('  echo Fresh migrations skipped.');
    Contents.Add(')');
    
    // Clean up
    Contents.Add('echo.');
    Contents.Add('echo Cleaning up temporary files...');
    Contents.Add('cd /d "%TEMP%"');
    
    // Add pause to see the output
    Contents.Add('echo.');
    Contents.Add('echo Migrations completed.');
    Contents.Add('pause >nul');
    
    // Save the batch file
    Contents.SaveToFile(AppPath + '\run_migrations.bat');
    LogMessage('Migration script created successfully');
  finally
    Contents.Free;
  end;
end;

{ Steps to perform after files are copied }
procedure CurStepChanged(CurStep: TSetupStep);
var
  CachePath, AppPath: string;
  TempBatch: TStringList;
  TempBatchFile: string;
  ResultCode: Integer;
  MigrationDir: string;
begin
  { Only run during post-install }
  if CurStep = ssPostInstall then
  begin
    LogMessage('Beginning post-installation tasks');
    AppPath := ExpandConstant('{app}');
    
    // Always install prerequisites
    LogMessage('Installing prerequisites');
    
    // Create database setup script
    CreateDatabaseBatchFile;
    
    // Verify VC++ Redistributable file
    if not FileExists(ExpandConstant('{tmp}\VC_redist.x64.exe')) then
      LogMessage('ERROR: VC_redist.x64.exe not found in cache directory!');
    
    // Verify MySQL installer file
    if not FileExists(ExpandConstant('{tmp}\mysql-installer.msi')) then
      LogMessage('ERROR: mysql-installer.msi not found in cache directory!');
    
    // Show status message
    WizardForm.StatusLabel.Caption := 'Installing dependencies...';

    { Only extract files if the extract component is selected }
    if WizardIsComponentSelected('extract') then
    begin
      LogMessage('Setting up application cache directory');
      
      { Setup Cache directory with fixed ID and ensure it has a drive letter }
      CachePath := AppPath + '\Cache\{#CacheID}';
      ForceDirectories(CachePath);
      
      { Extract all ZIP files to the same directory }
      WizardForm.StatusLabel.Caption := 'Installing...';
      ExtractFile(ExpandConstant('{tmp}\vendor.zip'), CachePath);
      ExtractFile(ExpandConstant('{tmp}\node_modules.zip'), CachePath);
      ExtractFile(ExpandConstant('{tmp}\php_runtime.zip'), CachePath);
      ExtractFile(ExpandConstant('{tmp}\artisan.zip'), CachePath);
      
      { Create shortcut file to artisan in app directory }
      FileCopy(CachePath + '\artisan', ExpandConstant('{app}\artisan'), False);
      
      { Extract migrations ZIP if component is selected }
      if WizardIsComponentSelected('migrations') then
      begin
        LogMessage('Extracting migrations files');
        MigrationDir := ExpandConstant('{tmp}\migrations_extracted');
        ForceDirectories(MigrationDir);
        
        // Create temp directory for migrations extraction
        // Use ForceDirectories instead of DirectoryExists
        ForceDirectories(MigrationDir);
           
        // Extract migrations zip to temp directory
        ExtractFile(ExpandConstant('{tmp}\migrations.zip'), MigrationDir);
        
        // Create migration script
        CreateMigrationScript(AppPath, CachePath);
        
        // Create a temporary directory for the migrations
        ForceDirectories(ExpandConstant('{tmp}\bulldozer-migrations'));
      end;
      
      { Log completion }
      LogMessage('Installation completed successfully');
    end;
  end;
end;

{ Check for required components }
function NextButtonClick(CurPageID: Integer): Boolean;
var
  NewComponentStates: array of Boolean;
  ComponentCount: Integer;
begin
  Result := True;
  
  if CurPageID = wpSelectComponents then begin
    // Get the component count for array initialization
    ComponentCount := 5; // app, mysql, vcredist, extract, migrations
    
    // Initialize component states array
    SetArrayLength(NewComponentStates, ComponentCount);
    
    // Set component states (true = selected, false = unselected)
    // app (0), mysql (1), vcredist (2), extract (3) should be true (fixed)
    // migrations (4) can be left as is
    NewComponentStates[0] := True; // app - always selected
    NewComponentStates[1] := True; // mysql - always selected
    NewComponentStates[2] := True; // vcredist - always selected
    NewComponentStates[3] := True; // extract - always selected
    
    // Keep migrations as user selected it
    if WizardIsComponentSelected('migrations') then
      NewComponentStates[4] := True
    else
      NewComponentStates[4] := False;
    
    // Check if we need to show messages and enforce component selection
    if not WizardIsComponentSelected('mysql') then begin
      MsgBox('MySQL Database Server is required for the application to function correctly. It will be installed.', mbInformation, MB_OK);
      WizardForm.ComponentsList.Checked[1] := True;
    end;
    
    if not WizardIsComponentSelected('vcredist') then begin
      MsgBox('Visual C++ Redistributable is required for the application to function correctly. It will be installed.', mbInformation, MB_OK);
      WizardForm.ComponentsList.Checked[2] := True;
    end;
    
    if not WizardIsComponentSelected('extract') then begin
      MsgBox('Application dependencies are required and will be extracted.', mbInformation, MB_OK);
      WizardForm.ComponentsList.Checked[3] := True;
    end;
  end;
end; 