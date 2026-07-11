import { expect, type APIRequestContext } from '@playwright/test';

const baseURL = () => process.env.PLAYWRIGHT_BASE_URL ?? 'http://gorev.test';

type TaskPayload = {
  id: number;
  assignee?: { id: number | null };
  arrival_photos?: string[];
};

export async function fetchApiToken(
  request: APIRequestContext,
  email: string,
  password: string,
): Promise<string> {
  const res = await request.post(`${baseURL()}/api/v1/auth/token`, {
    data: { email, password, device_name: 'playwright-e2e' },
  });
  expect(res.ok()).toBeTruthy();
  const body = await res.json();

  return body.access_token as string;
}

export async function findTaskIdByTitle(
  request: APIRequestContext,
  token: string,
  title: string,
): Promise<number> {
  const res = await request.get(`${baseURL()}/api/v1/tasks`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  expect(res.ok()).toBeTruthy();
  const body = await res.json();
  const items = (body.data ?? body) as Array<{ id: number; title: string }>;
  const task = items.find((t) => t.title === title);
  expect(task, `Görev bulunamadı: ${title}`).toBeTruthy();

  return task!.id;
}

async function fetchTask(
  request: APIRequestContext,
  token: string,
  taskId: number,
): Promise<TaskPayload> {
  const res = await request.get(`${baseURL()}/api/v1/tasks/${taskId}`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  expect(res.ok()).toBeTruthy();
  const body = await res.json();

  return (body.data ?? body) as TaskPayload;
}

/**
 * Görevi API üzerinden yönlendirildi → sahada → çözüldü → kapatıldı yapar.
 */
export async function advanceTaskToClosed(
  request: APIRequestContext,
  token: string,
  taskId: number,
): Promise<void> {
  const task = await fetchTask(request, token, taskId);
  const assigneeId = task.assignee?.id;
  const arrivalPhotos = task.arrival_photos ?? ['task-arrival-photos/demo-e2e.jpg'];
  const completionPhotos = ['task-completion-photos/demo-e2e.jpg'];

  const now = new Date();
  const iso = (d: Date) => d.toISOString();
  const assigned = new Date(now.getTime() - 3_600_000);
  const dispatched = new Date(now.getTime() - 1_800_000);

  const headers = {
    Authorization: `Bearer ${token}`,
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };

  let res = await request.patch(`${baseURL()}/api/v1/tasks/${taskId}`, {
    headers,
    data: {
      status: 'yonlendirildi',
      assigned_at: iso(assigned),
      assignee_id: assigneeId,
      arrival_photos: arrivalPhotos,
    },
  });
  expect(res.ok(), await res.text()).toBeTruthy();

  res = await request.patch(`${baseURL()}/api/v1/tasks/${taskId}`, {
    headers,
    data: {
      status: 'sahada',
      dispatched_at: iso(dispatched),
      arrival_photos: arrivalPhotos,
    },
  });
  expect(res.ok(), await res.text()).toBeTruthy();

  res = await request.patch(`${baseURL()}/api/v1/tasks/${taskId}`, {
    headers,
    data: {
      status: 'cozuldu',
      resolved_at: iso(now),
      solution_note: 'Playwright demo: işlem tamamlandı.',
      arrival_photos: arrivalPhotos,
      completion_photos: completionPhotos,
    },
  });
  expect(res.ok(), await res.text()).toBeTruthy();

  res = await request.patch(`${baseURL()}/api/v1/tasks/${taskId}`, {
    headers,
    data: { status: 'kapatildi' },
  });
  expect(res.ok(), await res.text()).toBeTruthy();
}
