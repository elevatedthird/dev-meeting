#!/bin/bash
#
# Example shell script to run post-provisioning.
#
# This script configures the default Apache Solr search core to use one of the
# Drupal Solr module's configurations. This shell script presumes you have
# `solr` in the `installed_extras`, and is currently set up for the D8 versions
# of Apache Solr Search or Search API Solr.
PROJECT_NAME="dev-meeting"
SOLR_CORE_NAME="collection1"
SOLR_SETUP_COMPLETE_FILE="/etc/drupal_vm_solr_config_complete_$SOLR_CORE_NAME"

# Search API Solr module.
SOLR_MODULE_NAME="search_api_solr"
SOLR_VERSION="7.x"
SOLR_CORE_PATH="/var/solr/data/$SOLR_CORE_NAME"

# Check to see if we've already performed this setup.
if [ ! -e "$SOLR_SETUP_COMPLETE_FILE" ]; then
  # Copy new Solr collection core with the Solr configuration provided by module.
  sudo cp /var/www/$PROJECT_NAME/settings/solr/* $SOLR_CORE_PATH/conf/
  # Adjust the autoCommit time so index changes are committed in 1s.
  sudo sed -i 's/\(<maxTime>\)\([^<]*\)\(<[^>]*\)/\11000\3/g' $SOLR_CORE_PATH/conf/solrconfig.xml
  # Restart Apache Solr.
  sudo service solr restart
  # Create a file to indicate this script has already run.
  sudo touch $SOLR_SETUP_COMPLETE_FILE
else
  exit 0
fi
