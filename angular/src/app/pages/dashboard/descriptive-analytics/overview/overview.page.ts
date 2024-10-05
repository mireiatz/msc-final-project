import { Component, OnDestroy } from "@angular/core";
import { ApiService } from "../../../../shared/services/api/services/api.service";
import { finalize, Subject, take } from "rxjs";
import { HttpErrorResponse } from "@angular/common/http";
import { OverviewMetrics } from "../../../../shared/services/api/models/overview-metrics";
import { ModalService } from "../../../../shared/services/modal/modal.service";
import { Product } from "../../../../shared/services/api/models/product";
import {
  ProductPerformanceModalComponent
} from "../modals/product-performance-modal/product-performance-modal.component";

@Component({
	selector: 'page-overview',
	templateUrl: './overview.page.html',
	styleUrls: ['./overview.page.scss'],
})

export class OverviewPage implements OnDestroy {

  public onDestroy: Subject<void> = new Subject();
  public isLoading: boolean = true;
  public errors: string[] = [];
  public metrics: OverviewMetrics | undefined = undefined;
  public startDate: string = '';
  public endDate: string = '';
  public stockChartData: Array<{ name: string; value: number; }> = [];
  public salesRevenueChartData: Array<{ name: string; value: number; }> = [];
  public salesItemsChartData: Array<{ name: string; value: number; }> = [];

  constructor(
    protected apiService: ApiService,
    protected modalService: ModalService,
  ) {}

  public ngOnDestroy(): void {
    this.onDestroy.next();
  }

  public getOverviewMetrics() {
    this.isLoading = true;
    
    this.apiService.getOverviewMetrics({
      body: {
        start_date: this.startDate,
        end_date: this.endDate,
      }
    }).pipe(
      take(1),
      finalize(() => this.isLoading = false),
    ).subscribe({
        next: response => {
          this.metrics = response.data;
          this.mapStockChartData();
          this.mapSalesChartData();
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
    this.getOverviewMetrics();
  }

  public mapStockChartData() {
    if(!this.metrics) return;

    const inStockPercentage = (this.metrics.stock.products_in_stock_count / this.metrics.stock.product_count) * 100;
    const outOfStockPercentage = (this.metrics.stock.products_out_of_stock_count / this.metrics.stock.product_count) * 100;

    this.stockChartData = [
      {
        "name": "In Stock",
        "value": inStockPercentage,
      },
      {
        "name": "Out Of Stock",
        "value": outOfStockPercentage,
      },
    ];
  }

  public mapSalesChartData() {
    if(!this.metrics) return;

    this.salesRevenueChartData = [
      {
        "name": "Highest",
        "value": Number(this.metrics.sales.highest_sale.toFixed(2)),
      },
      {
        "name": "Lowest",
        "value": Number(this.metrics.sales.lowest_sale.toFixed(2)),
      },
    ];

    this.salesItemsChartData = [
      {
        "name": "Most",
        "value": this.metrics.sales.max_items_sold_in_sale,
      },
      {
        "name": "Least",
        "value": this.metrics.sales.min_items_sold_in_sale,
      },
    ];
  }

  public displayProductInfo(product: Product) {

    const data: any = {
      title: 'Product: ' + product.name,
      product: product,
      start_date: this.startDate,
      end_date: this.endDate,
    }
    this.modalService.open(ProductPerformanceModalComponent, data);
  }
}
