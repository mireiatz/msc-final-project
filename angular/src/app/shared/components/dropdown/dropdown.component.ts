import { Component, Input, Output, EventEmitter } from '@angular/core';
import { Option } from "../../interfaces";

@Component({
  selector: 'app-dropdown',
  templateUrl: './dropdown.component.html',
  styleUrls: ['./dropdown.component.scss']
})
export class DropdownComponent {

  @Input() placeholder: string = 'Select an option';
  @Input() options: Option[] = [];
  @Input() selectedOptionId: string | undefined = '';
  @Output() selectionChange = new EventEmitter<string>();

  isDropdownOpen: boolean = false;

  toggleDropdown(): void {
    this.isDropdownOpen = !this.isDropdownOpen;
  }

  selectOption(option: Option): void {
    this.selectedOptionId = option.id;
    this.isDropdownOpen = false;
    this.selectionChange.emit(this.selectedOptionId);
  }

  getSelectedOption(): Option | undefined {
    return this.options.find(option => option.id === this.selectedOptionId);
  }
}
