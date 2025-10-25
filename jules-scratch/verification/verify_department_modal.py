from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch()
    page = browser.new_page()
    page.goto("http://127.0.0.1:8000/admin/organization")

    # "새 부서 추가" 버튼을 클릭합니다.
    page.click("#add-department-btn")

    # 모달이 나타날 때까지 기다립니다.
    page.wait_for_selector("#org-modal.show")

    # 새로운 필드가 있는지 확인합니다.
    viewer_employee_label = page.locator("label[for='viewer-employee-ids']")
    viewer_department_label = page.locator("label[for='viewer-department-ids']")

    assert viewer_employee_label.is_visible()
    assert viewer_department_label.is_visible()

    # 모달의 스크린샷을 찍습니다.
    page.screenshot(path="jules-scratch/verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
