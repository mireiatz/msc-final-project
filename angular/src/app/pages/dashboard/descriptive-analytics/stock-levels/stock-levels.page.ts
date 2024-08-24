import { Component, OnDestroy } from "@angular/core";
import { finalize, Subject, take } from "rxjs";
import { HttpErrorResponse } from "@angular/common/http";
import { ApiService } from "../../../../shared/services/api/services/api.service";
import { StockDetailedMetrics } from "../../../../shared/services/api/models/stock-detailed-metrics";

type ProductStatus = 'understocked' | 'overstocked' | 'within_range';

@Component({
  selector: 'page-overview',
  templateUrl: './stock-levels.page.html',
  styleUrls: ['./stock-levels.page.scss'],
})

export class StockLevelsPage implements OnDestroy {

  public onDestroy: Subject<void> = new Subject();
  public isLoading: boolean = true;
  public metrics: StockDetailedMetrics[] | undefined = undefined;
  public errors: string[] = [];
  public stockData: Array<{
    name: string,
    series: Array<{
      name: string,
      value: number,
      status: ProductStatus
    }>
  }> = [];
  public customColours: { name: string; value: string }[] = [];

  constructor(
    protected apiService: ApiService
  ) {
    this.getStockMetrics();
  }

  public ngOnDestroy(): void {
    this.onDestroy.next();
  }

  public getStockMetrics() {
    this.apiService.getStockMetrics().pipe(
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

  public mapStockData(): void {
    if(!this.metrics) return;

    this.stockData = this.metrics.map((data) => ({
      name: data.category.name,
      series: data.products
        .filter(product => product.current > 0)
        .map(product => ({
          name: product.name,
          value: product.current,
          status: product.status as ProductStatus,
        }))
    })).filter(category => category.series.length > 0);

    this.setColourScheme()
  }

  private setColourScheme(): void {
    const colors: Record<ProductStatus, string> = {
      understocked: '#FF4136',
      overstocked: '#1b82ff',
      within_range: '#2ECC40',
    };

    this.customColours = this.stockData.flatMap(category =>
      category.series.map(product => ({
        name: product.name,
        value: colors[product.status],
      }))
    );
  }
}
