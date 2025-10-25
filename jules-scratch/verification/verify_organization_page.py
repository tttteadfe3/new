
from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Log in
    page.goto("http://localhost:8000/login")
    with page.expect_popup() as popup_info:
        page.click("a[href^='/auth/kakao/callback']")
    popup = popup_info.value
    popup.wait_for_load_state()
    popup.click("input[type=submit]")

    # Navigate to the organization management page
    page.goto("http://localhost:8000/admin/organization")

    # Wait for the department list to load
    page.wait_for_selector("#departments-list-container .list-group-item")

    # Take a screenshot
    page.screenshot(path="jules-scratch/verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
