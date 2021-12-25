#!/bin/bash

sudo apt update
sudo apt install -y apache2
sudo a2enmod rewrite
sudo ufw enable
sudo ufw allow Apache
sudo apt install -y php
sudo apt install -y mysql-server
sudo mysql_secure_installation

read -esp "Enter Database Password:" db_password
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH caching_sha2_password BY '"$db_password"';"
sudo mysql -u root -p$db_password -e "CREATE DATABASE PrinterRoomSystem CHARACTER SET utf8 COLLATE utf8_bin;"

sudo apt install -y phpmyadmin

sudo chown -R $USER:www-data PrinterRoomSystem/storage
sudo chown -R $USER:www-data PrinterRoomSystem/bootstrap/cache
chmod -R 755 PrinterRoomSystem
chmod -R 775 PrinterRoomSystem/storage
chmod -R 775 PrinterRoomSystem/bootstrap/cache

sudo bash -c 'printf "\nInclude /etc/phpmyadmin/apache.conf">>/etc/apache2/apache2.conf'
sudo sed -i -e 's|Alias /phpmyadmin|Alias /admin/database|g' /etc/phpmyadmin/apache.conf

sudo cp /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-available/PrinterRoomSystem.conf
sudo sed -i -e 's|DocumentRoot /var/www/html|DocumentRoot '$(pwd)'/PrinterRoomSystem/public\n\n	<Directory '$(pwd)'/PrinterRoomSystem/public>\n		AllowOverride All\n		Require all granted\n	</Directory>|g' /etc/apache2/sites-available/PrinterRoomSystem.conf

sudo echo -e > ./composer.sh '#!/bin/bash'"\nscript_path="\$0"\ndestroy() {\n          shred -fzu \$script_path\n}\ntrap destroy EXIT\nEXPECTED_CHECKSUM=\"\$(php -r 'copy(\"https://composer.github.io/installer.sig\", \"php://stdout\");')\"\nphp -r \"copy('https://getcomposer.org/installer', 'composer-setup.php');\"\nACTUAL_CHECKSUM=\"\$(php -r \"echo hash_file('sha384', 'composer-setup.php');\")\"\nif [ \"\$EXPECTED_CHECKSUM\" != \"\$ACTUAL_CHECKSUM\" ]\nthen\n    >&2 echo 'ERROR: Invalid installer checksum'\n    rm composer-setup.php\n    exit 1\nfi\nphp composer-setup.php --quiet\nRESULT=\$?\nrm composer-setup.php\nexit \$RESULT\n"
sudo chmod +x ./composer.sh
sudo ./composer.sh
sudo mv ./composer.phar /usr/bin/composer

sudo a2dissite 000-default.conf
sudo a2ensite PrinterRoomSystem.conf
sudo systemctl restart apache2

cd PrinterRoomSystem

composer install
cp .env.example .env
php artisan key:generate
sed -i -e 's/APP_NAME=.*/APP_NAME=PrinterRoomSystem/g' .env
sed -i -e 's/APP_ENV=.*/APP_ENV=production/g' .env
sed -i -e 's/APP_DEBUG=.*/APP_DEBUG=false/g' .env
sed -i -e 's/DB_DATABASE=.*/DB_DATABASE=PrinterRoomSystem/g' .env
sed -i -e 's/DB_PASSWORD=.*/DB_PASSWORD=\"'$db_password'\"/g' .env
php artisan migrate --force
php artisan db:seed --force

script_path="$0"

destroy() {
  shred -fzu $script_path
}
trap destroy EXIT
