import { Component } from '@angular/core';
import { ModalService } from '../../../../../shared/services/modal/modal.service';
import { ApiService } from "../../../../../shared/services/api/services/api.service";

@Component({
  selector: 'app-reorder-info-modal',
  templateUrl: './reorder-info-modal.component.html',
  styleUrls: ['./reorder-info-modal.component.scss'],
})
export class ReorderInfoModalComponent {

  public errors: string[] = [];
  public title: string = '';
  public data: any;

  constructor(
    protected modalService: ModalService,
    protected apiService: ApiService,
  ) {
    this.data = this.modalService.data;
  }

  close() {
    this.modalService.close();
  }
}
