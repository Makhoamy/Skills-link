export interface CreateAdvertPayload {
  title: string;
  description: string;
  categoryId: number;
  provinceId: number;
  city: string;
  contactPhone: string;
  rateAmount?: number;
  ratePeriod?: 'hourly' | 'daily' | 'weekly' | 'per_job';
}

export interface Advert {
  id: number;
  worker_id: number;
  title: string;
  description: string;
  city: string;
  province_id: number;
  category_id: number;
  category: string;
  rate_amount: number | null;
  rate_period: string | null;
  contact_phone: string;
  created_at: string;
  first_name: string;
  avg_rating: number;
  rating_count: number;
}
