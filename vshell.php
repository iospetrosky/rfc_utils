<?php
require_once ( 'inc/WShell.php' ) ;

$wshell		=  new  WShell ( ) ;

// Launch NOTEPAD and let time for it for startup
//$wshell -> Exec ( "NOTEPAD.EXE" ) ;
echo "5 seconds to activate the target app\n";
sleep ( 5 ) ;
echo "2 more :-) \n";
sleep ( 2 ) ;


// NOTEPAD is launched : write the string "Hello world" in the document
$wshell -> SendKeys ( "gino_gigio{F5}") ;
sleep(10);
$wshell -> SendKeys ( "description of the role{TAB 4}R:XX146_PM_PMIS{enter}") ;
sleep(4);
$wshell -> SendKeys ( "{enter}") ;
sleep(4);
$wshell -> SendKeys ( "{tab}{right 2}{enter}") ;
sleep(4);
$wshell -> SendKeys ( "{tab 2}P00001{enter}description of P0{enter}^S") ;
sleep(4);
$wshell -> SendKeys ( "{F3}") ;

// We will save this new file :
// Type Alt+F, then DOWN key 3 times, press ENTER and type "example.txt" as the output filename.
// After that, you just need to click on the "Save" button to save the file.
// Note that we have to specify some delay between keystrokes (100ms in this example), because
// a few operations might need a delay to operate (for example, opening the "Save as" dialog box.
// Without this delay, a few characters may be missed from the filename "example.txt"
//$wshell -> SendKeys ( "%(F){DOWN 3}{ENTER}example.txt", 100 ) ;

