export interface LoginRecord {
  loginID: number;
  email: string;
  role: string;
  loginDate: string;
  ipAddress: string | null;
}

export interface UserRecord {
  id: number;
  email: string;
  role: string;
  created_at: string;
  loginCount: number;
  lastLogin: string | null;
}

export interface DashboardResponse {
  success: boolean;
  totalUsers: number;
  totalJobseekers: number;
  totalRecruiters: number;
  totalSignIns: number;
  loginsToday: number;
  activeLast7Days: number;
  loginHistory: LoginRecord[];
  users: UserRecord[];
}

export interface ReportColumn {
  key: string;
  label: string;
}

export interface ReportResponse {
  title: string;
  description: string;
  columns: ReportColumn[];
  rows: Record<string, string | number>[];
  summary: { label: string; value: string | number }[];
}

export type ReportType = 'users' | 'jobs' | 'applications' | 'provinces';

export interface BackupItem {
  name: string;
  label: 'manual' | 'auto' | 'prerestore';
  size: number;
  created_at: string;
  has_files: boolean;
}

export interface BackupListResponse {
  items: BackupItem[];
  auto_keep: number;
  zip_available: boolean;
}

