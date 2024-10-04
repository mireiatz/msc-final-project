import { Component, OnDestroy } from "@angular/core";
import { finalize, Subject, take } from "rxjs";
import { HttpErrorResponse } from "@angular/common/http";
import { ApiService } from "../../../../shared/services/api/services/api.service";
import { StockDetailedMetrics } from "../../../../shared/services/api/models/stock-detailed-metrics";
import { Option } from "../../../../shared/interfaces";

type ProductStatus = 'understocked' | 'overstocked' | 'within_range';

@Component({
  selector: 'page-overview',
  templateUrl: './stock-levels.page.html',
  styleUrls: ['./stock-levels.page.scss'],
})

export class StockLevelsPage implements OnDestroy {

  public onDestroy: Subject<void> = new Subject();
  public isLoading: boolean = true;

  public categoryId: string | undefined = '';
  public categoryOptions: Option[] = [];
  public metrics: StockDetailedMetrics | undefined = undefined;
  public errors: string[] = [];
  public stockData: Array<{
    name: string,
    value: number,
    status: ProductStatus
  }> = [];
  public customColours: { name: string; value: string }[] = [];

  constructor(
    protected apiService: ApiService
  ) {
    this.fetchCategories();
  }

  public ngOnDestroy(): void {
    this.onDestroy.next();
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
            this.getStockMetrics();
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

  public getStockMetrics() {
    if(!this.categoryId) return;

    this.isLoading = true;
    this.apiService.getCategoryStockMetrics({
      categoryId: this.categoryId
    }).pipe(
      take(1),
      finalize(() => this.isLoading = false),
    ).subscribe({
        next: response => {
          this.metrics = response.data;
          this.mapStockData();
        },
        error: (error: HttpErrorResponse) => {
          for (let errorList in error.error.errors) {
            this.errors.push(error.error.errors[errorList].toString())
          }
        }
      }
    );
  }

  public onCategorySelection(selectedCategory: any) {
    this.categoryId = selectedCategory;
    this.getStockMetrics();
  }

  public mapStockData(): void {
    if(!this.metrics) return;

    // Map products of the selected category to the chart data
    this.stockData = this.metrics.products
      .filter(product => product.current > 0)  // Only include products with stock
      .map(product => ({
        name: product.name,
        value: product.current,  // Stock balance
        status: product.status as ProductStatus,  // Stock status
      }));

    this.setColourScheme();
  }

  private setColourScheme(): void {
    const colors: Record<ProductStatus, string> = {
      understocked: '#FF4136',
      overstocked: '#1b82ff',
      within_range: '#2ECC40',
    };

    this.customColours = this.stockData.map(product => ({
      name: product.name,
      value: colors[product.status],
    }));
  }
}
