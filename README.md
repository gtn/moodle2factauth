A2FA or Etouffee as I like to call it!
======================================
A2FA is multi-factor authentication plugin that uses time-based tokens generated every 60 seconds in Google Authenticator app.
A2FA Stands for Another Two-Factor Authentication

##Installation:

* install this plugin as a moodle block

move the directories "exa2fa/auth/a2fa_*" to "yourmoodle/auth/a2fa_*"

Now go to ***Site Administration > Plugins > Authentication > Manage authentication*** and enable the a2fa plugins

##How to login:
you have to change the altternative login url in the moodle settings to
yourmoodlesite.com/blocks/exa2fa/login/
else the a2fa users can't login.



TODO: add copyright and sources