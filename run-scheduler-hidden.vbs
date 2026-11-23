' Runs the Laravel scheduler once, with no console window.
' Point a Windows Task Scheduler task at this file and repeat it every minute.
Set objFSO = CreateObject("Scripting.FileSystemObject")
Set objShell = CreateObject("WScript.Shell")
' Run from the project folder, so "artisan" resolves whatever directory the task starts in.
objShell.CurrentDirectory = objFSO.GetParentFolderName(WScript.ScriptFullName)
objShell.Run """D:\xampp\php\php.exe"" artisan schedule:run", 0, True
