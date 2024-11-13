#!/bin/bash

# Run the scraper inside the Docker container using xvfb-run
sudo docker exec php-nginx xvfb-run python3 selenium_scraper.py


