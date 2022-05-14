1)Instalar modulo de PDO y PDO_MYSQL
docker exec -it ucrm docker-php-ext-install pdo_mysql

Agregar Reporsitorio en el Docker de UISP en caso de error
(En el v3.13 cmabiar por la version por indicada en el UISP)

http://repository.fit.cvut.cz/mirrors/alpine/v3.13/main 
http://repository.fit.cvut.cz/mirrors/alpine/v3.13/community

2) Actualizar PCRE con el siguiente comando:
apk --no-cache add pcre-dev ${PHPIZE_DEPS}

3) Instalar Driver UNIX ODBC DEV:
apk --no-cache add unixodbc-dev

4) Instalar PDO SQL_SRV PHP desde pecl:
pecl install sqlsrv pdo_sqlsrv

5) Crear INI para PDO SQL Server:
vi /usr/local/etc/php/conf.d/docker-php-ext-pdo_mssql.ini

Agregar
extension=sqlsrv.so;
extension=pdo_sqlsrv.so;

5) Compilar PLugin
./vendor/bin/pack-plugin

6) Instalar


