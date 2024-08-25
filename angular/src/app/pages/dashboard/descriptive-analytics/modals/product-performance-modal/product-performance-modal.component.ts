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
        this.quantitySoldData = this.formatChartData(response.data.quantity_sold);
        this.salesRevenueData = this.formatChartData(response.data.sales_revenue);
        this.stockBalanceData = this.formatChartData(response.data.stock_balance);
      },
      error: (error: HttpErrorResponse) => {
        for (let errorList in error.error.errors) {
          this.errors.push(error.error.errors[errorList].toString())
        }
      }
    });
  }

  formatChartData(data: { date: string; amount: number }[]): any[] {
    return data.map(item => ({
      name: item.date,
      value: item.amount
    }));
  }

}
