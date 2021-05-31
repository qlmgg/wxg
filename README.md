### 使用说明
    1. cp .env.example .env
    2. 修改其中的数据库配置为自己的配置
    3. 修改 host 配置测试域名为 http://protection.test 
    4. 在命令行执行 php artisan migrate 的数据库迁移
    5. 执行 php artisan db:seed 执行数据库初始化
    6. 访问 http://protection.test 

