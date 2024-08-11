import { Component, OnDestroy } from "@angular/core";
import { finalize, Subject, take } from "rxjs";
import { HttpErrorResponse } from "@angular/common/http";
import { ApiService } from "../../../../shared/services/api/services/api.service";
import { ProductDetailedMetrics } from "../../../../shared/services/api/models/product-detailed-metrics";

@Component({
	selector: 'page-product-performance',
	templateUrl: './product-performance.page.html',
	styleUrls: ['./product-performance.page.scss'],
})

export class ProductPerformancePage implements OnDestroy {

  public onDestroy: Subject<void> = new Subject();
  public isLoading: boolean = true;
  public metrics: ProductDetailedMetrics[] | undefined = undefined;
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

  public page = 1;
  public pagination = {
    count: 0,
    total_items: 0,
    items_per_page: 15,
    current_page: 1,
    total_pages: 0
  };

  constructor(
    protected apiService: ApiService
  ) {}

  public ngOnDestroy(): void {
    this.onDestroy.next();
  }

  public getProductsMetrics(page: number) {
    this.isLoading = true;

    this.apiService.getProductsMetrics({
      page: page,
      body: {
        start_date: this.startDate,
        end_date: this.endDate,
      }
    }).pipe(
      take(1),
      finalize(() => this.isLoading = false),
    ).subscribe({
        next: response => {
          this.metrics = response.data.items;
          this.pagination = response.data.pagination;
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
    this.page = 1;
    this.pagination.current_page = 1;
    this.getProductsMetrics(this.page);
  }

  public onPageChange(page: number): void {
    this.page = page;
    this.pagination.current_page = page;
    this.getProductsMetrics(this.page);
  }
}
