# Esewa Payment Module for Magento 2

### Requirement & Compatibility
- Requires magento version at least: `2.x`
- Tested and working upto `Magento 2.4.6`

### Installation
- Create the following folder structure inside "app/code/" folder and copy all the files
  "Retroitsoln/Esewa"
- After you have copied all the files the folder structure should be like this
  "app/code/Retroitsoln/Esewa/UPLOADED_FILES"
- Enable Esewa Module
    `php bin/magento module:enable --clear-static-content Retroitsoln_Esewa`
- Run Setup Upgrade
  `php bin/magento setup:upgrade`
- Run DI Compilation to generate classes
    `php bin/magento setup:di:compile`
- If you are on Production Environment, make sure you run the following command as well
  `php bin/magento setup:static-content:deploy`
- Flush the Cache
    `php bin/magento cache:flush`
- Finally Install and Run Cron
    `php bin/magento cron:install`