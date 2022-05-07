1)Instalar modulo de PDO y PDO_MYSQL
docker exec -it ucrm docker-php-ext-install pdo_mysql

Agregar Reporsitorio en el Docker de UISP en caso de error
(En el v3.13 cmabiar por la version por indicada en el UISP)

http://repository.fit.cvut.cz/mirrors/alpine/v3.13/main 
http://repository.fit.cvut.cz/mirrors/alpine/v3.13/community

2) Compilar PLugin
./vendor/bin/pack-plugin

3) Instalar

4) Subir APK en el Data/APK

