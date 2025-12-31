import time
from selenium import webdriver
from selenium.webdriver.common.by import By

def test_button_placeholder(): 
    try: 
        driver = setup()

        #Click button with "Resolve" label
        #resolve_button = driver.find_element(By.CSS_SELECTOR, "input[placeholder*='Resolve']")
        #resolve_button.click()
        #print("\"Resolve\" button works.")

        #sleep or wait
        time.sleep(10)

    finally: 
        teardown(driver)

def setup(): 
    driver = webdriver.Chrome()
    driver.get("http://localhost/UKMtrash_system/landing.php")
    return driver

def teardown(driver): 
    driver.quit()

test_button_placeholder()