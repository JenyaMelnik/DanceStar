#!/usr/bin/env bash

docker compose exec php su -s /bin/bash -c "cd /var/www/html && php -d upload_tmp_dir=/var/www/html/storage/tmp -d sys_temp_dir=/var/www/html/storage/tmp ./vendor/bin/pint $*" sail
