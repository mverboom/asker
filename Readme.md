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

# Configuration

The configuration uses the standard ini file format (because it was easy to implement :) ). The idea is that for each specific set of dialogs there is a seperate configuration file. When starting asker you need to specify which configuration file it needs to process through the action parameter. The value of the action parameter is the name of the ini configuration file without the extension.

## Variables

Except for the global part of the configuration, variables can be used when defining screens. Variables are identified bij % marks around the capitalised names. So for example:

`%VAR%`

is a variable. Variables can be choosen to be any letter/number combination, except for the variable `%OUTPUT%`. This variable is automatically defined when a screen is defined with a command to run. The variable `%OUTPUT%` contains the output of the command.

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

### action (optional)

An action defines a command that needs to be executed. There can only be one action per screen. When a screen has an action defined, the action will be run before any of the information in the screen is displayed.

The command will always run in the background. When running an action the webbrowser will be served with a bit of javascript which periodically will poll the server to see if the task has already completed. This works around webserver timeout problems with long running tasks.

**Arguments**

*(normal|follow)*

Normal will just show a time counter indicating how long the command has been running. When completed it will display the screen. Follow will also show the time counter and show the output of the command while it is running. After the command has completed it will display the screen.

*command*

The command to run. It is possible to use variables when defining the command.

**Examples**

Generate a process list and display the amount of time that has elapsed

`action = normal,ps -ef`

Create a directory listing and sleep 5 seconds with the output showing on screen

`action = follow,ls -al;sleep 5`

Run the command the user has entered in a previous screen and is in the variable CMD

`action = normal,%CMD%

### item[] (optional)

An item is something which is displayed in the browser. To display anything you will need at least one item[] in the configuration. You can have many of these items per screen. They will be processed in the order that they are defined.

The value of the item[] defines what it will do. There are different kinds of output that can be generated with items.  Each type of item can have arguments, which are seperated by a comma. What each argument does depends on the type of item.

Below follows a list of different types of items.

#### text

Text is a very basic item. It will display the text following it on the screen.

**Arguments**

*Text to display*

This is the text to display. Variable substitution is applied to the text.

**Example**

Show text This is a test

`item[] = text,"This is a test"`

Show text Hi with the name of the user in variable USER from previous screen.

`item[] = text,"Hi %USER%"`

#### input

Input will show a free form input field in which the user can fill out text. The text will be assigned to a variable which can be used in another screen.

**Arguments**

*Variable name*

This is the name of the variable the input of the user will be assigned to.

*Text to display*

This text will be prepended to the input box. This text can contain variables that will be expanded.

**Example**

Ask the user's name and assign it to variable USER.

`item[] = input,USER,"Enter your name"`

#### showaction

Show action will show the output of the command that was run, so basically the contents of the variable OUTPUT.

**Arguments**

*pre* (optional)

When the argument pre is given the output will be shown pre-formatted.

**Example**

Show the output of the action pre-formatted.

`item[] = showaction,pre`

#### select

The select input will show a list of items which the user can make a selection from.

**Arguments**

*Variable*

This is the name of the variable the selection of the user will be assigned to.

*List of items*

As lists can be quite long, the list of items is usually a variable (for example the output of a command). Each item for the list is seperated by a newline. If an item contains a tab character, the part before the tab is used to assign to the variable if the item is choosen. The part after the tab is being shown to the user. This can for example be useful when showing a list of users on a system. Before the tab the login account of the user is used and after the tab the full user name.

*Text to display*

This text will be prepended to the select box. This text can contain variables that will be expanded.


**Example**

Show a selection box with all usernames previously obtained with an action and assign it to the variable USER.

`item[] = select,%USER%,%OUTPUT%,"Choose user"`

#### checkbox

A checkbox will create a checkbox with text the user can select (or not).

**Arguments**

*Variable*

This is the name of the variable the selection of the user will be assigned to.

*Chosen option*

If the user checks the checkbox, this value will be assigned to the variable. This value will not be shown to the user.

*Text to display*

The text that is shown next to the checkbox.

**Example**

Give the user the option to run a command in debug mode.

`item[] = checkbox,%DEBUG%,-d,"Debug mode"`

#### button

A button is used to transition to a different screen.

**Arguments**

*Name of screen*

This is the name of the screen that should be shown when the button is pressed.

*Text to display*

The text that is shown on the button.

**Example**

Go to the runcommand screen when the user presses the button Run Command

`item[] = button,runcommand,Run Command`

# Screen design

The design of the screens is something that is not done through asker. There is a configuration setting in each configuration file which you can use to reference a cascading stylesheet. There is an example in the repository which does at least some formatting.

Mostly standard HTML is used, but there are a few div's in the output generated by asker that can assist in formatting the output. You can ofcourse also hide specific output through the css if you don't want it shown.

## #heading

This id is used by the information heading at the start of the page. In the example css it is used to pin this information to the top of the page and don't make it scroll.

## #progress

This is can be used to put aditional content on the screen when an action is running. You can for example show an animation or do other stuff.

## #time

This id is used when an action is running to indicate the div that is updated with the number of seconds the action has been running.


## #follow

This id is used to reference the part of the screen where the output of a running action is displayed. It can be useful to make this an auto scrolling part of the screen. The javascript code that updates this id with new output as it becomes available also scrolls the output down if it is set to scroll in the css.
