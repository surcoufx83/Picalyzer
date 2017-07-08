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
Once it's done, you get the result of the analyzation process.

# Result
On the results page you will have 4 pictures for each source picture.
* The left one is the same as the source picture but cropped to the borders of the red tracking circle.
* The second one is the working copy of the first image and that cropped by white borders with the black circle and cross placed on top.
* The third picture shows you the activity zones where the fish swum. It's fragmented into the four directions (north-west, north-east, south-east and south-west) and ten circles of same size. Every zone get's filled by an activity color code (see below).
* The last image is like the third one but with only two zones for every direction. The inner zone represents the inner eight zones of the third picture, the outer zone the two other zones of the third picture.

![Pattern with almost no activity](https://raw.githubusercontent.com/surcoufx83/Picalyzer/master/Pictures/NoActivity.png)
![Pattern with low activity](https://raw.githubusercontent.com/surcoufx83/Picalyzer/master/Pictures/LowActivity.png)
![Pattern with medium activity](https://raw.githubusercontent.com/surcoufx83/Picalyzer/master/Pictures/MediumActivity.png)
![Pattern with high activity](https://raw.githubusercontent.com/surcoufx83/Picalyzer/master/Pictures/HighActivity.png)

# Calculations

## Detecting the real well size
