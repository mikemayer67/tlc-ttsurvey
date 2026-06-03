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

## Core Features

- Customizable survey branding (name/logo)
- Responsive design (works on desktop, laptop, or mobile device)
- Archive of prior surveys and responses
  - summary can be viewed online
  - new surveys can be cloned from old surveys
  - cloned surveys autopopulate content
  - cloned survesy autopopulate draft user responses
- Admin Dashboard provides access to:
  - survey app settings
  - user roles
  - review of current/past survey content
  - creation of draft survey content
  - summary of user response status
- Participants manage their own account
  - self registration
  - can review/revise/unsubmit their responses
  - may additionally be granted an admin role
  - may additionally be granted access to view response summary
- Multiple question types:
  - Simple checkbox (yes/no type questions)
  - Single select options (with optional write-in)
  - Multiple selection options (with optional write-in)
  - Freetext responses (user can answer in their own words)
  - *An optional qualifier field may be added to most question types*
- Survey content can be grouped by content sections
  - may be made collapsible to conserve space on the page
  - particularly useful when using the app on a mobile device
- Repsonse summaries
  - viewable online by anyone with role that allows access to the summary
  - can be downloaded as a CSV or PDF file
- Printable survey
  - admins can download a printable PDF for use by folks without online access

## Requirements

- HTTP server (e.g. Apache)
- PHP 8.1 or newer
- MySQL 5.7 or newer
  - MySQLi extension
  - MySQL Native Driver (mysqlnd, for mysqli_stmt::get_result)

## Setup

The full instructions for setting up the survey app can be found
in the [Getting Started documentation](docs/getting_started.md).

At a high level, this consists of
- creating the necessary database tables and views
- customizing the app's .ini file to your particular setup
- configuring the app's settings via the Admin Dashboard

## Additional Resources

- [Survey Content](docs/survey_content.md)
- [Survey Participation](docs/participants.md)
- [Admin Roles](docs/admin_roles.md)
- [Admin Dashboard](docs/admin_dashboard.md)
- [Assigning Admin Roles](docs/assigning_roles.md)
- [Managing Survey Participants](docs/managing_participants.md)
- [Managing Survey Content](docs/managing_survey_content.md)
