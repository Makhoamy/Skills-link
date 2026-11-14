export interface WorkerProfile {
  user_id: number;
  first_name: string;
  last_name: string;
  gender: string | null;
  date_of_birth: string | null;
  citizenship: string | null;
  has_id_number: boolean;
  drivers_license: string | null;
  province_id: number | null;
  city: string | null;
  suburb: string | null;
  bio: string | null;
  years_experience: number | null;
  availability: string | null;
  has_photo: boolean;
  has_cv: boolean;
  avg_rating: number;
  rating_count: number;
}

export interface RecruiterProfile {
  user_id: number;
  company_name: string;
  registration_number: string | null;
  company_description: string | null;
  address: string | null;
  city: string | null;
  province_id: number | null;
  zip_code: string | null;
  website: string | null;
  contact_first_name: string | null;
  contact_last_name: string | null;
  contact_position: string | null;
  verification_status: 'unverified' | 'pending' | 'verified' | 'rejected';
  has_registration_doc: boolean;
}

export type UploadKind = 'cv' | 'photo' | 'registration_doc';

export interface PendingRecruiter {
  user_id: number;
  company_name: string;
  registration_number: string | null;
  website: string | null;
  city: string | null;
  verification_status: string;
  email: string;
  has_registration_doc: boolean;
}
