# Asker

Asker is an insecure (I can't stress this enough) PHP script to create web based dialogs with a user to execute commands. The main drive behind the script was to create a web based interface for a helpdesk to allow them to execute tasks that normally would have to be done by system administrators.

This is **BETA** grade software at best!

Asker tries to make it easy to design screens and is therby restricted to some basic types of dialogs that are supported:

- plain text display
- free form input fields
- number input fields
- password input fields
- dropdown select boxes
- checkboxes
- buttons
- file upload

Input values come either form the dialogs or from an executed command. The values are assigned to variables that can be used in the dialog.

The configs directory contains a test configuration which tries to demo some of the features.

## Installation

Asker has only been tested on Debian/Linux at this moment. Currently it has been tested with the following components:

- Apache webserver
- PHP 5
- Firefox webbrowser (recent release)

Asker will try and check if the connection to the webserver is encrypted and you have basic authentication running. If you don't it will complain. You can overrule this behaviour in the asker.php file. Just edit the file and look at the top of the file for:

`$OVERRULE_SSL=false;`

`$OVERRULE_AUTH=false;`

Change them to true if you want to overrule either or both checks.

Asker will also do some basic sanity checking of the permissions of the `config` directory, to make sure there aren't any too obvious security issue's.

After putting the files from the repository somewhere in the webserver document
root you should be able to start asker with the test configuration:

http://yourserver/installdir/asker.php?action=test

If you no longer require the test configuration, I would urge you to remove the `test.sh` shellscript that is used in the testcase.

# Concept

The basic thought behind asker is to create a sequence of stateless web based screens, which can transition to eachother. You can go from screen to screen, showing information and requesting input from the user. The communication between screens is done by variables. Input from the user, or output from commands is assigned to variables that can be used in either the screen output or running commands.

# Debugging

It can sometimes be tricky to find why something is not working like intended. To help out a bit in such a situation it is possible to enable debug mode. This is done by passing an additional argument to the URL named debug with a value larger then 0. For example:

http://yourserver/installdir/asker.php?action=test&debug=1

With the default ccs this will show small red d's next to each element. Hovering over the d will show information on how asker has interpreted the configuration of the specified item.

# Configuration

The configuration uses the standard ini file format (because it was easy to implement :) ). The idea is that for each specific set of dialogs there is a seperate configuration file. When starting asker you need to specify which configuration file it needs to process through the action parameter. The value of the action parameter is the name of the ini configuration file without the extension.

## Variables

Except for the global part of the configuration, variables can be used when defining screens. Variables are identified bij % marks around the capitalised names. So for example:

`%VAR%`

is a variable. Variables can be choosen to be any letter/number combination.

## Global configuration

Each configuration file has a global configuration part. which is called

`[start]`

The following configuration items are available in this section:

### name (mandatory)

Name is the name of the configuration. This name is used to display in the generated screens.

**Example**

Assign the name Test case to the configuration.

`name = "Test case"`

### begin (mandatory)

Begin refers to the defenition of the screen at which the configuration should start.

**Example**

Start at the screen named main.

`name = main`

### css (optional)

This refers to a file with a cascading style sheet to change the design of the pages.

**Example**

Use the file asker.css as the cascading style sheet.

`css = asker.css`

### log (optional)

Specifying a log facility makes asker log lines for every request. This will give some basic tracing capability for actions that are performed through asker.

**Logging type**

This can be either file or syslog.

When logging through a file, the second part of the arguments should be pointing to a filename. Keep in mind that the user the software is running as needs write capabilities for that file.

When using syslog, the second part of the argument can be the syslog facility level. These are:
- LOG_EMERG
- LOG_ALERT
- LOG_CRIT
- LOG_ERR
- LOG_WARNING
- LOG_NOTICE
- LOG_INFO
- LOG_DEBUG

**Examples**

Use the file /var/log/asker.log to log user actions.

 log = file,/var/log/asker.log

Log all actions through syslog with level critical.

 log = syslog,LOG_CRIT

## Screen configuration

There can be loads of screens in a single configuration file (maybe it is better to split them out over more configuration files, but it is possible).

A screen starts at the defenition of a new section heading (with the exception of the start section as discussed previously).

**Example**

Start a screen with with the name main.

`[main]`

Each screen can have the following configuration items

### title (mandatory)

This is a description of the specific screen and is used to display on the screen.

**Example**

Set the title of the scren to This is the first screen

`title = "This is the first screen"`

### run (optional)

Run defines a command that needs to be executed. There can only be one run per screen. When a screen has an run defined, the run will be executed before any of the information in the screen is displayed.

The command will always be executed in the background. When the command is running the webbrowser will be served with a bit of javascript which periodically will poll the server to see if the command has already completed. This works around webserver timeout problems with long running commands.

**Options**

*type* (optional)

Defaults to normal.

Normal will just show a time counter indicating how long the command has been running. When completed it will display the screen.

Follow will also show the time counter and show the output of the command while it is running. After the command has completed it will display the screen.

*var* (mandatory)

The name of the variable the output of the command should be assigned to.

*err* (optional)

This option can point to a screen name which should be shown when an error occurs when running the command.

**command**

The command to run. It is possible to use variables when defining the command.

**Examples**

Generate a process list, assing it to variable LIST and display the amount of time that has elapsed

`run = {var:%LIST%},ps -ef`

Create a directory listing and sleep 5 seconds with the output showing on screen and assigning it to variable DIR.

`run = {type:follow,var:%DIR%},ls -al;sleep 5`

Run the command the user has entered in a previous screen and is in the variable CMD. The output of the command is assigned to the variable OUTPUT.

`run = {var:%OUTPUT%},%CMD%`

Runt he command lkjsdf and go to screen error if an error occurs.

`run = {var:%OUTPUT%,err:error},lkjsdf`

### item[] (optional)

An item is something which is displayed in the browser. To display anything you will need at least one item[] in the configuration. You can have many of these items per screen. They will be processed in the order that they are defined.

The value of the item[] defines what it will do. There are different kinds of output that can be generated with items.  Each type of item can have arguments, which are seperated by a comma. What each argument does depends on the type of item.

Below follows a list of different types of items.

#### text

Text is a very basic item. It will display the text following it on the screen.

**Options**

*id* (optional)

Assign an id to the item that can be referenced from a cascading style sheet for formatting.

**Text to display**

This is the text to display. Variable substitution is applied to the text. It is also possible to use HTML code in the text.

**Example**

Show text This is a test

`item[] = text{},"This is a test"`

Show text Hi with the name of the user in italic from the variable USER read in the previous screen.

`item[] = text{},"Hi <i>%USER%</i>"`

#### input

Input will show a free form input field in which the user can fill out text. The text will be assigned to a variable which can be used in another screen.

**Options**

*var* (mandatory)

This is the name of the variable the input of the user will be assigned to.

*size* (optional)

Defaults to 30.

The size in number of characters for the input field.

*id* (optional)

Assign an id to the item that can be referenced from a cascading style sheet for formatting.

**Text to display**

This text will be prepended to the input box. This text can contain variables that will be expanded.

**Example**

Ask the user's name and assign it to variable USER.

`item[] = input{var:%USER%},"Enter your name:"`

#### password

Password will show a input field for a password. The password will be assigned to a variable which can be used in another screen.

**Options**

*var* (mandatory)

This is the name of the variable the input of the user will be assigned to.

*size* (optional)

Defaults to 10.

The size in number of characters for the input field.

*id* (optional)

Assign an id to the item that can be referenced from a cascading style sheet for formatting.

**Text to display**

This text will be prepended to the input box. This text can contain variables that will be expanded.

**Example**

Ask the user's password (max 12 character) and assign it to variable PASSWORD.

`item[] = password{var:%PASSWORD%,size:12},"Enter your password:"`

#### number

Number will show a free form input field in which the user can fill out a number. The number will be assigned to a variable which can be used in another screen.

**Options**

*var* (mandatory)

This is the name of the variable the input of the user will be assigned to.

*min* (optional)

The lowest number that can be inputted.

*max* (optional)

The highest number that can be inputted.

*req* (optional)

Defaults to false.

When set to true the field must be filled out, otherwise the user can't go to a next screen.

*id* (optional)

Assign an id to the item that can be referenced from a cascading style sheet for formatting.

**Text to display**

This text will be prepended to the input box. This text can contain variables that will be expanded.

**Example**

Ask the user's age (between 12 and 100) and assign it to variable AGE.

`item[] = number{var:%AGE%,min:12,max:100},"Enter your age:"`

#### edit

Edit will show a text area the user can edit the content of.

**Options**

*var* (mandatory)

This is the name of the variable the input of the user will be assigned to.

*width* (optional)

Defaults to 40.

The width in characters of the area.

*height* (optional)

Defaults to 20.

The height in characters of the area.

*req* (optional)

Defaults to false.

When set to true the field must be filled out, otherwise the user can't go to a next screen.

*id* (optional)

Assign an id to the item that can be referenced from a cascading style sheet for formatting.

**Text to display**

This text will be the contents of the area.  This text can contain variables that will be expanded.

**Example**

The variable %TEXT contains the output of a command the user can edit in a 80x25 area. The result will be assigned to %INPUT%.

Ask the user's age (between 12 and 100) and assign it to variable AGE.

`item[] = edit{var:%INPUT%,width:80,height:25},%OUTPUT%`

#### select

The select input will show a list of items which the user can make a selection from.

**Options**

*size* (optional)

Defaults to 1.

This is the number of items that will be shown at a time. When 1 is specified it will function as a drop down box.

*var* (mandatory)

This is the name of the variable the selection of the user will be assigned to.

*list* (mandatory)

As lists can be quite long, the list of items a variable (for example the output of a run). Each item for the list is seperated by a newline. If an item contains a tab character, the part before the tab is used to assign to the variable if the item is choosen. The part after the tab is being shown to the user. This can for example be useful when showing a list of users on a system. Before the tab the login account of the user is used and after the tab the full user name.

*id* (optional)

Assign an id to the item that can be referenced from a cascading style sheet for formatting.

*req* (optional)

Defaults to false.

When set to true the field must have a selection, otherwise the user can't go to a next screen.

**Text to display**

This text will be prepended to the select box. This text can contain variables that will be expanded.

**Example**

Show a selection box with all usernames previously obtained with an action (assigned to %LIST%) and assign it to the variable USER. Ten users will be displayed at once.

`item[] = select{size:10,var:%USER%,list:%LIST%},"Choose user"`

#### checkbox

A checkbox will create a checkbox with text the user can select (or not).

**Options**

*var* (mandatory)

This is the name of the variable the selection of the user will be assigned to.

*val* (mandatory)

If the user checks the checkbox, this value will be assigned to the variable. This value will not be shown to the user.

*id* (optional)

Assign an id to the item that can be referenced from a cascading style sheet for formatting.

**Text to display**

The text that is shown next to the checkbox.

**Example**

Give the user the option to run a command in debug mode.

`item[] = checkbox{var:%DEBUG%,val:-d},"Debug mode"`

#### button

A button is used to transition to a different screen.

**Options**

*scr* (mandatory)

This is the name of the screen that should be shown when the button is pressed.

*id* (optional)

Assign an id to the item that can be referenced from a cascading style sheet for formatting.

**Text to display**

The text that is shown on the button.

**Example**

Go to the runcommand screen when the user presses the button Run Command

`item[] = button{scr:runcommand},Run Command`

#### keep

keep is a special case. If a variable has been assigned a value in a previous screen, but it is not going to be used in the current screen but in a next one, you can preserve the value of the variable by using keep. If keep isn't used, the variable value will be lost in the next screen.

**Options**

*var* (mandatory)

This is the name of the variable which value has to be kept so it can be used in a next screen.

**Example**

Keep the value of variable ITEM.

`item[] = keep{var:%ITEM%}`

#### autosubmit

This will not wait for the user to press a button, but automatically transition to the next screen. This can be useful if more then one action needs to be ran. Remember to use a keep statement if you want to preserve the output of actions across screens.

**Options**

*scr* (mandatory)

This is the name of the screen that should be shown when the button is pressed.

**Example**

Go to the screen nextaction automatically.

`item[] = autosubmit{scr:nextaction}`

#### upload

Upload will give the option to upload files to the server. The name of the file will be assigned to a variable so it can be used for further processing.

**Options**

*dir* (mandatory)

This is the path of the directory the file should be put in after upload. Errors will be generated when a file with the same name already exists in the directory or it is otherwise not possible to put the file in the directory.

*name* (mandatory)

This is the name of the variable the final filename should be assigned to. The directory is included with the filename.

*id* (optional)

Assign an id to the item that can be referenced from a cascading style sheet for formatting.

**Text to display**

The text that will be displayed in front of the upload selection.

*req* (optional)

Defaults to false.

When set to true a file must have been selected, otherwise the user can't go to a next screen.

**Example**

Upload a file to /documents and put the full filename and path into the variable %FILE%.

`item[] = upload{dir:/documents:name=%FILE%},Choose file to upload`

# Screen design

The design of the screens is something that is not done through asker. There is a configuration setting in each configuration file which you can use to reference a cascading stylesheet. There is an example in the repository which does at least some formatting.

Mostly standard HTML is used, but there are a few div's in the output generated by asker that can assist in formatting the output. You can ofcourse also hide specific output through the css if you don't want it shown.

## #heading

This id is used by the information heading at the start of the page. In the example css it is used to pin this information to the top of the page and don't make it scroll.

## #progress

This is can be used to put aditional content on the screen when an command is running. You can for example show an animation or do other stuff.

## #time

This id is used when an command is running to indicate the div that is updated with the number of seconds the command has been running.


## #follow

This id is used to reference the part of the screen where the output of a running command is displayed. It can be useful to make this an auto scrolling part of the screen. The javascript code that updates this id with new output as it becomes available also scrolls the output down if it is set to scroll in the css.
