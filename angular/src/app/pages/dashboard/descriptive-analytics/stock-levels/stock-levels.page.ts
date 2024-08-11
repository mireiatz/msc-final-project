import { Component } from "@angular/core";
import { take } from "rxjs";
import { HttpErrorResponse } from "@angular/common/http";
import { ApiService } from "../../../../shared/services/api/services/api.service";
import { Color, ScaleType } from "@swimlane/ngx-charts";
import { StockDetailedMetrics } from "../../../../shared/services/api/models/stock-detailed-metrics";

type ProductStatus = 'understocked' | 'overstocked' | 'within_range';

@Component({
  selector: 'page-overview',
  templateUrl: './stock-levels.page.html',
  styleUrls: ['./stock-levels.page.scss'],
})

export class StockLevelsPage {

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
  public colourScheme: Color | undefined = undefined;

  constructor(
    protected apiService: ApiService
  ) {
    this.getStockMetrics();
  }

  public getStockMetrics() {
    this.apiService.getStockMetrics().pipe(
      take(1)
    ).subscribe({
        next: response => {
          this.metrics = response.data;
          console.log(this.metrics)
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
      series: data.products.map(product => ({
        name: product.name,
        value: product.current,
        status: product.status as ProductStatus,
      }))
    }));

    this.setColorScheme();
  }

  private setColorScheme(): void {
    const colors: Record<ProductStatus, string> = {
      understocked: '#FF4136',
      overstocked: '#1b82ff',
      within_range: '#2ECC40',
    };

    this.colourScheme = {
      name: 'custom',
      selectable: true,
      group: ScaleType.Ordinal,
      domain: this.stockData.flatMap(category => category.series.map(product => colors[product.status]))
    };
  }
}
