# 1. Chọn image chính thức Elasticsearch
FROM docker.elastic.co/elasticsearch/elasticsearch:8.12.0

# 2. Thiết lập biến môi trường tối thiểu cho single-node
ENV discovery.type=single-node \
    ES_JAVA_OPTS=-Xms512m

# 3. Tạo user chỉ định (nếu cần)
# USER elasticsearch

# 4. Expose