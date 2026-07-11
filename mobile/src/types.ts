export type UserRole = 'admin' | 'vice_mayor' | 'manager' | 'staff';

export type User = {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  department: { id: number | null; name: string | null };
};

export type Task = {
  id: number;
  task_code: string;
  title: string;
  department: { id: number; name: string };
  assignee: { id: number | null; name: string | null };
  location: string | null;
  latitude: number | null;
  longitude: number | null;
  priority: string;
  status: string;
  description: string | null;
  solution_note: string | null;
  arrival_photos: string[] | null;
  completion_photos: string[] | null;
  assigned_at: string | null;
  dispatched_at: string | null;
  resolved_at: string | null;
};

export type ReportDashboard = {
  scope: string;
  summary: {
    open_tasks: number;
    critical_open: number;
    in_field: number;
    resolved_today: number;
    total_staff: number;
  };
  by_status: Record<string, number>;
  by_department: Array<{
    id: number;
    name: string;
    open: number;
    completed: number;
  }>;
};

export const STATUS_LABELS: Record<string, string> = {
  bekliyor: 'Bekliyor',
  yonlendirildi: 'Yönlendirildi',
  sahada: 'Sahada',
  cozuldu: 'Çözüldü',
  onay_bekliyor: 'Onay bekliyor',
  kapatildi: 'Kapatıldı',
  tamamlandi: 'Tamamlandı',
};

export function isReportRole(role: UserRole): boolean {
  return role === 'manager' || role === 'vice_mayor' || role === 'admin';
}

export function isStaffRole(role: UserRole): boolean {
  return role === 'staff';
}
