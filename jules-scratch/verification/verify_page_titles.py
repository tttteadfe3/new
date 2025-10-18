
from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch()
    context = browser.new_context()
    page = context.new_page()

    # Login
    page.goto("http://localhost:8000/login")
    page.get_by_label("이메일").fill("admin@test.com")
    page.get_by_label("비밀번호").fill("password")
    page.get_by_role("button", name="로그인").click()
    page.wait_for_url("http://localhost:8000/my-page")

    # Check My Page title
    page.goto("http://localhost:8000/my-page")
    assert "마이페이지" in page.title()
    page.screenshot(path="jules-scratch/verification/my-page.png")

    # Check Admin page title
    page.goto("http://localhost:8000/admin/organization")
    assert "부서/직급 관리" in page.title()
    page.screenshot(path="jules-scratch/verification/admin-organization.png")

    context.close()
    browser.close()

with sync_playwright() as playwright:
    run(playwright)
