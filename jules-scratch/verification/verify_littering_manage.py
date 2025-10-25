
from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch()
    page = browser.new_page()
    page.goto("http://localhost:8000/api/littering_admin/reports?status=processed_for_approval")
    page.wait_for_load_state("networkidle")
    browser.close()

with sync_playwright() as playwright:
    run(playwright)
