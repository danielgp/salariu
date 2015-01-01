
Set WshShell   = WScript.CreateObject("WScript.Shell")

MsgBox "I will assess the PHP project with PHP-Metrics!"

strCurDir      = WshShell.CurrentDirectory
strBaseDir     = Replace(strCurDir, "tests", "")

WshShell.Run "D:\www\App\PHP\PHP56\php.exe -d date.timezone=""Europe/Bucharest"" c:\Users\Transformer-\Documents\Git\GitHub.3rdPartyWebApps\PhpMetrics\build\phpmetrics.phar --report-xml=" & strCurDir & "\php-metrics\checks.xml " & strBaseDir, 0, True
WshShell.Run "D:\www\App\PHP\PHP56\php.exe -d date.timezone=""Europe/Bucharest"" c:\Users\Transformer-\Documents\Git\GitHub.3rdPartyWebApps\PhpMetrics\build\phpmetrics.phar --violations-xml=" & strCurDir & "\php-metrics\violations.xml " & strBaseDir, 0, True

MsgBox "I finished generating XML files with PHP-Metrics results!"
