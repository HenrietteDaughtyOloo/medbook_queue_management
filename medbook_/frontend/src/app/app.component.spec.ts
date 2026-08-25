/// <reference types="jasmine" />

import { provideHttpClient } from '@angular/common/http';
import {
  HttpTestingController,
  provideHttpClientTesting,
} from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { AppComponent } from './app.component';

describe('AppComponent', () => {
  let http: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AppComponent],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();

    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    http.verify();
  });

  function flushQueue(queue: object[], activeCustomer: object | null = null): void {
    http.expectOne('/api/queue').flush({
      calculated_at: '2026-08-20T11:15:00Z',
      next_customer: queue[0] ?? null,
      active_customer: activeCustomer,
      queue,
    });
  }

  it('creates the application', () => {
    // Arrange
    const fixture = TestBed.createComponent(AppComponent);

    // Act
    const app = fixture.componentInstance;

    // Assert
    expect(app).toBeTruthy();
  });

  it('uses the first ordered queue customer as Serve next', () => {
    // Arrange
    const fixture = TestBed.createComponent(AppComponent);
    const mockQueue = [
      {
        id: 1,
        name: 'Peter',
        service: 'General consultation',
        arrival_at: '2026-08-20T09:45:00Z',
        original_priority: 'Normal',
        effective_priority: 'Emergency',
        waiting_minutes: 90,
        status: 'Waiting',
        position: 1,
        allowed_transitions: ['Being Served', 'Cancelled'],
      },
    ];

    // Act
    fixture.detectChanges(); // Triggers initial HTTP GET /api/queue
    flushQueue(mockQueue);
    fixture.detectChanges(); // Renders updated queue state to DOM

    // Assert
    const renderedText = fixture.nativeElement.textContent;
    expect(renderedText).toContain('Peter');
    expect(renderedText).not.toContain('Queue is clear');
  });

  it('posts a registered customer and reloads the queue', () => {
    // Arrange
    const fixture = TestBed.createComponent(AppComponent);
    const app = fixture.componentInstance;
    const mockFormData = {
      name: 'Amina',
      service: 'Consultation',
      arrival_at: '2026-08-20T15:00',
      original_priority: 'Normal',
    };
    app.form = { ...mockFormData };

    // Act
    app.addCustomer();

    // Assert & Intercept Requests
    const postRequest = http.expectOne('/api/customers');
    expect(postRequest.request.method).toBe('POST');
    expect(postRequest.request.body.name).toBe('Amina');
    expect(postRequest.request.body.arrival_at).toBe(
      new Date('2026-08-20T15:00').toISOString(),
    );

    // Act (Flush async responses)
    postRequest.flush({ message: 'Customer added to the queue.' });
    flushQueue([]);

    // Assert Final State
    expect(app.message).toBe('Customer added to the queue.');
  });
});
