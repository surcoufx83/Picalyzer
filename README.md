# Picalyzer
This PHP scripts are used to parse the output images of the ZebraLab tool to identify moving zones of the animals.

# Requirements and prerequisites
To use  this tool one needs the following software:
* A webserver with the following modules enabled: PHP, Imagick-Library
* You should set the script execution timeout within your php.ini to a much higher value than the default 30 seconds. One image requires between 2 and 6 seconds to be processed. Batch execution with 100 images of 12 well plate normally takes up to 600 seconds, 100 images of 96 well plate up to 300 seconds.
* Copy all php files and the three folders *Data*, *Work* and *__Olds* to a directory within your webservers documents folder (like */var/www/html/Picalyzer*).
* The webserver must have read and write access to those three folders.

Make sure that the following prerequisites have been fulfilled:
* Only 12-, 24- and 96- well plates are supported. 6 and 48 should also work but could not be tested right now.

# Usage
Inside the *Data* folder, create a subfolder for running this test. Put all the ZebraLab tracking images into this folder. Now navigate to the Picalyzer website on your webserver (like *http://localhost/Picalyzer/*). If everything is fine you should see a simple list with the names of all of the folders inside the *Data* directory. After you click on one of the links to the subfolders, the script starts running. Don't interrupt it, as it could take a lot of time, depending on the number of images inside the directory.
