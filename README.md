# tlc-ttsurvey

This repository contains web app used for the annual Time & Talent survey used by
Trinity Lutheran Church of North Potomac, MD (https://trinityelca.org).  

While it is was designed for and around a particular congregation, it is made 
available to anyone who feels it may be of use to them (with or without modification).
To that end, while the prefix ```tlc``` is used extensively through the code and
database, every effort was made to allow the front end to be customized so as to
not include "Trinity Lutheran" or any of its members or staff in the survey.

I welcome pull issues and pull requests from anyone who wishes to contribute to
this projects.  I will accept pull requests that add features or increase generality
in it usage.  I will not accept pull requests that add specificity to any partiular
user of the app (including changes specific to Trinity).

## Uage Overview

(still to come)


## Setup Notes:

### database
- This app requires access to a MySQL database
- You *could* use an existing schema, but it's not recommended to avoid table name collisions
- Use the ```create_tables.sql`` script in the sql directory to create tables required by this app

### tlc-ttsurvey.init-dist
- The repo does **not** include this file, but it is necessary for the app to work.
- The easiest way to create it is to make a copy of tlc-ttsurvey.ini.dist.
- Look for all instances of ```[fill this in]``` and replace with the appropriate value for your hosting environment.

### custom email content
- You may provide a custom message to be included in the various emails the app sends to particpants
- This custom content will be inserted prior to the auto-generated message
- Read the instructions in sendmail/0.readme.md for info on how to create custom email content
