# tlc-ttsurvey

## Setup Notes:

### database
- This app requires access to a MySQL database
- You *could* use an existing schema, but it's not recommended to avoid table name collisions
- Use the ```create_tables.sql`` script in the sql directory to create tables required by this app

### tlc-ttsurvey.init-dist
- The repo does **not** include this file, but it is necessary for the app to work.
- The easiest way to create it is to make a copy of tlc-ttsurvey.ini.dist.
- Look for all instances of ```[fill this in]``` and replace with the appropriate value for your hosting environment.
