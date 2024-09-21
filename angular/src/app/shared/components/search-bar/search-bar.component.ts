import { Component, EventEmitter, Output } from '@angular/core';

@Component({
  selector: 'app-search-bar',
  templateUrl: './search-bar.component.html',
  styleUrls: ['./search-bar.component.scss'],
})
export class SearchBarComponent {

  public searchQuery: string = '';

  @Output() search: EventEmitter<string> = new EventEmitter<string>();

  onSearchChange(): void {
    this.search.emit(this.searchQuery);
  }
}
