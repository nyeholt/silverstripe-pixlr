=== License ===
The main source - the java applet - is developed under the LGPL 2.1 - see lgpl-2.1.txt.
The provided sample application (php and javascript) are provided AS IS.
This software comes with no guarantees or warranties, use at your own risk.

=== What's it all about ===
Supa - The Screenshot UPload Applet is a java applet that allows users to
upload images from the local clipboard directly to a website. This cuts out
the very annoying step of saving the image to a file first.

The applet is primarily designed to be included in other projects (e.g. the
dokuwiki plugin elsewhere on this project's website). However, a simple sample
application is provided in this package.

=== Security considerations ===
Normal security settings of the browser prevent java applets to access any
local resource (i.e. clipboard). To bypass this restriction, the applet needs
to be signed and trusted by the user.
If the signature is not known to the client - the user must confirm access to 
local resources when the applet is started. A pre-signed applet with a custom
signature is provided in this package.

=== Setting up the demo ===
Go to your webserver's root directory
$ cd /path/to/your/webserver/htdocs/rootdir

Untar the downloaded archive. This will create the subdir "supa"
$ tar xvfj supa-VERSION.tar.bz2 

Change to the newly created subdirectory
$ cd supa

Make the datadir writable for the webserver
$ chown apache data
$ chmod u+w data

If you want to use the applet that is signed with the development certificate
(for testing purposes only!!!), rename the development .jar file:
$ mv Supa.jar.signed.development Supa.jar

If you want to sign the provided unsigned applet with your own certificate:
Jarsigner needs to be in your path. To obtain jarsigner, download the latest JDK from http://java.sun.com.
The final filename needs to be Supa.jar, so we rename the unsigned .jar and sign it. 
$ cp Supa.jar.unsigned Supa.jar
$ jarsigner -keystore /path/to/your/keystore-file -storepass password_for_your_keystore -keypass password_for_your_key Supa.jar YourKeyAliasName

On a side note:
To create a certificate to sign the jar file with (valid for 3600 days):
$ keytool -genkey -keystore resources/keystore -storepass supasupa -alias supa -keypass supasupa -validity 3600

A more detailed explanation of the signing process:
http://java.sun.com/j2se/1.5.0/docs/tooldocs/solaris/jarsigner.html

=== The demo application ===
- Browse to the demo application: http://yourhost/supa/demo.php
- copy some image to the system clipboard (e.g. by taking a screenshot,
  press PrintScreen on windows boxen)
- accept the certificate - should be the one you just created/signed with or
  the provided Supa Development certificate!
- click the "paste from clipboard" button
- look at the preview image :)
- hit the "upload" button
- watch a new window pop up containing the image you just uploaded... or
  not... check your popup settings in the browser and retry :)
- send loads of cash to the Supa development team ;)

=== Demo-App implementation details ===
The java applet reads the clipboard. Javascript retrieves the image content
from the applet as a Base64 encoded string. This string is then uploaded via a
generated HTTP-POST (Ajax, woohoo).  The returned URL is then opened in the browser.
