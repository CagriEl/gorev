import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { expect, test } from '@playwright/test';
import { advanceTaskToClosed, fetchApiToken, findTaskIdByTitle } from '../helpers/api.js';
import { login, logout } from '../helpers/auth.js';
import {
  expectPageHeading,
  filamentSelect,
  filamentTab,
  logStep,
  nav,
  fillUserForm,
  openCreateRecord,
  pause,
  pickTaskMapLocation,
  saveForm,
  uploadInField,
} from '../helpers/filament.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const photoPath = path.join(__dirname, '../fixtures/photo.png');

const demo = {
  departmentName: 'Sunum Demo Müdürlüğü',
  managerName: 'Sunum Demo Müdür',
  managerEmail: 'sunum-mudur@kirklareli.bel.tr',
  foremanName: 'Sunum Demo Saha Şefi',
  foremanEmail: 'sunum-saha@kirklareli.bel.tr',
  password: 'password',
};

const creds = {
  admin: {
    email: process.env.PLAYWRIGHT_ADMIN_EMAIL ?? 'admin@admin.com',
    password: process.env.PLAYWRIGHT_ADMIN_PASSWORD ?? 'password',
  },
  viceMayor: {
    email: process.env.PLAYWRIGHT_VICE_MAYOR_EMAIL ?? 'aydemircan@kirklareli.bel.tr',
    password: process.env.PLAYWRIGHT_VICE_MAYOR_PASSWORD ?? 'password',
  },
  demoManager: {
    email: process.env.PLAYWRIGHT_DEMO_MANAGER_EMAIL ?? demo.managerEmail,
    password: process.env.PLAYWRIGHT_DEMO_MANAGER_PASSWORD ?? demo.password,
  },
  demoForeman: {
    email: process.env.PLAYWRIGHT_DEMO_FOREMAN_EMAIL ?? demo.foremanEmail,
    password: process.env.PLAYWRIGHT_DEMO_FOREMAN_PASSWORD ?? demo.password,
  },
};

const complaintText = 'Çöpler çok dolu, sokak kokuyor — tam sistem demo';
const taskTitle = `Tam sistem demo ${Date.now()}`;

test.describe.configure({ mode: 'serial' });

test('Bel-Sistem: baştan sona panel makrosu', async ({ page }) => {
  test.setTimeout(600_000);

  // ─── BÖLÜM A: YÖNETİCİ ─────────────────────────────────────────────
  await test.step('A1 — Admin girişi ve gösterge paneli', async () => {
    logStep(1, 'Admin girişi');
    await login(page, creds.admin.email, creds.admin.password);
    await expect(page).toHaveURL(/\/admin\/?$/);
    await expect(page.getByText(/Bel-Sistem|Kırklareli/i).first()).toBeVisible();
    await pause(page);
  });

  await test.step('A2 — Yeni müdürlük oluşturma', async () => {
    logStep(2, 'Sunum Demo müdürlüğü oluştur');
    await nav(page, 'Müdürlükler');
    await expectPageHeading(page, /Müdürlük/i);
    await openCreateRecord(page);
    await page.getByLabel('Müdürlük adı').fill(demo.departmentName);
    await filamentSelect(page, 'Başkan yardımcısı', 'Aydemir');
    await page.getByLabel('Birim müdürü').fill(demo.managerName);
    await page.getByLabel('Müdür telefon').fill('0288 000 00 01');
    await page.getByLabel('Saha şefi', { exact: true }).fill(demo.foremanName);
    await page.getByLabel('Saha şefi telefon').fill('0288 000 00 02');
    await page.getByLabel('Personel sayısı').fill('12');
    await saveForm(page);
    await expect(page.getByText(demo.departmentName).first()).toBeVisible();
    await pause(page);
  });

  await test.step('A3 — Birim yöneticisi kullanıcısı', async () => {
    logStep(3, 'Müdür kullanıcı hesabı');
    await nav(page, 'Kullanıcılar');
    await openCreateRecord(page);
    await fillUserForm(page, {
      name: demo.managerName,
      email: demo.managerEmail,
      password: demo.password,
    });
    await filamentSelect(page, 'Rol', 'Birim yöneticisi');
    await filamentSelect(page, 'Müdürlük', 'Sunum Demo');
    await saveForm(page);
    await expect(page).toHaveURL(/\/admin\/users/);
    await expect(page.getByText(demo.managerName).first()).toBeVisible();
    await pause(page);
  });

  await test.step('A4 — Saha şefi kullanıcısı + müdürlüğe bağlama', async () => {
    logStep(4, 'Saha şefi hesabı ve müdürlük eşlemesi');
    await nav(page, 'Kullanıcılar');
    await openCreateRecord(page);
    await fillUserForm(page, {
      name: demo.foremanName,
      email: demo.foremanEmail,
      password: demo.password,
    });
    await filamentSelect(page, 'Rol', 'Personel');
    await filamentSelect(page, 'Müdürlük', 'Sunum Demo');
    await saveForm(page);

    await nav(page, 'Müdürlükler');
    await page.getByRole('searchbox', { name: 'Ara', exact: true }).fill('Sunum Demo');
    await page.waitForTimeout(600);
    await page.getByRole('link', { name: demo.departmentName }).first().click();
    await filamentSelect(page, 'Saha şefi kullanıcısı', demo.foremanName);
    await page.getByRole('button', { name: /Kaydet|Değişiklikleri/i }).first().click();
    await page.waitForLoadState('networkidle');
    await pause(page);
  });

  await test.step('A5 — Yapay zeka: sınıflandırıcı', async () => {
    logStep(5, 'Müdürlük sınıflandırıcı — tahmin');
    await page.goto('/admin/mudurluk-siniflandirici');
    await expectPageHeading(page, /sınıflandırıcı/i);
    await page.locator('textarea[wire\\:model="testText"]').fill(complaintText);
    await page.getByRole('button', { name: 'Tahmin et' }).click();
    await pause(page, 2500);

    if (await page.getByText('Hazır').isVisible().catch(() => false)) {
      await expect(page.locator('dl').first()).toBeVisible();
      const addBtn = page.getByRole('button', { name: /eğitim örneği olarak kaydet/i });
      if (await addBtn.isVisible().catch(() => false)) {
        await addBtn.click();
        await expect(page.getByText(/kaydedildi/i).first()).toBeVisible();
      }
    } else {
      console.log('   (ML API kapalı — sınıflandırıcı atlandı)');
    }
    await pause(page);
  });

  await test.step('A6 — Model eğitim örnekleri', async () => {
    logStep(6, 'Eğitim örnekleri listesi');
    await page.goto('/admin/classifier-training-samples');
    await expectPageHeading(page, /Eğitim örnek/i);
    await pause(page);
  });

  await test.step('A7 — Görev oluşturma (yeni müdürlük)', async () => {
    logStep(7, 'Yeni görev + harita + varış fotoğrafı');
    await nav(page, 'Görevler');
    await openCreateRecord(page);
    await page.getByLabel('Başlık').fill(taskTitle);
    await filamentSelect(page, 'Müdürlük', 'Sunum Demo');
    await page.waitForTimeout(800);
    await page.getByLabel('Adres / konum').fill('Kırklareli — Sunum Demo müdürlüğü');
    await pickTaskMapLocation(page);
    await filamentSelect(page, 'Öncelik', 'Normal');
    await filamentSelect(page, 'Durum', 'Bekliyor');
    await page.getByLabel('Açıklama').fill(complaintText);
    await filamentTab(page, 'Göreve varış fotoğrafı');
    await uploadInField(page, 'Varış fotoğrafları', photoPath);
    await pause(page);
    await saveForm(page);
    await pause(page);
  });

  await test.step('A8 — Görev yaşam döngüsü ve panelde doğrulama', async () => {
    logStep(8, 'Görev durumları (API) + listede Kapatıldı');
    const token = await fetchApiToken(page.request, creds.admin.email, creds.admin.password);
    const taskId = await findTaskIdByTitle(page.request, token, taskTitle);
    await advanceTaskToClosed(page.request, token, taskId);

    await nav(page, 'Görevler');
    await page.reload();
    await expect(page.getByRole('row', { name: new RegExp(taskTitle) })).toContainText(/Kapatıldı/i);

    await page.getByRole('link', { name: taskTitle }).click();
    await expect(page.locator('main').getByText('Kapatıldı').first()).toBeVisible();
    await pause(page);
  });

  await test.step('A9 — Saha haritası', async () => {
    logStep(9, 'Saha haritası');
    await page.goto('/admin/saha-haritasi');
    await expect(page.getByText(/Saha Haritası|harita/i).first()).toBeVisible();
    await pause(page, 2000);
  });

  await test.step('A10 — Raporlar ve denetim', async () => {
    logStep(10, 'Başkan yardımcısı raporu');
    await page.goto('/admin/baskan-yardimcisi-raporu');
    await expectPageHeading(page, /Başkan yardımcısı/i);
    await pause(page);

    logStep(11, 'Onay talepleri');
    await page.goto('/admin/approval-requests');
    await expectPageHeading(page, /Onay/i);
    await pause(page);

    logStep(12, 'Denetim kayıtları');
    await page.goto('/admin/audit-logs');
    await expectPageHeading(page, /Denetim/i);
    await pause(page);
  });

  await test.step('A11 — Sistem rehberleri', async () => {
    logStep(13, 'Kullanıcı rehberi ve API rehberi');
    await nav(page, 'Kullanıcı Rehberi');
    await expect(page.getByText(/rehber|görev/i).first()).toBeVisible();
    await pause(page);
    await nav(page, 'API Rehberi');
    await expect(page.getByText(/API|token/i).first()).toBeVisible();
    await pause(page);
  });

  // ─── BÖLÜM B: BAŞKAN YARDIMCISI ───────────────────────────────────
  await test.step('B1 — Başkan yardımcısı oturumu', async () => {
    logStep(14, 'Başkan yardımcısı girişi');
    await logout(page);
    await login(page, creds.viceMayor.email, creds.viceMayor.password);
    await page.goto('/admin/baskan-yardimcisi-raporu');
    await expectPageHeading(page, /Başkan yardımcısı/i);
    await page.goto('/admin/tasks');
    await expect(page.getByText(taskTitle).first()).toBeVisible();
    await pause(page);
  });

  // ─── BÖLÜM C: SUNUM DEMO MÜDÜR ────────────────────────────────────
  await test.step('C1 — Sunum demo müdür oturumu', async () => {
    logStep(15, 'Birim yöneticisi (Sunum Demo) girişi');
    await logout(page);
    await login(page, creds.demoManager.email, creds.demoManager.password);
    await nav(page, 'Görevler');
    await expect(page.getByText(taskTitle).first()).toBeVisible();
    await page.getByRole('link', { name: taskTitle }).click();
    await expect(page.locator('main').getByText('Kapatıldı').first()).toBeVisible();
    await pause(page);
  });

  // ─── BÖLÜM D: SUNUM DEMO SAHA ŞEFİ ────────────────────────────────
  await test.step('D1 — Sunum demo saha şefi oturumu', async () => {
    logStep(16, 'Saha şefi (Sunum Demo) girişi');
    await logout(page);
    await login(page, creds.demoForeman.email, creds.demoForeman.password);
    await nav(page, 'Görevler');
    await expect(page.getByRole('main')).toBeVisible();
    await pause(page);
  });

  // ─── BÖLÜM E: API doğrulama ───────────────────────────────────────
  await test.step('E1 — REST API (demo müdür token)', async () => {
    logStep(17, 'API: demo müdür token + görev listesi');
    const token = await fetchApiToken(page.request, creds.demoManager.email, creds.demoManager.password);
    const res = await page.request.get(`${process.env.PLAYWRIGHT_BASE_URL ?? 'http://gorev.test'}/api/v1/tasks`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.ok()).toBeTruthy();
    expect(JSON.stringify(await res.json())).toContain(taskTitle.slice(0, 16));
    await pause(page);
  });

  await test.step('Z — Demo tamamlandı', async () => {
    logStep(18, 'Tam sistem demosu bitti');
    await logout(page);
  });
});
