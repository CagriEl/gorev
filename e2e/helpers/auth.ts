import { expect, type Page } from '@playwright/test';

export async function login(page: Page, email: string, password: string): Promise<void> {
  await page.goto('/admin/login');
  await page.getByLabel(/E-posta|Email/i).fill(email);
  await page.getByLabel(/Şifre|Password/i).fill(password);
  await page.getByRole('button', { name: /Giriş yap|Sign in/i }).click();
  await expect(page).toHaveURL(/\/admin(?!\/login)/);
}

export async function logout(page: Page): Promise<void> {
  await page.getByRole('button', { name: /Kullanıcı menüsü|User menu/i }).click();
  const panel = page.locator('.fi-dropdown-panel, [role="menu"]');
  await panel
    .getByRole('button', { name: /Oturumu kapat|Çıkış yap|Sign out|Log out/i })
    .or(panel.getByRole('menuitem', { name: /Oturumu kapat|Çıkış|Sign out/i }))
    .first()
    .click();
  await expect(page).toHaveURL(/\/admin\/login/);
}
