import { Component, OnDestroy } from "@angular/core";
import { finalize, Subject, take } from "rxjs";
import { HttpErrorResponse } from "@angular/common/http";
import { ApiService } from "../../../../shared/services/api/services/api.service";
import { ProductDetailedMetrics } from "../../../../shared/services/api/models/product-detailed-metrics";
import { ModalService } from "../../../../shared/services/modal/modal.service";
import { ProductPerformanceModalComponent } from "../modals/product-performance-modal/product-performance-modal.component";
import { Option } from "../../../../shared/interfaces";

@Component({
	selector: 'page-products-performance',
	templateUrl: './products-performance.page.html',
	styleUrls: ['./products-performance.page.scss'],
})

export class ProductsPerformancePage implements OnDestroy {

  public onDestroy: Subject<void> = new Subject();
  public isLoading: boolean = true;
  public metrics: ProductDetailedMetrics[] | undefined = undefined;
  public filteredMetrics: ProductDetailedMetrics[] | undefined = undefined;
  public errors: string[] = [];
  public startDate: string = '';
  public endDate: string = '';
  public categoryId: string | undefined = '';
  public categoryOptions: Option[] = [];

  public columns = [
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
    protected apiService: ApiService,
    protected modalService: ModalService,
  ) {
    this.fetchCategories();
  }

  public ngOnDestroy(): void {
    this.onDestroy.next();
  }

  public onCategorySelection(selectedCategory: any) {
    this.categoryId = selectedCategory;
    this.getProductsMetrics(this.page);
  }

  public fetchCategories() {
    this.apiService.getCategories().pipe(
      take(1),
      finalize(() => this.isLoading = false),
    ).subscribe({
        next: response => {
          if(!response.data) return;

          this.categoryOptions = response.data.map(category => ({
            id: category.id,
            name: category.name
          }));

          if(this.categoryOptions) {
            this.onCategorySelection(this.categoryOptions[0].id)
            this.getProductsMetrics(this.page);
          }
        },
        error: (error: HttpErrorResponse) => {
          for (let errorList in error.error.errors) {
            this.errors.push(error.error.errors[errorList].toString())
          }
        }
      }
    );
  }

  public getProductsMetrics(page: number) {
    if(!this.categoryId) return;

    this.isLoading = true;

    this.apiService.getProductsMetrics({
      categoryId: this.categoryId,
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
          this.filteredMetrics = response.data.items;
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

  public displayProductInfo(product: any) {
    const data: any = {
      title: 'Product: ' + product.name,
      product: product,
      start_date: this.startDate,
      end_date: this.endDate,
    }
    this.modalService.open(ProductPerformanceModalComponent, data);
  }

  public onSearch(query: string): void {
    this.filterMetrics(query);
  }

  public filterMetrics(query: string = ''): void {
    if (!this.metrics) {
      return;
    }

    this.filteredMetrics = this.metrics.filter(metric =>
      metric.name.toLowerCase().includes(query.toLowerCase())
    );
  }
}
