# Asker

Asker is an insecure (I can't stress this enough) PHP script to create web based dialogs with a user to execute actions. The main drive behind the script was to create a web based interface for a helpdesk to allow them to execute tasks that normally would have to be done by system administrators.

This is **BETA** grade software at best!

Asker tries to make it easy to design screens and is therby restricted to some basic types of dialogs that are supported:

- plain text display
- free form input fields
- dropdown select boxes
- checkboxes
- buttons

Input values come either form the dialogs or from an executed command. The values are assigned to variables that can be used in the dialog.

The configs directory contains a test configuration which tries to demo some of the features.

## Installation

Asker has only been tested on Debian/Linux at this moment. Currently it has been tested with the following components:

- Apache webserver
- PHP 5
- Firefox webbrowser (recent release)

Asker will try and check if the connection to the webserver is encrypted and you have basic authentication running. If you don't it will complain. You can overrule this behaviour in the asker.php file. Just edit the file and look at the top of the file for:

```
$OVERRULE_SSL=false;
$OVERRULE_AUTH=false;
```

Change them to true if you want to overrule either or both checks.

Asker will also do some basic sanity checking of the permissions of the `config` directory, to make sure there aren't any too obvious security issue's.

After putting the files from the repository somewhere in the webserver document
root you should be able to start asker with the test configuration:

http://yourserver/installdir/asker.php?action=test

If you no longer require the test configuration, I would urge you to remove the `test.sh` shellscript that is used in the testcase.

# Configuration

TBD
