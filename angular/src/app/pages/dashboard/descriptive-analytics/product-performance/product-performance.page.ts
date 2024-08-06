import { Component } from "@angular/core";
import { take } from "rxjs";
import { HttpErrorResponse } from "@angular/common/http";
import { ApiService } from "../../../../shared/services/api/services/api.service";
import { ProductsMetrics } from "../../../../shared/services/api/models/products-metrics";
import { ProductDetailedMetrics } from "../../../../shared/services/api/models/product-detailed-metrics";

@Component({
	selector: 'page-product-performance',
	templateUrl: './product-performance.page.html',
	styleUrls: ['./product-performance.page.scss'],
})

export class ProductPerformancePage {

  public metrics: ProductsMetrics | undefined = undefined;
  public detailedMetrics: ProductDetailedMetrics[] | undefined = undefined;
  public errors: string[] = [];
  public startDate: string = '';
  public endDate: string = '';

  public columns = [
    { header: 'Category', field: 'category' },
    { header: 'Product', field: 'name' },
    { header: 'Provider', field: 'provider' },
    { header: 'Price', field: 'sale' },
    { header: 'Sale Quantity', field: 'total_quantity_sold' },
    { header: 'Sale Revenue', field: 'total_sales_revenue' },
    { header: 'Initial Stock', field: 'initial_stock_balance' },
    { header: 'Final Stock', field: 'final_stock_balance' },
  ];

  constructor(
    protected apiService: ApiService
  ) {}

  public getProductsMetrics() {
    this.apiService.getProductsMetrics({
      body: {
        start_date: this.startDate,
        end_date: this.endDate,
      }
    }).pipe(
      take(1)
    ).subscribe({
        next: response => {
          this.metrics = response.data;
          this.detailedMetrics = response.data.detailed_metrics;
          console.log(this.metrics)
        },
        error: (error: HttpErrorResponse) => {
          for (let errorList in error.error.errors) {
            this.errors.push(error.error.errors[errorList].toString())
          }
        }
      }
    );
  }

  public setDatesSelected(event: any) {
    this.startDate = event.startDate;
    this.endDate = event.endDate;
    this.getProductsMetrics();
  }
}
