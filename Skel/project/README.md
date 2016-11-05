INSTALLATION
============

Requirements
------------

This software require PHP version 5.3 or up with the php command-line
program available to run istallation scripts.

You may also need Internet access to be able to download dependencies.

Download Composer
-----------------

Run this in your terminal to get the latest Composer version:

    curl -sS https://getcomposer.org/installer | php

Or if you don't have curl:

    php -r "readfile('https://getcomposer.org/installer');" | php

This installer script will simply check some php.ini settings, warn you
if they are set incorrectly, and then download the latest composer.phar
in the current directory

Run the installation
--------------------

Run this in your terminal to install dependencies and configure the
software.

    ./composer.phar install

Configuration
-------------

Site specific configuration is loaded from the file

    config/config.local.php

To change the configuration, run this in a
terminal:

    ./setup config -set key value
