import re
from playwright.sync_api import Page, expect, sync_playwright

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    console_messages = []
    page.on("console", lambda msg: console_messages.append(f"[{msg.type}] {msg.text}"))

    try:
        page.goto("http://localhost:8000/test-login", timeout=15000)
        expect(page).to_have_url(re.compile(r".*/holidays"), timeout=15000)
        expect(page.get_by_role("heading", name="휴일/근무일 설정")).to_be_visible(timeout=10000)
        print("Successfully logged in and navigated to holidays page.")

        add_button = page.get_by_role("button", name="휴일/근무일 등록")
        add_button.click()

        modal_title = page.locator("#holidayModalLabel")
        expect(modal_title).to_have_text("휴일/근무일 등록")
        print("Add holiday modal opened.")

        page.locator("#holidayName").fill("Jules-Day")
        page.locator("#holidayDate").fill("2025-10-24")
        page.locator("#holidayType").select_option("holiday")
        print("Form filled out.")

        save_button = page.get_by_role("button", name="저장")
        save_button.click()
        print("Save button clicked.")

        new_holiday_row = page.locator("tbody#holidays-table-body tr", has_text="Jules-Day")
        expect(new_holiday_row).to_be_visible(timeout=5000)
        expect(new_holiday_row.locator("td").nth(1)).to_have_text("2025-10-24")
        print("New holiday 'Jules-Day' successfully verified in the table.")

        screenshot_path = "jules-scratch/verification/holidays_verification.png"
        page.screenshot(path=screenshot_path)
        print(f"Screenshot saved to {screenshot_path}")

    except Exception as e:
        print("\n--- CONSOLE MESSAGES ---")
        for msg in console_messages:
            print(msg)
        print("------------------------\n")

        # --- Start of new code: Capture and print HTML content on error ---
        page_content = page.content()
        print("\n--- PAGE HTML CONTENT ON ERROR ---")
        print(page_content)
        print("----------------------------------\n")
        # --- End of new code ---

        print(f"An error occurred during Playwright verification: {e}")
        page.screenshot(path="jules-scratch/verification/error.png")
        raise

    finally:
        context.close()
        browser.close()

if __name__ == "__main__":
    with sync_playwright() as playwright:
        run(playwright)