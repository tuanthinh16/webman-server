FROM redis:6.2

# Set timezone
ENV TZ=Asia/Ho_Chi_Minh
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Custom redis.conf
COPY ./docker/redis/redis.conf /usr/local/etc/redis/redis.conf

# Run Redis with custom config
CMD [ "redis-server", "/usr/local/etc/redis/redis.conf" ]

EXPOSE 6379