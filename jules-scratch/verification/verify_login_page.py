from playwright.sync_api import sync_playwright, expect, Page

def verify_login_page(page: Page):
    """
    This test verifies that the root route successfully displays the login page.
    """
    # 1. Go to the application's root URL.
    page.goto("http://localhost:8000/")

    # 2. Check that the main heading of the login page is visible.
    # The text is "로그인 후 사용 가능합니다." (Login is required to use.)
    heading = page.get_by_role("heading", name="로그인 후 사용 가능합니다.")
    expect(heading).to_be_visible()

    # 3. Check that the Kakao login button is present.
    kakao_button = page.get_by_role("link", name="카카오 계정으로 로그인")
    expect(kakao_button).to_be_visible()

    # 4. Capture the final result for visual verification.
    page.screenshot(path="jules-scratch/verification/verification.png")

def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        try:
            verify_login_page(page)
            print("Verification script ran successfully.")
        except Exception as e:
            print(f"Verification script failed: {e}")
            # Take a screenshot on failure for debugging purposes.
            page.screenshot(path="jules-scratch/verification/verification_error.png")
        finally:
            browser.close()

if __name__ == "__main__":
    main()