import { API_URL } from '../config';
import type { ReportDashboard, Task, User } from '../types';

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public body?: unknown,
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

async function request<T>(
  path: string,
  options: RequestInit & { token?: string } = {},
): Promise<T> {
  const { token, headers, ...rest } = options;
  const res = await fetch(`${API_URL}${path}`, {
    ...rest,
    headers: {
      Accept: 'application/json',
      ...(rest.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...headers,
    },
  });

  const text = await res.text();
  const body = text ? JSON.parse(text) : null;

  if (!res.ok) {
    const message =
      (body as { message?: string })?.message ??
      (body as { errors?: Record<string, string[]> })?.errors
        ? Object.values((body as { errors: Record<string, string[]> }).errors)
            .flat()
            .join(' ')
        : `HTTP ${res.status}`;
    throw new ApiError(message, res.status, body);
  }

  return body as T;
}

export async function login(
  email: string,
  password: string,
): Promise<{ access_token: string; user: User }> {
  return request('/auth/token', {
    method: 'POST',
    body: JSON.stringify({
      email,
      password,
      device_name: 'bel-sistem-mobile',
    }),
  });
}

export async function fetchMe(token: string): Promise<User> {
  return request('/me', { token });
}

export async function fetchTasks(token: string): Promise<Task[]> {
  const body = await request<{ data: Task[] } | Task[]>('/tasks', { token });
  return Array.isArray(body) ? body : body.data;
}

export async function fetchTask(token: string, id: number): Promise<Task> {
  const body = await request<{ data: Task } | Task>(`/tasks/${id}`, { token });
  return 'data' in body ? body.data : body;
}

export async function patchTask(
  token: string,
  id: number,
  payload: Record<string, unknown>,
): Promise<Task> {
  const body = await request<{ data: Task } | Task>(`/tasks/${id}`, {
    method: 'PATCH',
    token,
    body: JSON.stringify(payload),
  });
  return 'data' in body ? body.data : body;
}

export async function uploadTaskPhoto(
  token: string,
  uri: string,
  type: 'arrival' | 'completion',
): Promise<string> {
  const form = new FormData();
  form.append('type', type);
  form.append('photo', {
    uri,
    name: `${type}.jpg`,
    type: 'image/jpeg',
  } as unknown as Blob);

  const body = await request<{ path: string }>('/uploads/task-photo', {
    method: 'POST',
    token,
    body: form,
  });

  return body.path;
}

export async function fetchReportDashboard(token: string): Promise<ReportDashboard> {
  return request('/reports/dashboard', { token });
}

export async function revokeToken(token: string): Promise<void> {
  await request('/auth/revoke', { method: 'POST', token });
}

export async function registerPushToken(
  token: string,
  payload: { token: string; platform?: 'ios' | 'android'; device_name?: string },
): Promise<void> {
  await request('/push-tokens', {
    method: 'POST',
    token,
    body: JSON.stringify(payload),
  });
}

export async function unregisterPushToken(
  apiToken: string,
  pushToken: string,
): Promise<void> {
  await request('/push-tokens/revoke', {
    method: 'POST',
    token: apiToken,
    body: JSON.stringify({ token: pushToken }),
  });
}
