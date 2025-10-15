
import re
from playwright.sync_api import sync_playwright, Page, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    try:
        # Step 1: Log in
        page.goto("http://127.0.0.1:8000/login")
        # This is a mock login based on how kakao auth works.
        # A real test would require handling the full OAuth flow.
        page.evaluate("""
            () => {
                const user = {
                    id: 1,
                    kakao_id: '12345',
                    email: 'admin@example.com',
                    nickname: 'Admin',
                    employee_id: 1,
                    roles: ['Super Admin'],
                    permissions: ['organization.manage']
                };
                // Simulate session creation by setting a value
                // In a real app, this would be a secure session cookie
                window.sessionStorage.setItem('mock_user', JSON.stringify(user));
            }
        """)


        # Step 2: Navigate to the organization admin page
        page.goto("http://127.0.0.1:8000/admin/organization")

        # Wait for the department list to be loaded
        expect(page.locator("#departments-list-container .list-group-item")).not_to_contain_text("로딩 중...", timeout=10000)


        # Step 3: Click the first edit button in the department list
        edit_button = page.locator("#departments-list-container .edit-btn").first
        expect(edit_button).to_be_visible()
        edit_button.click()

        # Step 4: Verify the modal and the checkbox
        modal = page.locator("#org-modal")
        expect(modal).to_be_visible()

        checkbox_label = page.get_by_label("전체 휴가내역 조회 권한")
        expect(checkbox_label).to_be_visible()

        # Step 5: Take a screenshot
        page.screenshot(path="jules-scratch/verification/verification.png")

    finally:
        browser.close()

with sync_playwright() as playwright:
    run(playwright)
