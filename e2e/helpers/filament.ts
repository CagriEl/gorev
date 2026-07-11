import { expect, type Page } from '@playwright/test';

export function logStep(step: number, message: string): void {
  console.log(`\n━━━ ${step}. ${message} ━━━`);
}

export async function pause(page: Page, ms = 1500): Promise<void> {
  const slow = Number(process.env.PLAYWRIGHT_SLOW_MO ?? '0');
  if (slow > 0) {
    await page.waitForTimeout(ms);
  }
}

export async function nav(page: Page, label: string): Promise<void> {
  const link = page.getByRole('navigation').getByRole('link', { name: label });
  if (await link.isVisible().catch(() => false)) {
    await link.click();
  } else {
    await page.getByRole('link', { name: label }).first().click();
  }
  await page.waitForLoadState('domcontentloaded');
}

export async function filamentSelect(page: Page, label: string, optionText: string): Promise<void> {
  const field = page.locator('.fi-fo-field-wrp').filter({ hasText: label }).first();
  const combobox = field.getByRole('combobox');
  const listbox = field.getByRole('listbox');

  if ((await combobox.count()) > 0) {
    await combobox.first().click();
  } else if ((await listbox.count()) > 0) {
    await listbox.first().click();
  } else {
    await field.locator('button').first().click();
  }

  const escaped = optionText.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  await page.getByRole('option', { name: new RegExp(escaped.slice(0, 14), 'i') }).first().click();
}

export async function filamentTab(page: Page, tabName: string): Promise<void> {
  await page.getByRole('tab', { name: tabName }).click();
}

export async function uploadInField(page: Page, label: string, filePath: string): Promise<void> {
  const field = page.locator('.fi-fo-field-wrp').filter({ hasText: label }).first();
  await field.locator('input[type="file"]').setInputFiles(filePath);
  await expect(field.getByText(/Yükleme tamamlandı|Upload complete/i).first()).toBeVisible({
    timeout: 30_000,
  });
  await page.waitForTimeout(1500);
}

export async function saveForm(page: Page): Promise<void> {
  const urlBefore = page.url();
  await page
    .getByRole('button', { name: /Kaydet|Oluştur|Değişiklikleri|Save|Create/i })
    .filter({ visible: true })
    .first()
    .click();

  try {
    await expect(page).not.toHaveURL(urlBefore, { timeout: 10_000 });
  } catch {
    await page.waitForLoadState('networkidle');
    await expect(page.getByText('validation.required')).toHaveCount(0, { timeout: 15_000 });
  }
  await page.waitForLoadState('networkidle');
}

export async function ensureAssigneeSelected(page: Page): Promise<void> {
  const assignee = page.getByRole('combobox', { name: /Atanan/i });
  const text = await assignee.textContent();
  if (text?.includes('Bir seçenek')) {
    await assignee.click();
    await page.getByRole('option').first().click();
  }
}

export async function goToTaskEdit(page: Page, taskTitle: string): Promise<void> {
  if (page.url().includes('/edit')) {
    return;
  }

  if (/\/tasks\/\d+$/.test(page.url())) {
    await page.getByRole('link', { name: /Düzenle/i }).click();
    return;
  }

  await nav(page, 'Görevler');
  await page.getByRole('link', { name: taskTitle }).click();
  await page.getByRole('link', { name: /Düzenle/i }).click();
}

export async function stabilizeArrivalPhotos(page: Page, photoPath: string): Promise<void> {
  await filamentTab(page, 'Göreve varış fotoğrafı');

  if (await page.getByText('Yükleniyor').isVisible().catch(() => false)) {
    const cancel = page.getByRole('button', { name: /İptal|Kaldır|Remove/i }).filter({ visible: true });
    if ((await cancel.count()) > 0) {
      await cancel.first().click();
      await page.waitForTimeout(800);
    }
  }

  if (!(await page.getByText(/Yükleme tamamlandı/i).first().isVisible().catch(() => false))) {
    await uploadInField(page, 'Varış fotoğrafları', photoPath);
  }
}

export async function expectPageHeading(page: Page, pattern: RegExp | string): Promise<void> {
  await expect(page.getByRole('heading', { name: pattern }).first()).toBeVisible();
}

export async function fillUserForm(
  page: Page,
  data: { name: string; email: string; password: string },
): Promise<void> {
  await page.getByLabel('Ad soyad').fill(data.name);
  await page.locator('#data\\.email').fill(data.email);
  await page.locator('#data\\.password').fill(data.password);
}

export async function openCreateRecord(page: Page): Promise<void> {
  const create = page
    .getByRole('link', { name: /Oluştur/i })
    .or(page.getByRole('button', { name: /Oluştur/i }));
  await create.first().click();
  await page.waitForLoadState('domcontentloaded');
}

/** Görev formunda haritadan örnek koordinat (Kırklareli merkez). */
export async function pickTaskMapLocation(page: Page, lat = 41.734, lng = 27.222): Promise<void> {
  await page.evaluate(
    ({ lat, lng }) => {
      const root = document.querySelector('[x-data*="leafletTaskLocation"]');
      if (!root) {
        return;
      }
      const wireId = (root as HTMLElement).getAttribute('wire:id')
        ?? (root.closest('[wire\\:id]') as HTMLElement | null)?.getAttribute('wire:id');
      const wire = wireId ? (window as unknown as { Livewire: { find: (id: string) => { $set: (k: string, v: number, l?: boolean) => void } } }).Livewire.find(wireId) : null;
      if (wire?.$set) {
        wire.$set('data.latitude', lat, true);
        wire.$set('data.longitude', lng, true);
      }
    },
    { lat, lng },
  );
  await page.waitForTimeout(500);
}
