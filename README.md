EXA2fa
======================================
EXA2fa Plugin adds a 2nd authentication step (a2fa) to your existing Auth (manual, email and ldap) 

A2FA Stands for Another Two-Factor Authentication
A2FA is a multi-factor authentication plugin that uses time-based tokens generated every 60 seconds.
The users also need to install the Google Authenticator app or FreeOTP app on their phone.

EXA2fa allows users to turn a2fa on and off in their profile page 


##Installation:

* install this plugin as a moodle block
* move the directories "exa2fa/auth/a2fa_*" to "yourmoodle/auth/a2fa_*"
* Now go to ***Site Administration > Plugins > Authentication > Manage authentication*** and enable the a2fa plugins (Name starts with A2FA)
* Change the Alternate login URL in the moodle settings (***Site Administration > Plugins > Authentication > Manage authentication***) to
yourmoodlesite.com/blocks/exa2fa/login/,
else the a2fa users can't login.


##User Configuration:

* Note: Users can activate a2fa by themselfs, the administrator can not do this step for the users
* To use a2fa each user has to first install the authenticator App on their phone (Google Authenticator or FreeOTP or similar).
* Then login to moodle and go to the profile page
* Look for "2FA Settings", click on "Enable 2FA" and follow the instructions on the page
* After you have activated 2FA your profile Page should read "2FA is active"
* 2FA is now activated. The next time you login you also need to provide the correct code from your Phone Auth App