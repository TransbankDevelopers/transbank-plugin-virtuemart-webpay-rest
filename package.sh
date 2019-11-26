#!/usr/bin/env bash

#Script for create the plugin artifact
echo "Travis tag: $TRAVIS_TAG"

if [ "$TRAVIS_TAG" = "" ]
then
   TRAVIS_TAG='1.0.0'
fi

SRC_DIR="src"
FILE1="transbank_webpay.xml"

sed -i.bkp "s/<version>2.0.4/<version>${TRAVIS_TAG}/g" "$SRC_DIR/$FILE1"

PLUGIN_FILE="plugin-transbank-webpay-virtuemart3-$TRAVIS_TAG.zip"

cp CHANGELOG.md $SRC_DIR
cp LICENSE $SRC_DIR
cd $SRC_DIR
zip -FSr ../$PLUGIN_FILE . -x *.git/\* .DS_Store* *.zip "$FILE1.bkp"
cd ..

rm "$SRC_DIR/CHANGELOG.md"
rm "$SRC_DIR/LICENSE"
cp "$SRC_DIR/$FILE1.bkp" "$SRC_DIR/$FILE1"
rm "$SRC_DIR/$FILE1.bkp"

echo "Plugin version: $TRAVIS_TAG"
echo "Plugin file: $PLUGIN_FILE"
