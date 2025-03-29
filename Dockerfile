FROM php:8.0-apache

# ติดตั้ง mysqli extension และ extension อื่นๆ ที่จำเป็น
RUN docker-php-ext-install mysqli pdo pdo_mysql

# คัดลอกไฟล์แอปพลิเคชันทั้งหมดไปยังคอนเทนเนอร์
COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]
