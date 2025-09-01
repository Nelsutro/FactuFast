import { Component, Input, Output, EventEmitter } from '@angular/core';

@Component({
  selector: 'app-sidebar',
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.css'],
  standalone: false
})
export class SidebarComponent {
  @Input() isOpen: boolean = false;
  @Output() toggleSidebar = new EventEmitter<void>();

  closeSidebar() {
    this.toggleSidebar.emit();
  }
}