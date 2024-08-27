import { Component } from '@angular/core';
import { ModalService } from "../../services/modal/modal.service";

@Component({
  selector: 'app-modal',
  templateUrl: './modal.component.html',
  styleUrls: ['./modal.component.scss'],
})
export class ModalComponent {
  constructor(
    protected modalService: ModalService,
  ) {}

  close() {
    this.modalService.close();
  }
}
