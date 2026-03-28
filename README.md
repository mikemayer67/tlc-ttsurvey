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

## Survey Structure
## Survey Participation 

The app distinguishes between two groups of users

### Participants
- Users register themselves via the login page
  - must provide a unique userid
  - must provide a password
  - must provide the name that will be displayed in the survey and summary
  - may provide an email address (recommended for password recovery and notifications)
  - may elect to add a quick-connect button to avoid having to enter userid/password to log in
- Can participate in the survey
- Can manage their response submission status
  - store in draft state
  - submit upon completion
  - withdraw submission back to draft state
- May be able to view a summary of all submitted responses (depending on summary visibility rules)

## Survey Structure

### Sections

Each survey contains one or more sections.  Sections contain one or more questions plus informational text.
The intended purpose for sections is to provide a logical grouping of related survey questions.

- Sections may contain a block of introductory text which will be displayed in the survey before any of the
other content.  This intro text may contain [markdown](https://markdownguide.offshoot.io/basic-syntax/) 
to help format how it will appear in the survey.

- Sections may be marked as collapsible.  
  - This allows for a more compact display of the survey.  
  - Non-collapsible sections are always visible.  
  - Only one collapsible section will be visible in the survey at any time.
  - The section name is only visible for collapsible sections

- Sections may include a feedback block which will add a freetext response field at the end of that section.

### Info Text

Informational text can be dispersed at any point within a section. Like section introductions, this may
contain [markdown](https://markdownguide.offshoot.io/basic-syntax/) to control how its text will appear 
in the survey.

### Questions

There are four types of questions which can be included in the survey:

- Simple Checkbox: 
  - "yes/no" responses
  - participant either selects it or doesn't
  - a freetext box may be provided which allows the particpant to qualify their response

- Single Select: 
  - multiple choices are provided
  - an optional "other" field may be provided with a customizable label
  - participant may select a single response or leave the question blank
  - a freetext box may be provided which allows the particpant to qualify their response

- Multi Select:
  - multiple choices are provided
  - an optional "other" field may be provided with a customizable label
  - participant may select as many response as they feel apply
  - a freetext box may be provided which allows the particpant to qualify their response

- Freetext:
  - a freetext box is provided to allow the user to respond in their own words

Questions may be visibly grouped. 
Grouped questions will have their responses aligned to emphasize the questions are related to one another.

## Admin Dashboard

The admin dashboard provides the tools necessary for the admins to:

- customize the survey app settings
  - look and feel
  - logging level
  - password reset rules
  - email reminder rules
  - smtp server configuration

- assign roles
  - primary admin (only 1)
  - survey admins
  - content editors
  - technical contacts
  - summary access rules
    - all participants or only admins and specified participants
    - can participants see the summary before submitting their own responses

- manage survey content
  - create new surveys (survey and primary admins only)
  - change survey status (survey and primary admins only)
    - draft -> live (only one live survey at a time)
    - live -> closed
    - closed -> live
    - live -> draft (requires confirmation as this can impact existing user responses)
  - edit content of draft surveys (content editors + survey/primary admins)
    - *see Editing Survey Content below*

- view list of participants
  - mapping from userid to full (displayed) name
  - email address (if provided)
  - ability to send a password reset email
  - status of the user's survey response (draft/submitted)
  - does **not** allow for adding/removing users

- view the survey app's log
  - without logging into the server directly

