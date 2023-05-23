#!/bin/sh
#
# Run this script to copy the source code into a test Wordpress app
# 
# Set the plugins and bundle variables to match your setup
#

# Target plugins folders
plugin=/Users/steve/Sites/test/wp-content/plugins/sjd_subscribe_plugin

# Remove the old plugin from the test application
rm -R ${plugin}/

# Make a new test plugins directory
mkdir ${plugin}

# Copy the plugin files
cp -r images ${plugin}/
cp -r includes ${plugin}/
cp -r templates ${plugin}/
cp -r index.php ${plugin}/
cp -r readme.md ${plugin}/
cp -r sjd_subscribe.php ${plugin}/
cp -r styles.css ${plugin}/
