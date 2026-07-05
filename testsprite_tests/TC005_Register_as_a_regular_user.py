import asyncio
import re
from playwright import async_api
from playwright.async_api import expect

async def run_test():
    pw = None
    browser = None
    context = None

    try:
        # Start a Playwright session in asynchronous mode
        pw = await async_api.async_playwright().start()

        # Launch a Chromium browser in headless mode with custom arguments
        browser = await pw.chromium.launch(
            headless=True,
            args=[
                "--window-size=1280,720",
                "--disable-dev-shm-usage",
                "--ipc=host",
                "--single-process"
            ],
        )

        # Create a new browser context (like an incognito window)
        context = await browser.new_context()
        # Wider default timeout to match the agent's DOM-stability budget;
        # auto-waiting Playwright APIs (expect, locator.wait_for) inherit this.
        context.set_default_timeout(15000)

        # Open a new page in the browser context
        page = await context.new_page()

        # Interact with the page elements to simulate user flow
        # -> navigate
        await page.goto("http://localhost:80/Test%20Store/index.php")
        try:
            await page.wait_for_load_state("domcontentloaded", timeout=5000)
        except Exception:
            pass
        
        # -> Open the registration page by navigating to the site's 'Register' URL (the register.php page) so the registration form can be filled.
        await page.goto("http://localhost/Test%20Store/auth/register.php")
        try:
            await page.wait_for_load_state("domcontentloaded", timeout=5000)
        except Exception:
            pass
        
        # -> input
        # Choose a username text field
        elem = page.locator('[id="username"]')
        await elem.wait_for(state="visible", timeout=10000)
        await elem.fill("testuser20260626_1")
        
        # -> input
        # you@example.com email field
        elem = page.locator('[id="email"]')
        await elem.wait_for(state="visible", timeout=10000)
        await elem.fill("testuser20260626_1@example.com")
        
        # -> input
        # Min. 8 characters password field
        elem = page.locator('[id="password"]')
        await elem.wait_for(state="visible", timeout=10000)
        await elem.fill("test12345")
        
        # -> input
        # Repeat your password password field
        elem = page.locator('[id="password2"]')
        await elem.wait_for(state="visible", timeout=10000)
        await elem.fill("test12345")
        
        # -> click
        # person_add Create Account button
        elem = page.get_by_role('button', name='person_add Create Account', exact=True)
        await elem.click(timeout=10000)
        
        # --> Assertions to verify final state
        
        # --> Verify the account is created and the user lands on the correct destination
        # Assert: The URL contains '/Test%20Store/index.php', confirming the user landed on the home page.
        await expect(page).to_have_url(re.compile("/Test%20Store/index\\.php"), timeout=15000), "The URL contains '/Test%20Store/index.php', confirming the user landed on the home page."
        # Assert: The navbar displays the username 'testuser20260626_1', confirming the account was created and the user is logged in.
        await expect(page.locator("xpath=/html/body/nav/div/div/ul/li[3]/a").nth(0)).to_have_text("testuser20260626_1", timeout=15000), "The navbar displays the username 'testuser20260626_1', confirming the account was created and the user is logged in."
        await asyncio.sleep(5)

    finally:
        if context:
            await context.close()
        if browser:
            await browser.close()
        if pw:
            await pw.stop()

asyncio.run(run_test())
    