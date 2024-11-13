@echo off

docker exec php-nginx xvfb-run python3 /var/www/html/selenium_scraper.py