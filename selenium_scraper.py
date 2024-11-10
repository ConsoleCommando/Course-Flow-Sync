from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from browsermobproxy import Server
import time
import json

url = 'https://sis-reg.utc.edu/StudentRegistrationSsb/ssb/registration'

# Might have to download a different version of browsermob based on OS
# Start BrowserMob Proxy
browsermob_binary_path = 'browsermob-proxy\\bin\\browsermob-proxy.bat'
server = Server(browsermob_binary_path)
server.start()
proxy = server.create_proxy()

# Set up Selenium WebDriver to use the Proxy
chrome_options = webdriver.ChromeOptions()
chrome_options.add_argument(f'--proxy-server={proxy.proxy}')

# Start the Chrome WebDriver
driver = webdriver.Chrome(options=chrome_options)

# Enable proxy to capture the network traffic
proxy.new_har("course_flow", options={'captureHeaders': True, 'captureContent': True})

# Go to the website
driver.get(url)

# Wait for the link with the id 'classSearchLink' to be clickable
link = WebDriverWait(driver, 20).until(
    EC.element_to_be_clickable((By.ID, 'classSearchLink'))
)
link.click()

# Wait for the page to load
time.sleep(2) 

a_tag = WebDriverWait(driver, 20).until(
    EC.element_to_be_clickable((By.CSS_SELECTOR, "a.select2-choice.select2-default"))
)
a_tag.click()
input_field = driver.find_element(By.ID, 's2id_autogen1_search')
input_field.send_keys('fall 2024')


# Wait for the dropdown list to appear (it might take a second)
WebDriverWait(driver, 20).until(
    EC.visibility_of_element_located((By.ID, 'select2-results-1'))
)

# Select the first option in the dropdown
first_option = driver.find_element(By.XPATH, '//*[@id="select2-results-1"]/li[1]')
first_option.click()

time.sleep(1)
input_field.send_keys(Keys.ENTER)

# Click the continue button
WebDriverWait(driver, 20).until(
    EC.element_to_be_clickable((By.ID, 'term-go'))
)
continue_button = driver.find_element(By.ID, 'term-go')
continue_button.click()

# Wait for any form submission or page transition (if necessary)
time.sleep(2) 

# Find the input field and input text to trigger the dropdown
search_field = driver.find_element(By.ID, 's2id_autogen5')
search_field.send_keys('Aborn')  # Replace with professors last name

time.sleep(1)
search_field.send_keys(Keys.ENTER)


# If there's a visible search button, click it
WebDriverWait(driver, 20).until(
    EC.element_to_be_clickable((By.ID, 'search-go'))
  )

search_button = driver.find_element(By.ID, 'search-go') 
search_button.click()

# Capture the GET request response
time.sleep(2)

# Get the captured HAR (HTTP Archive) data from BrowserMob Proxy
har_data = proxy.har 

print(har_data)


for entry in har_data['log']['entries']:
    request_url = entry['request']['url']
    mime_type = entry['response']['content']['mimeType']
    content_length = entry['response']['content'].get('size', 'N/A')
    
    # Only continue if the URL contains 'searchResults' and the response is JSON
    if 'searchResults' in request_url:
        if mime_type == 'application/json;charset=UTF-8':
            json_data = entry['response']['content']['text']
            print("Found JSON Data")
            break

# Save the JSON data to a file
if json_data:
    with open('course_data.json', 'w') as json_file:
        json.dump(json.loads(json_data), json_file, indent=4)
    print("Data saved to 'course_data.json'")

# Close the browser and stop the proxy
driver.quit()
server.stop()