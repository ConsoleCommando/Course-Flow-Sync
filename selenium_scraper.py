import time
import pyperclip
from selenium import webdriver
from selenium.webdriver.firefox.service import Service
from selenium.webdriver.firefox.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

pyperclip.set_clipboard("xclip")

# Define the base URL with pageMaxSize set to a large value
base_url = 'https://sis-reg.utc.edu/StudentRegistrationSsb/ssb/searchResults/searchResults?txt_subject=CPSC&txt_term=202440&startDatepicker=&endDatepicker=&uniqueSessionId={}&pageOffset=0&pageMaxSize=1000&sortColumn=subjectDescription&sortDirection=asc'

# Set up Firefox options
firefox_options = Options()

# Path to geckodriver
geckodriver_path = '/usr/local/bin/geckodriver'

service = Service(geckodriver_path)

# Start Firefox WebDriver with the service and options
driver = webdriver.Firefox(service=service, options=firefox_options)

def get_unique_session_id(driver):
    # Extract uniqueSessionId from network resources.
    har_data = driver.execute_script("return window.performance.getEntriesByType('resource');")
    for entry in har_data:
        request_url = entry['name']
        if 'uniqueSessionId' in request_url:
            return request_url.split('uniqueSessionId=')[1].split('&')[0]
    return None

# Go to the registration page
url = 'https://sis-reg.utc.edu/StudentRegistrationSsb/ssb/registration'
driver.get(url)

# Navigate to the search page and select term
link = WebDriverWait(driver, 20).until(EC.element_to_be_clickable((By.ID, 'classSearchLink')))
link.click()

a_tag = WebDriverWait(driver, 20).until(EC.element_to_be_clickable((By.ID, "select2-chosen-1")))
a_tag.click()

# Wait for the page to load and select the term
a_tag = WebDriverWait(driver, 20).until(EC.element_to_be_clickable((By.ID, "202440")))
a_tag.click()

# Click the continue button
continue_button = WebDriverWait(driver, 20).until(EC.element_to_be_clickable((By.ID, 'term-go')))
continue_button.click()

# Wait for the page to load
time.sleep(2)

# Input 'CPSC' in the course search
search_field = driver.find_element(By.ID, 's2id_autogen1')
search_field.send_keys('CPSC')

# Select the first option in the dropdown
time.sleep(2)
first_option = driver.find_element(By.ID, 'CPSC')
first_option.click()

# Click the search button
search_button = WebDriverWait(driver, 20).until(EC.element_to_be_clickable((By.ID, 'search-go')))
search_button.click()

# Wait for the search results page to load
time.sleep(2)

# Extract uniqueSessionId from network traffic
unique_session_id = get_unique_session_id(driver)

# If we found the uniqueSessionId, construct the URL and navigate to it in Selenium
if unique_session_id:
    full_url = base_url.format(unique_session_id, 0)
    print(f"Using URL: {full_url}")
    
    # Use Selenium to navigate to the full URL
    driver.get(full_url)
else:
    print("uniqueSessionId not found.")

# Find and click the "Copy" button
copy_button = WebDriverWait(driver, 20).until(EC.element_to_be_clickable((By.CLASS_NAME, 'btn.copy')))
copy_button.click()

# Wait for the content to be copied to the clipboard
time.sleep(1)

# Retrieve the copied data from the clipboard using pyperclip
copied_data = pyperclip.paste()

# Format the data as JSON (assuming it's in a JSON-compatible format)
import json
try:
    json_data = json.loads(copied_data)
    
    # Define the path for saving the JSON file
    json_file_path = 'course_data.json'  # Save in the same directory as the script

    # Save the formatted JSON to the file
    with open(json_file_path, 'w') as json_file:
        json.dump(json_data, json_file, indent=4)
    print(f"Data saved to {json_file_path}")
except json.JSONDecodeError:
    print("The copied data is not valid JSON.")

# Close the browser
driver.quit()
