import { Component } from '@angular/core';
import { ModalService } from '../../../../../shared/services/modal/modal.service';
import { Prediction } from "../../../../../shared/services/api/models/prediction";

@Component({
  selector: 'app-product-demand-forecast-modal',
  templateUrl: './product-demand-forecast-modal.component.html',
  styleUrls: ['./product-demand-forecast-modal.component.scss'],
})
export class ProductDemandForecastModalComponent {

  public errors: string[] = [];
  public title: string = '';
  public product_name: string = '';
  public predictions: Prediction[];
  public productForecastData: object = {};
  constructor(
    protected modalService: ModalService,
  ) {
    this.title = this.modalService.data.title;
    this.product_name = this.modalService.data.product_name;
    this.predictions = this.modalService.data.predictions;
    this.productForecastData = this.mapProductForecastData(this.product_name, this.predictions)
  }

  public close() {
    this.modalService.close();
  }

  public mapProductForecastData(name: string, predictions: Prediction[]): any[] {
    return [{
      name: name,
      series: predictions.map(item => ({
        name: new Date(item.date),
        value: item.value,
      }))
    }];
  }
}
