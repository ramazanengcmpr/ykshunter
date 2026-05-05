#!/bin/bash
# Render'da PHP built-in server'ı başlatır
PORT=${PORT:-10000}
exec php -S 0.0.0.0:$PORT -t /var/www/html index.php
