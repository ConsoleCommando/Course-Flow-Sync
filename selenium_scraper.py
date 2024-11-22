from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from browsermobproxy import Server
import time
import json
import os

# Start BrowserMob Proxy
browsermob_binary_path = './browsermob-proxy/bin/browsermob-proxy'
server = Server(browsermob_binary_path)
server.start()
proxy = server.create_proxy()

# Set up Selenium WebDriver to use the Proxy
chrome_options = webdriver.ChromeOptions()
chrome_options.add_argument(f'--proxy-server={proxy.proxy}')
chrome_options.add_argument('--ignore-certificate-errors')
chrome_options.add_argument('--headless')
chrome_options.add_argument('--no-sandbox')  
chrome_options.add_argument("--disable-gpu")
chrome_options.add_argument("--disable-software-rasterizer")
chrome_options.add_argument('--disable-dev-shm-usage') 

# Specify the path to your ChromeDriver
chrome_driver_path = './chromedriver'

service = Service(chrome_driver_path)

# Start the Chrome WebDriver
driver = webdriver.Chrome(service=service, options=chrome_options)

# Enable proxy to capture the network traffic
proxy.new_har("course_flow", options={'captureHeaders': True, 'captureContent': True})

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

# Initialize a list to store all data
all_data = []

# Loop to get all pages
page_number = 1  # Start with page 1
while True:
    # Get the captured HAR (HTTP Archive) data from BrowserMob Proxy
    har_data = proxy.har

    # Flag to track if we found the new page's data
    page_data_found = False

    # Extract data from the HAR logs
    for entry in har_data['log']['entries']:
        request_url = entry['request']['url']
        mime_type = entry['response']['content']['mimeType']
        
        # Only process the response that contains search results
        if 'searchResults' in request_url and mime_type == 'application/json;charset=UTF-8':
            json_data = entry['response']['content']['text']
            print("Found JSON Data")
            page_data_found = True  # Mark that we have found the new page's data
            page_data = json.loads(json_data)  # Extract the data for this page

            # Only extract the 'data' array and add it to the all_data list
            if 'data' in page_data:
                all_data.extend(page_data['data'])  # Add the 'data' array to the overall data array

    # If new data is found, save it to a new file
    if page_data_found:
        file_name = f'course_data_page_{page_number}.json'  # Create a unique file name for each page
        with open(file_name, 'w') as json_file:
            json.dump(page_data, json_file, indent=4)
        print(f"Page data saved to '{file_name}'")

    # Try to find the 'Next' button to move to the next page
    try:
        next_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.CLASS_NAME, 'paging-control.next.ltr.enabled')))
        next_button.click()  # Click the 'Next' button
        time.sleep(2)  # Wait for the next page to load
        page_number += 1  # Increment the page number for the next file
    except:
        print("No more pages to scrape.")
        break  # Exit the loop if no 'Next' button is found

# After scraping all pages, combine the data from the saved files
print("Combining data from all pages...")

# Initialize a dictionary for the combined structure with a 'data' array
combined_structure = {'data': []}

# Specify the directory where the course data files are saved
directory = '.'  # Assuming the JSON files are in the current directory

# Loop through each file in the directory
for filename in os.listdir(directory):
    if filename.endswith('.json') and filename.startswith('course_data_page_'):
        # Open each individual file
        with open(os.path.join(directory, filename), 'r') as file:
            # Load the full JSON data from the file
            page_data = json.load(file)
            
            # Extract the 'data' array and add it to the combined structure
            if 'data' in page_data:
                combined_structure['data'].extend(page_data['data'])  # Combine 'data' arrays

# Write the combined structure with 'data' array to a new JSON file
with open('course_data.json', 'w') as combined_file:
    json.dump(combined_structure, combined_file, indent=4)

print("All 'data' arrays combined into 'course_data.json' with the 'data' array structure.")

# Delete all individual page JSON files except the combined one
for filename in os.listdir(directory):
    if filename.endswith('.json') and filename.startswith('course_data_page_'):
        try:
            os.remove(os.path.join(directory, filename))
            print(f"Deleted {filename}")
        except Exception as e:
            print(f"Error deleting {filename}: {e}")

# Close the browser and stop the proxy
driver.quit()
server.stop()
