export type EmploymentType = 'full_time' | 'part_time' | 'temporary' | 'once_off';
export type PayPeriod = 'hourly' | 'daily' | 'weekly' | 'monthly';

export interface JobCategory {
  id: number;
  group_name: string;
  name: string;
}

export interface Province {
  id: number;
  name: string;
}

export interface JobSummary {
  id: number;
  title: string;
  city: string;
  employment_type: EmploymentType;
  pay_amount: number;
  pay_period: PayPeriod;
  below_nmw_flag: 0 | 1;
  category_name: string;
  province_name: string;
  company_name: string;
  recruiter_rating: number;
  created_at: string;
}

export interface JobDetail extends JobSummary {
  description: string;
  recruiter_id: number;
  recruiter_rating_count: number;
}

export interface CreateJobPayload {
  title: string;
  description: string;
  categoryId: number;
  provinceId: number;
  city: string;
  employmentType: EmploymentType;
  payAmount: number;
  payPeriod: PayPeriod;
}

/** One of the signed-in recruiter's own jobs, with how many people applied. */
export interface MyJob {
  id: number;
  title: string;
  city: string;
  status: 'draft' | 'open' | 'closed' | 'filled';
  employment_type: EmploymentType;
  pay_amount: number;
  pay_period: PayPeriod;
  created_at: string;
  applicant_count: number;
  new_count: number;
}
