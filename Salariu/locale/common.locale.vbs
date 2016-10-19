Set objFSO = WScript.CreateObject("Scripting.FileSystemObject")
Set WshShell   = WScript.CreateObject("WScript.Shell")

Sub ShowSubFolders(Folder, Action, TargetFolder, RootFolder)
    For Each Subfolder in Folder.SubFolders
        Set objFolder = objFSO.GetFolder(Subfolder.Path)
        Set colFiles = objFolder.Files
        For Each objFile in colFiles
            If objFSO.GetExtensionName(objFile.Name) = "po" Then
                Select Case Action
                    Case "Compile"
                        WshShell.Run "D:\www\AppForDeveloper\GetText\msgfmt.exe " & objFile.Path & _ 
                            " --output-file=" & objFolder.Path & "\" & objFSO.GetBaseName(objFile.Name) & ".mo " & _
                            "--statistics --check --verbose", 0, True
                    Case "CopyToServerForDaniel"
                        objFSO.CopyFile objFolder.Path & "\" & objFSO.GetBaseName(objFile.Name) & _
                            ".mo", TargetFolder & Replace(objFolder.Path, RootFolder & "\", "") & "\", True
                    Case "Echo"
                        Wscript.Echo objFolder.Path & "\" & objFSO.GetBaseName(objFile.Name) & ".mo -----> " & _
                            " " & TargetFolder & Replace(objFolder.Path, RootFolder & "\", "") & "\"
                End Select
            End If
        Next
        ShowSubFolders Subfolder, Action, TargetFolder, RootFolder
    Next
End Sub
