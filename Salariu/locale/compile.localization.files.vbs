sub includeFile (fSpec)
    dim fileSys, file, fileData
    set fileSys = createObject ("Scripting.FileSystemObject")
    set file = fileSys.openTextFile (fSpec)
    fileData = file.readAll ()
    file.close
    executeGlobal fileData
    set file = nothing
    set fileSys = nothing
end sub
includeFile "common.locale.vbs"

ShowSubfolders objFSO.GetFolder(WshShell.CurrentDirectory), "Compile", "", ""
MsgBox "I finished compiling localization files!"
