import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Customer, QueueResponse } from '../models/customer.model';

@Injectable({
  providedIn: 'root',
})
export class QueueService {
  constructor(private http: HttpClient) {}

  getQueue(): Observable<QueueResponse> {
    return this.http.get<QueueResponse>('/api/queue');
  }

  addCustomer(payload: Partial<Customer>): Observable<{ message: string }> {
    return this.http.post<{ message: string }>('/api/customers', payload);
  }

  updateStatus(
    customerId: number,
    status: string,
  ): Observable<{ message: string }> {
    return this.http.patch<{ message: string }>(
      `/api/customers/${customerId}/status`,
      { status },
    );
  }
}
