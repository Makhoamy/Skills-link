export interface Review {
  rating: number;
  comment: string | null;
  created_at: string;
  reviewer_id: number;
}

export interface CreateReviewPayload {
  jobId?: number;
  revieweeUserId: number;
  rating: number;
  comment?: string;
}
