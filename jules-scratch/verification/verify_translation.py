from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch()
    page = browser.new_page()
    page.goto("http://localhost:8000/login")
    page.screenshot(path="jules-scratch/verification/translation_verification.png")
    browser.close()

with sync_playwright() as playwright:
    run(playwright)
