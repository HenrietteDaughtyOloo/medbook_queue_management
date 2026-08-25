import { CommonModule, DatePipe } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { Customer } from '../../models/customer.model';

@Component({
  selector: 'app-queue-board',
  standalone: true,
  imports: [CommonModule, DatePipe],
  templateUrl: './queue-board.component.html',
  styleUrl: './queue-board.component.scss',
})
export class QueueBoardComponent {
  @Input() queue: Customer[] = [];
  @Input() nextCustomer: Customer | null = null;
  @Input() activeCustomer: Customer | null = null;

  @Output() statusChanged = new EventEmitter<{ customer: Customer; status: string }>();

  changeStatus(customer: Customer, status: string): void {
    this.statusChanged.emit({ customer, status });
  }
}
