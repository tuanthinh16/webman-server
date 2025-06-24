FROM mysql:8.0

# Set timezone
ENV TZ=Asia/Ho_Chi_Minh
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Copy SQL script to initialize database
COPY ./docker/mysql/init.sql /docker-entrypoint-initdb.d/

# Set character set
RUN { \
    echo '[mysqld]'; \
    echo 'character-set-server = utf8mb4'; \
    echo 'collation-server = utf8mb4_unicode_ci'; \
    echo '[client]'; \
    echo 'default-character-set = utf8mb4'; \
    } > /etc/mysql/conf.d/charset.cnf

EXPOSE 3306