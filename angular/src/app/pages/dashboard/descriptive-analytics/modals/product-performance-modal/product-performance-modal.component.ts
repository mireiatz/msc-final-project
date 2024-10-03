import { Component } from '@angular/core';
import { ModalService } from '../../../../../shared/services/modal/modal.service';
import { ApiService } from "../../../../../shared/services/api/services/api.service";
import { HttpErrorResponse } from "@angular/common/http";

@Component({
  selector: 'app-product-performance-modal',
  templateUrl: './product-performance-modal.component.html',
  styleUrls: ['./product-performance-modal.component.scss'],
})
export class ProductPerformanceModalComponent {

  public errors: string[] = [];
  public title: string = '';
  public data: any;
  quantitySoldData: any[] = [];
  salesRevenueData: any[] = [];
  stockBalanceData: any[] = [];

  constructor(
    protected modalService: ModalService,
    protected apiService: ApiService,
  ) {
    this.data = this.modalService.data;

    this.title = this.modalService.data.title;
    this.fetchProductData();
  }

  close() {
    this.modalService.close();
  }

  public fetchProductData() {
    this.apiService.getProductMetrics({
      productId: this.data.product.id,
      body: {
        start_date: this.data.start_date,
        end_date: this.data.end_date,
      }
    }).subscribe({
      next: response => {
        this.quantitySoldData = this.mapChartData(response.data.quantity_sold, 'Quantity');
        this.salesRevenueData = this.mapChartData(response.data.sales_revenue, 'Revenue');
        this.stockBalanceData = this.mapChartData(response.data.stock_balance, 'Stock');
      },
      error: (error: HttpErrorResponse) => {
        for (let errorList in error.error.errors) {
          this.errors.push(error.error.errors[errorList].toString())
        }
      }
    });
  }

  mapChartData(data: { date: string; amount: number }[], name: string): any[] {
    return [{
      name: name,
      series: data.map(item => ({
        name: new Date(item.date),
        value: item.amount
      }))
    }];
  }
}
