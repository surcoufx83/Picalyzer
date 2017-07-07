# Picalyzer
This PHP scripts are used to parse the output images of the ZebraLab tool to identify moving zones of the animals.

# Requirements and prerequisites
To use  this tool one needs the following software:
* A webserver with the following modules enabled: PHP, Imagick-Library
* You should set the script execution timeout within your php.ini to a much higher value than the default 30 seconds. One image requires between 2 and 6 seconds to be processed. Batch execution with 100 images of 12 well plate normally takes up to 600 seconds, 100 images of 96 well plate up to 300 seconds.
