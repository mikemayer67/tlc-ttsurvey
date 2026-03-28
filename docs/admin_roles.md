# Admin Roles

## Site Admin
There is one site admin whose userid and password is set in the app configuration
file (tlc-ttsurvey.ini).  Additionally a "real" name and email may be provided for
the site admin via the Settings tab in the [Admin Dashboard)(admin_dashboard.md).

The site admin has absolute authority with full access to all features and settings
availble in the Admin Dashboard.

## Primary Admin
In addition, to the site admin, there may be one primary admin who has exactly the same
authority as the site admin.

The only difference is that the primary admin is a role assigned to one of the 
[survey participants](participants.md) and is not configured through the app
configuration .ini file.

## Survey Admins
One or more [survey participant](participants.md) may be assigned the role of 
survey admin.

Survey admins have authority to:
- assign admin roles
- manage participants (send reminder emails or password reset instructions)
- view the survey app's log file
- create, monitor, or change survey status
- modify content of surveys that are in draft state
- view content of surveys that are in active or closed state

They do not have authority to change the survey app's settings.

## Content Editors
One or more [survey participant](participants.md) may be assigned the role of 
content editor.

Content eidtors have authority to:
- modify content of surveys that are in draft state
- view content of surveys that are in active or closed state

They do not have any additional admin authority.

## Technical Contacts
One or more [survey participant](participants.md) may be assigned the role of 
technical contact. 

Technical contacts have the same authority as survey admins with the added
"bonus" of being listed on any page or email that instructs a participant
to contact an admin to report an issue using the survey app.
