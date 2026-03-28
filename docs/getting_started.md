# Getting Started

## Requirements

### HTTP Server

It should go without saying, but you will need access to an HTTP Server
that you have permission to install web apps on and to connect to 
a mySQL server running on the same server.  

*This app was developed and tested using exclusively Apache servers.  
Use on other servers such as nginx may require some tweaks to the
code.  You are on your own if you go this route. If you are successful,
in getting this working with a different server, I would gladly
consider accepting a pull request to merge that into the baseline.*

### PHP 

The HTTP server must support at least PHP 8.1

*PHP 7 reached end-of-life in 2022 and is no longer supported. This
app uses functionality that was introduced in v8.1.*

### MySQL Server

This app was developed and testing using MySQL 5.7.

You will need a login to the MySQL server that has permission to 
create tables in order to set up the tables used by this app.  It 
is, however, suggested that you may want to create an additional
login that does not have the permission to create or alter tables
for use by this app.  That account would only need access to 
perform select, insert, and deletes on table content.

## Initial Setup

### Download the app

If you have not already done so, you will need to get a copy of the
app onto your server.  I strongly recommend cloning the git repository
so that you can easily get updates as they become available.

```
> git clone git@github.com:mikemayer67/tlc-ttsurvey.git
```

*If you think you are going to want to make customizations the code for your use, I suggest that you create a personal fork of the repository on Github.  This is especially important if you think you might eventually wish to contribute to the project.*

Althernatively you could download the .zip file and decompress it, but upgrading will be more difficult down the road.

You will want to make sure the tlc-ttsurvey directory is in
a location that the HTTP server will pick it up.  You can
rename it to anything you like once it has been cloned (or downloaded).
The name itself isn't important to using the app, but it may be
required in forming the URL that will be used to access it.
For example, in my development environment, I installed it as a
subdirectory of the root directory sourced by Apache.  I access 
it using the URL 'localhost://tlc-ttsurvey/tt.php' or more simply as 'localhost://tlc-ttsurvey'.  In my deployed environment, I have 
a subdomain that points directly to the app's directory on the server.
I access it using 'https://tlc-ttsurvey.mydomain.org/tt.php' or more
simply as 'https://tlc-ttsurvey.mydomain.org'.  If you choose to
rename the directory, you might end up using something like:
'https://yourdomain.com/annual-survey/tt.php'.

### Configure your database

I recommend if all possible to create a schema specifically for use
for this app.  It contains a number of tables and views.  If you use
the same schema for this and other apps, it could quicky become 
confusing as to what table/view goes with which app.  This is 
somewhat mitigated by the fact that all of the tables and views used
by this app are prefixed with `tlc_tt_`.  But it will be cleaner if
you create a dedicated schmea.  If you choose to (or cannot) create
a new schema, make sure that you have no existing tables or views 
that start with `tlc_tt_`.  You wouldn't want installing this app
to clobber those.

When creating the schema, I suggest using utf8mb4 charset with the
`utf8mb4-unicode-ci` collation.

Once you have a schema identified, you will need to create the
tables and views used by this app.  This is done by running the
SQL script `configure_schema.sql` located in the sql directory.
This can be done from the command line using the mysql command or
by loading the script into a tool such as myphpadmin or
MySQLWorkbench and running it from there.  The following example
will need to be modified to contain your authentication method
and relative path to the configure script.  (*If that doesn't make
sense to you, you may be in over your head here.  I suggest you
brush up on how to do this before proceeding.*) In this example,
I am using the schema `tlc_tt`, am running from the sql directory,
and had previously configured my mysql settings to provide
local authentication.

```
> mysql --login-path=local tlc_tt --verbose < configure_schema.sql
```

This should create 19 tables and 18 view (all starting with `tlc_tt_`).

*Note that future versions may require new tables/views or 
modification to the existing ones.  In that case, a migration script
will be provided that preserves all of your data.  You should not
need to ever run the `configure_schema.sql` script again unless you
really want to start over from scratch.*

### Create the configuration file

The app requires a file named tlc-ttsurvey.ini to exist in its
root directory.  To prevent accidental spillage of protected
data, this file is **not** included with the distribution. You 
will need to create it from scratch.  The easiest way to do
this is to copy the provided tlc-ttsurvey.ini-dist file and
customize its content.

```
> cp tlc-ttsurvey.ini-dist tlc-ttsurvey.init
```

**Do not check tlc-ttsurvey.ini into your local repo.** This
also means you should not remove it from .gitignore.  This file
will contain passwords and PI that you probably do not want
to appear on a publivaly visible Github repository.

Once you have copied the config file, you will want to open it
and replace all instances of `[fill this in]` with information
specific to your hosting environment.  This includes things
such as:
- survey admin username and password
- path to the app's log file
- mysql connection information (host, schema, username, password)

There are hopefully plenty of instructions in the config file
template to guide you in configuring these values.

### Configure the app settings

**CONGRATULATIONS**  At this point, the app is fully installed
and operational. The remaining setup will be done from within 
the app itself.

Once you have registered as a survey user and been granted
admin permissions as a user, you will be provided a link to
the admin dashboard from within the navigation bar.  But for now,
you will need to log in using the admin username/password
you set up in the config file and you will need to explicitly
request the admin dashboard in the URL.  This will always
end with 'tt.php?admin'.  Going back to my examples above, this
would like 'localhost://tlc-ttsurvey/tt.php?admin' in my development
environment or 'https://tlc-ttsurvey.mydomain.org/tt.php?admin' on
my deployment server.  To access the admin dashboard the tt.php 
component of the URL is not optional.







