import json
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from webdriver_manager.chrome import ChromeDriverManager

def scrape_webpage(url):
    # Set up the Selenium WebDriver using webdriver_manager
    service = Service(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=service)
    
    try:
        # Open the webpage
        driver.get(url)
        
        # Extract all text from the webpage
        body = driver.find_element(By.TAG_NAME, 'body')
        text = body.text
        
        # Save the text to a dictionary
        data = {'text': text}
        
        # Save the data to a JSON file
        with open('scraped_data.json', 'w', encoding='utf-8') as json_file:
            json.dump(data, json_file, ensure_ascii=False, indent=4)
    
    finally:
        driver.quit()

if __name__ == '__main__':
    url = 'https://www.utc.edu/'  # Replace with your target URL
    scrape_webpage(url)