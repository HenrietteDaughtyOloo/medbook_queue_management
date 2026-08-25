import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';
import { Customer, QueueResponse } from './models/customer.model';
import { QueueService } from './services/queue.service';
import { SidebarComponent } from './components/sidebar/sidebar.component';
import { CustomerFormComponent } from './components/customer-form/customer-form.component';
import { QueueBoardComponent } from './components/queue-board/queue-board.component';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [
    CommonModule,
    SidebarComponent,
    CustomerFormComponent,
    QueueBoardComponent,
    FormsModule,
  ],
  templateUrl: './app.component.html',
  styleUrl: './app.component.scss',
})


export class AppComponent implements OnInit {
  title = 'Medbook Queue';
  queue: Customer[] = [];
  nextCustomer: Customer | null = null;
  activeCustomer: Customer | null = null;
  loading = false;
  isDarkMode = localStorage.getItem('medbook-theme') === 'dark';
  message = '';
  error = '';

  form = {
    name: '',
    service: '',
    arrival_at: this.localDateTime(),
    original_priority: 'Normal',
  };

  constructor(private readonly queueService: QueueService) {}

  ngOnInit(): void {
    this.loadQueue();
  }

  loadQueue(): void {
    this.loading = true;
    this.queueService.getQueue().subscribe({
      next: (data) => {
        if (!Array.isArray(data.queue)) {
          this.handleError(
            new HttpErrorResponse({
              error: {
                message: 'The API returned an unexpected response format.',
              },
            }),
          );
          return;
        }
        this.queue = data.queue;
        this.nextCustomer = data.queue[0] ?? data.next_customer ?? null;
        this.activeCustomer = data.active_customer ?? null;
        this.loading = false;
      },
      error: (error) => this.handleError(error),
    });
  }

  addCustomer(): void {
    this.clearFeedback();
    const payload = {
      ...this.form,
      arrival_at: new Date(this.form.arrival_at).toISOString(),
    };
    this.queueService.addCustomer(payload).subscribe({
      next: (result) => {
        this.message = result.message;
        this.clearForm();
        this.loadQueue();
      },
      error: (error) => this.handleError(error),
    });
  }

  onAddCustomer(form: {
    name: string;
    service: string;
    arrival_at: string;
    original_priority: string;
  }): void {
    this.form = form;
    this.addCustomer();
  }

  changeStatus(customer: Customer, status: string): void {
    this.clearFeedback();
    this.queueService.updateStatus(customer.id, status).subscribe({
      next: (result) => {
        this.message = result.message;
        this.loadQueue();
      },
      error: (error) => this.handleError(error),
    });
  }

  toggleTheme(): void {
    this.isDarkMode = !this.isDarkMode;
    localStorage.setItem('medbook-theme', this.isDarkMode ? 'dark' : 'light');
  }

  scrollTo(sectionId: string): void {
    document
      .getElementById(sectionId)
      ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  clearForm(): void {
    this.form = {
      name: '',
      service: '',
      arrival_at: this.localDateTime(),
      original_priority: 'Normal',
    };
  }

  private handleError(response: HttpErrorResponse): void {
    const errors = response.error?.errors;
    this.error = errors
      ? Object.values(errors).flat().join(' ')
      : response.error?.message || 'Something went wrong.';
    this.loading = false;
  }

  private clearFeedback(): void {
    this.message = '';
    this.error = '';
  }

  private localDateTime(): string {
    const now = new Date(Date.now() - new Date().getTimezoneOffset() * 60000);
    return now.toISOString().slice(0, 16);
  }
}
