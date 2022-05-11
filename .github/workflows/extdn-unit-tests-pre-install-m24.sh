#!/bin/bash
composer config minimum-stability dev
composer config prefer-stable false

composer require magento/composer:"1.8.0 as 1.6.0" --no-update
composer require yireo/magento2-replace-bundled:^4.0 --no-update
composer require yireo/magento2-replace-inventory:^4.0 --no-update
composer require yireo/magento2-replace-pagebuilder:^4.0 --no-update
