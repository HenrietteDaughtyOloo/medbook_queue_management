import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-sidebar',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './sidebar.component.html',
  styleUrl: './sidebar.component.scss',
})
export class SidebarComponent {
  @Input() queueLength = 0;
  @Input() isDarkMode = false;

  @Output() scrollRequested = new EventEmitter<string>();

  scrollTo(sectionId: string): void {
    this.scrollRequested.emit(sectionId);
  }
}
