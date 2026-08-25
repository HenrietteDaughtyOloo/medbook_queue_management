import { Component, EventEmitter, OnDestroy, OnInit, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-customer-form',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './customer-form.component.html',
  styleUrl: './customer-form.component.scss',
})
export class CustomerFormComponent implements OnInit, OnDestroy {
  @Output() formSubmit = new EventEmitter<{
    name: string;
    service: string;
    arrival_at: string;
    original_priority: string;
  }>();

  form = this.getInitialForm();
  
  private timerId: any;
  isUserEditing = false; // Tracks focus state so live updates don't interrupt active typing

  ngOnInit(): void {
    // Keep arrival_at updated live every second
    this.timerId = setInterval(() => {
      if (!this.isUserEditing) {
        this.form.arrival_at = this.getCurrentLocalDateTime();
      }
    }, 1000);
  }

  ngOnDestroy(): void {
    // Clear timer when component unmounts to prevent memory leaks
    if (this.timerId) {
      clearInterval(this.timerId);
    }
  }

  submit(): void {
    this.formSubmit.emit({ ...this.form });
    this.clearForm();
  }

  clearForm(): void {
    this.form = this.getInitialForm();
  }

  private getCurrentLocalDateTime(): string {
    const now = new Date(Date.now() - new Date().getTimezoneOffset() * 60000);
    return now.toISOString().slice(0, 16);
  }

  private getInitialForm() {
    return {
      name: '',
      service: '',
      arrival_at: this.getCurrentLocalDateTime(),
      original_priority: 'Normal',
    };
  }
}