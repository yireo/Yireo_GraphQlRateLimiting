#!/bin/bash
composer config minimum-stability dev
composer config prefer-stable false

composer require yireo/magento2-integration-test-helper --no-update
composer require yireo/magento2-replace-bundled:^3.0 --no-update
composer require yireo/magento2-replace-inventory:^3.0 --no-update
