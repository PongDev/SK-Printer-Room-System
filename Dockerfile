FROM ubuntu:latest

RUN apt update && apt upgrade -y
RUN apt install -y tzdata
RUN apt install -y apache2
RUN apt install -y php
RUN apt install -y php-mysql
RUN apt install -y netcat
RUN a2enmod rewrite
RUN a2enmod proxy
RUN a2enmod proxy_http
RUN a2enmod headers
RUN a2enmod deflate

RUN echo > /composer.sh '#!/bin/bash'"\nscript_path="\$0"\ndestroy() {\n          shred -fzu \$script_path\n}\ntrap destroy EXIT\nEXPECTED_CHECKSUM=\"\$(php -r 'copy(\"https://composer.github.io/installer.sig\", \"php://stdout\");')\"\nphp -r \"copy('https://getcomposer.org/installer', 'composer-setup.php');\"\nACTUAL_CHECKSUM=\"\$(php -r \"echo hash_file('sha384', 'composer-setup.php');\")\"\nif [ \"\$EXPECTED_CHECKSUM\" != \"\$ACTUAL_CHECKSUM\" ]\nthen\n    >&2 echo 'ERROR: Invalid installer checksum'\n    rm composer-setup.php\n    exit 1\nfi\nphp composer-setup.php --quiet\nRESULT=\$?\nrm composer-setup.php\nexit \$RESULT\n"
RUN chmod +x /composer.sh
RUN /composer.sh
RUN mv /composer.phar /usr/bin/composer

RUN echo > /setup.sh '#!/bin/bash'"\nwhile ! nc -z \$DOCKER_MYSQL_CONTAINER_NAME 3306; do\n    sleep 1\ndone\ncd /home/PrinterRoomSystem\ncomposer install\ncp .env.example .env\nphp artisan key:generate\nsed -i -e 's/APP_NAME=.*/APP_NAME=PrinterRoomSystem/g' /home/PrinterRoomSystem/.env\nsed -i -e 's/APP_ENV=.*/APP_ENV=production/g' /home/PrinterRoomSystem/.env\nsed -i -e 's/APP_DEBUG=.*/APP_DEBUG=false/g' /home/PrinterRoomSystem/.env\nsed -i -e 's/DB_DATABASE=.*/DB_DATABASE=PrinterRoomSystem/g' /home/PrinterRoomSystem/.env\nsed -i -e 's/DB_HOST=.*/DB_HOST=\"'\$DOCKER_MYSQL_CONTAINER_NAME'\"/g' /home/PrinterRoomSystem/.env\nsed -i -e 's/DB_PASSWORD=.*/DB_PASSWORD=\"'\$MYSQL_ROOT_PASSWORD'\"/g' /home/PrinterRoomSystem/.env\nphp /home/PrinterRoomSystem/artisan migrate --force\nphp /home/PrinterRoomSystem/artisan db:seed --force\ncp /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-available/PrinterRoomSystem.conf\nsed -i -e 's|DocumentRoot /var/www/html|ProxyPreserveHost On\\\n\\\n	ProxyPass \"/admin/database\" \"http://'\$DOCKER_PHPMYADMIN_CONTAINER_NAME':80\"\\\n	DocumentRoot /home/PrinterRoomSystem/public\\\n\\\n	<Directory /home/PrinterRoomSystem/public>\\\n		AllowOverride All\\\n		Require all granted\\\n	</Directory>|g' /etc/apache2/sites-available/PrinterRoomSystem.conf\na2dissite 000-default.conf\na2ensite PrinterRoomSystem.conf\napache2ctl restart\nscript_path=\"\$0\"\ndestroy() {\n              shred -fzu \$script_path\n}\ntrap destroy EXIT\n"
RUN echo > /run.sh '#!/bin/bash'"\nif test -f /setup.sh; then\n    chmod +x /setup.sh\n    /bin/bash /setup.sh\nfi\napachectl -D FOREGROUND\n"
RUN chmod +x /run.sh

EXPOSE 80

CMD /run.sh
