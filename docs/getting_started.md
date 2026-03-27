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
