export interface Customer {
  id: number;
  name: string;
  service: string;
  arrival_at: string;
  original_priority: string;
  effective_priority: string | null;
  waiting_minutes: number | null;
  status: string;
  position: number | null;
  allowed_transitions: string[];
}
export interface QueueResponse {
  calculated_at: string;
  next_customer: Customer | null;
  active_customer: Customer | null;
  queue: Customer[];
}
