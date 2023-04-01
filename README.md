### 压缩图片
本项目是使用api进行图片压缩,可以单个图片，也可以传图片所在的目录进行批量压缩

### tinypng APK 
使用此项目进行压缩图片时需要先到https://tinify.cn/developers 申请app key
1.在网站填写full name和email，点击Get your API key按钮
2.email会收到一条邮件，点击可以获取到app key
3.每个app key每个月可以免费压缩500张图片

### 安装使用
1. git clone https://github.com/jackminh/tinypng.git 
2. 执行composer install
3. composer dump-autoload
4. 配置config/tinypng.php,配置正确的app key
5. 调用方法 php public/index.php (index.php中调用方法)
	

