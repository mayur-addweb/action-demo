#!/usr/bin/env bash

# This script will download the database from a remote site site and importing into a local dev site.
#
# Requirements:
# - Vagrant must be setup and "up"
# - The drupal DB has already been configured within the local development site

## Variables for commands.
PROJECT_NAME='VSCPA D8'

REMOTE_SERVER_TYPE='STAGING' # staging, production, etc
REMOTE_SERVER_IP='10.50.20.12'
REMOTE_SERVER_USER='utadmin'
REMOTE_SERVER_PORT=52222
REMOTE_PROJECT_DIR='/srv/vscpa.utstaging.com/current'
REMOTE_BACKUP_DIR='/tmp'

PUBLIC_FILES_DIR="web/sites/default/files" #this should be relative to the project root

DB_BACKUP_FILENAME='vscpa.sql.gz'

VAGRANT_PROJECT_DIR='/var/www/vscpa'



green='\e[0;32m' # '\e[1;32m' is too bright for white bg.
endColor='\e[0m'

## Determine the project root directory
# Get the directory of the script
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"

if [ ! -f $PROJECT_DIR/composer.lock ]; then
    echo "Unable to determine project root"
    exit;
fi

cd $PROJECT_DIR


set -e # We want to fail at each command, to stop execution

## Confirm intention.
echo
echo -e "${green}This will sync the DB and files from the ${PROJECT_NAME} ${REMOTE_SERVER_TYPE} server.\n${endColor}"
echo
echo -e "${green}You must meet the following requirements:\n${endColor}"
echo -e "${green}\t- This should only be run on the your local computer.${endColor}"
echo -e "${green}\t- Vagrant must be provisioned and up.${endColor}"
echo -e "${green}\t- You are not logged into vagrant.${endColor}"
echo -e "${green}\t- The Drupal DBs have already been created and configured in the Drupal settings files.\n${endColor}"
read -p " Are you sure you want to continue? [y|n] " -n 1 -r
echo    # (optional) move to a new line
if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    exit 1
fi


echo -e "${green} Backing up the ${PROJECT_NAME} ${REMOTE_SERVER_TYPE} database to ${REMOTE_BACKUP_DIR} ...\n${endColor}"
ssh $REMOTE_SERVER_USER@$REMOTE_SERVER_IP -p$REMOTE_SERVER_PORT "cd ${REMOTE_PROJECT_DIR}/web && ../vendor/bin/drush sql-dump --gzip > ${REMOTE_BACKUP_DIR}/${DB_BACKUP_FILENAME}"

echo -e "${green} Copying Database from the ${PROJECT_NAME} ${REMOTE_SERVER_TYPE} server ...\n${endColor}"
scp -P$REMOTE_SERVER_PORT $REMOTE_SERVER_USER@$REMOTE_SERVER_IP:$REMOTE_BACKUP_DIR/$DB_BACKUP_FILENAME $PROJECT_DIR/$DB_BACKUP_FILENAME

echo -e "${green} Importing the database...\n${endColor}"
vagrant ssh -c "cd ${VAGRANT_PROJECT_DIR}/web && gzip -dc < ../${DB_BACKUP_FILENAME} | ../vendor/bin/drush sqlc"
rm $PROJECT_DIR/$DB_BACKUP_FILENAME

echo -e "${green} Rsyncing files from the ${PROJECT_NAME} ${REMOTE_SERVER_TYPE} server ...\n${endColor}"
rsync -ravz -e "ssh -p ${REMOTE_SERVER_PORT}" --delete --omit-dir-times --exclude /php --exclude /js/* --exclude /styles/* $REMOTE_SERVER_USER@$REMOTE_SERVER_IP:$REMOTE_PROJECT_DIR/$PUBLIC_FILES_DIR/ $PROJECT_DIR/$PUBLIC_FILES_DIR/

echo -e "${green} Clearing Caches...\n${endColor}"
vagrant ssh -c "cd ${VAGRANT_PROJECT_DIR}/web && ../vendor/bin/drush cr"
