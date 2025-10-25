from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Navigate to the positions page directly.
    # If not logged in, this will likely redirect to the login page.
    # The screenshot will capture whatever is rendered.
    page.goto("http://localhost:8000/admin/positions", wait_until="networkidle")

    # Take a screenshot to verify the result.
    page.screenshot(path="jules-scratch/verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
