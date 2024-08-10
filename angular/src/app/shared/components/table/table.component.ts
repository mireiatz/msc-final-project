import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-table',
  templateUrl: './table.component.html',
  styleUrls: ['./table.component.scss'],
})
export class TableComponent {

  @Input() columns: Array<{ header: string; field: string }> = [{ header: '', field: '' }];
  @Input() data: any[] | undefined = [];

}
