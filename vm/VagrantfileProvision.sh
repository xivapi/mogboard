#!/usr/bin/env bash

#
# Setup
#
echo "Setting up"
cd /vagrant
USER=vagrant
sudo locale-gen en_GB.UTF-8
echo "- Updating"
sudo apt-get update -y -qq
sudo apt-get upgrade -y -qq
echo "- Installing: python-software-properties, software-properties-common, acl, htop, unzip, curl, git"
sudo apt-get install -y -qq python-software-properties software-properties-common acl htop unzip curl git dos2unix


#
# NGINX
#
echo "Installing: NGINX"
sudo add-apt-repository ppa:nginx/stable -y
sudo apt-get update
sudo apt-get install -y nginx &> /dev/nul
rm /etc/nginx/sites-available/default
echo "- Copying nginx configuration"
sudo cp /vagrant/vm/VagrantfileNginxCommon /etc/nginx/sites-available/common
sudo cp /vagrant/vm/VagrantfileNginxDefault /etc/nginx/sites-available/default
sudo cp /vagrant/vm/VagrantfileNginx.conf /etc/nginx/nginx.conf

#
# PHP
#
echo "Installing: PHP"
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update -y
sudo apt-get install -y -qq php7.2-fpm php-apcu php7.2-dev php7.2-cli php7.2-tidy php7.2-json php7.2-fpm php7.2-intl php7.2-mysql php7.2-sqlite php7.2-curl php7.2-gd php7.2-mbstring php7.2-dom php7.2-xml php7.2-zip php7.2-tidy php7.2-bcmath
echo "- Updating PHP configuration"
sudo sed -i 's|display_errors = Off|display_errors = On|' /etc/php/7.2/fpm/php.ini
sudo sed -i 's|memory_limit = 128M|memory_limit = 4G|' /etc/php/7.2/fpm/php.ini
sudo sed -i "s|www-data|$USER|" /etc/php/7.2/fpm/pool.d/www.conf

#
# MySQL
#

echo "Installing: MySQL"
echo "mysql-server mysql-server/root_password password mogboard" | debconf-set-selections
echo "mysql-server mysql-server/root_password_again password mogboard" | debconf-set-selections
sudo apt install mysql-server -y -qq
echo "- Importing dev database"
mysql -uroot -pmogboard < /vagrant/vm/Database.sql

#
# Redis
#
echo "Installing: Redis"
sudo apt-get install redis-server -y -qq
echo "Installing: PHPRedis"
git clone https://github.com/phpredis/phpredis.git
cd phpredis && phpize && ./configure && make && sudo make install
cd /vagrant
rm -rf /vagrant/phpredis
echo "- Adding PHPRedis module to PHP"
sudo echo "extension=redis.so" > /etc/php/7.2/mods-available/redis.ini
sudo ln -sf /etc/php/7.2/mods-available/redis.ini /etc/php/7.2/fpm/conf.d/20-redis.ini
sudo ln -sf /etc/php/7.2/mods-available/redis.ini /etc/php/7.2/cli/conf.d/20-redis.ini
sudo service php7.2-fpm restart

#
# Finish
#
echo "Cleaning up ..."
sudo mkdir -p /mogboard/log /mogboard/cache /mogboard/session
sudo chown vagrant:vagrant /mogboard/log /mogboard/cache /mogboard/session
sudo chmod -R 777 /mogboard/log /mogboard/cache /mogboard/session
echo "- Restarting services"
sudo service nginx restart
sudo service php7.2-fpm restart
echo "- Auto removing and cleaning up services"
sudo apt-get autoremove -y -qq
sudo apt-get update -y -qq
sudo apt-get upgrade -y -qq
echo "- Updating db"
php /vagrant/bin/console doctrine:schema:update --force --dump-sql
echo "FINISHED"
