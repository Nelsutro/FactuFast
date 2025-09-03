import { Component, Output, EventEmitter } from '@angular/core';

@Component({
  selector: 'app-sidebar',
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.css'],
  standalone: false
})
export class SidebarComponent {
  @Output() closeSidenav = new EventEmitter<void>();

  onItemClick() {
    // Close sidenav on mobile after navigation
    this.closeSidenav.emit();
  }
}