#!/bin/bash
echo 'Copying...'
cp -R src orig
echo 'Running Babel...'
babel orig -d src
echo 'Running Grunt...'
grunt amd --force
echo 'Cleaning up...'
rm -R src
mv orig src
echo 'Done.'
