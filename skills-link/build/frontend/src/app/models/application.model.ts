export type ApplicationStatus = 'applied' | 'shortlisted' | 'rejected' | 'hired' | 'withdrawn';

export interface Applicant {
  id: number;
  status: ApplicationStatus;
  cover_note: string | null;
  applied_at: string;
  worker_id: number;
  first_name: string;
  last_name: string;
  city: string | null;
  years_experience: number | null;
  availability: string | null;
  bio: string | null;
  has_cv: boolean;
  phone: string | null;
}
