@servers(['test' => ['root@47.108.67.39'], 'prod' => ['root@47.108.67.39']])

@task('deploy:test', ['on' => 'prod'])
    cd /www/wwwroot/protectionApi
    git pull origin master
    php artisan opcache:clear
    php artisan optimize
@endtask

@task('deploy:prod', ['on' => 'prod'])
    cd /www/wwwroot/protectionApiProduction
    git pull origin master
    php artisan opcache:clear
    php artisan optimize
@endtask

@task('deploy:test:install', ['on' => 'prod'])
    cd /www/wwwroot/protectionApi
    git pull origin master
    php artisan opcache:clear
    composer install
@endtask

@task('deploy:install', ['on' => 'prod'])
    cd /www/wwwroot/protectionApiProduction
    git pull origin master
    php artisan opcache:clear
    composer install
@endtask

@story('deploy', ['on' => 'prod'])
    deploy:prod
    deploy:test
@endstory

@story('install', ['on' => 'prod'])
    deploy:install
    deploy:test:install
@endstory
