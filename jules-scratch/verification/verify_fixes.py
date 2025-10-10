import re
from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    try:
        # Step 1: Log in by visiting the test-only session setup route
        print("Setting up authenticated session...")
        page.goto("http://localhost:8000/test/setup-session")
        # The controller redirects to /dashboard on success. We wait for that.
        expect(page).to_have_url(re.compile(r".*/dashboard"), timeout=15000)
        print("Session setup successful. User is logged in.")

        # Step 2: Verify the Waste Admin page
        print("Navigating to Waste Admin page...")
        page.goto("http://localhost:8000/waste/admin")

        # Wait for the table to show its initial state (empty or with data).
        # This confirms the page loaded and the initial API call was successful.
        expect(page.locator("#data-table-body tr")).to_be_visible(timeout=10000)

        print("Waste Admin page loaded. Taking screenshot...")
        page.screenshot(path="jules-scratch/verification/waste-admin-verification.png")

        # Step 3: Verify the Profile page
        print("Navigating to Profile page...")
        page.goto("http://localhost:8000/profile")

        # Wait for the profile container to be populated with the user's name.
        # This confirms the JavaScript API call was successful.
        profile_heading = page.get_by_role("heading", name="Test Admin")
        expect(profile_heading).to_be_visible(timeout=10000)

        print("Profile page loaded. Taking screenshot...")
        page.screenshot(path="jules-scratch/verification/profile-verification.png")

        # Step 4: Verify the refactored Employee Management page
        print("Navigating to Employee Management page...")
        page.goto("http://localhost:8000/employees")

        # Wait for the employee table to show its initial state.
        expect(page.locator("#employee-table-body tr")).to_be_visible(timeout=10000)

        print("Employee Management page loaded. Taking screenshot...")
        page.screenshot(path="jules-scratch/verification/employees-verification.png")


        print("Verification script completed successfully.")

    except Exception as e:
        print(f"An error occurred during verification: {e}")
        page.screenshot(path="jules-scratch/verification/error.png")
        # Make sure to close the browser even on error
        browser.close()
        raise

    finally:
        # This block will not be reached if an exception is raised and re-raised
        # The 'finally' in the original code was flawed. Closing in except is better.
        if 'browser' in locals() and browser.is_connected():
            browser.close()


with sync_playwright() as p:
    run(p)