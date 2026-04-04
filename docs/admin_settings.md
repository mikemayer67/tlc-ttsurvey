# Survey App Settings

There are a number of suvey app settings that the primary and survey admins can set via the 
Settings tab on the [Admin Dashboard](admin_dashboard.md).

These apply to the function of the app regardless of the current active survey.

These settings are:

**Look and Feel**

- **app_name** is the name of the survey app as it will appear
  to participants. It will be displayed in the navigation
  bar when there is no active survey and will appear as the
  from line in any email sent out by the survey.  If left
  unset, this will default to `Time and Talent Survey`.
- **app_logo** allows you to select an image to be displayed
  in the navigation bar before the app/survey name.  This
  can be most (any?) standard image format. It is recommended,
  however, that it be roughly square in shape with a resolution
  of at least 128x128. If left unset, no image will be shown.
- **timezone** sets the timezone used to display any time in
  the survey, the dashboard, or emails.  Internally, all
  times are recorded in UTC so that hanging the timezone 
  does not impact how times are written to the database.
  If left unset, the timezone will default to that used
  by your HTTP server.

**Admin**

- **admin_name** is the real (or pseudo) name for the site 
  admin defined in the config .ini file.  This will only
  be used if no participant (user) has been assigned the
  role of `primary admin`.  If left unset, the site admin
  name will be shown as `the survey admin`.
- **admin_email** is the email address displayed when instructing
  participants to contact an admin due to a problem with the
  app.  This will only be used if no participant has been
  assigned the role of `primary admin`.  If left unset, 
  no email address will displayed.

**Logging**

- **log_file** is the full path to the location of the 
  app's log file.  This displays the value set in the
  config .ini file.  It cannot be changed in the dashboard.
- **log_level** sets the level of information to include in
  the logging (errors, warnings, info, dev). The suggested
  level is errors and warnings unless you are doing active
  app development.

**Password Reset**

- **pwreset_timeout** is the number of minutes that the token
  issued during a password reset request will be valid before
  it times out.  If left unset, this defaults to 15 minutes.
- **pwreset_length** is the number of characters that will be
  included in the generated password reset tokens. If left
  unset, this defaults to 10 characters.

*Note that password reset settings only apply for particpants
that have provided an email address and can thus request a 
password reset.*

**Email Reminders**

- **reminder_freq** is the minimum time (hours) between 
  any reminder email sent to any given participant. This
  is set to guard against inadvertant spamming if any
  admin initiates the sending of reminders to one or
  more set of participants.

**Email Server**

*I will not be able to help you determine your particular
settings.  You will need to work with your email service
provider to work out the values for their SMTP servers.
I am using gmail and there is a lot of good documentation
out there on setting this up.*

- **smtp_host** is the URL for the SMTP server
- **smtp_auth** SMTPS or STARTTLS
- **smtp_port** will probably be either 465 for SMTPS or 587 for STARTTLS
- **smtp_username** is a SMTP login credential
- **smtp_password** is a SMTP login credential
- **smtp_reply_email** is an optional SMTP used to set the reply-to 
  field in any outgoing email.
- **smtp_reply_name** is an optional SMTP used to set the reply-to 
  field in any outgoing email.
- **smtp_debug** is the only setting not associated with your SMTP
  server. It tells the survey app how to log any debug responses
  it receives from the SMTP server. If any are selected for logging,
  they will be logged and the `info` (aka `notices`) level. 

After setting email server settings, you can then send a test email 
to the primary (or site) admin to verify you have everything set correctly.