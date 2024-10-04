import { Component, OnDestroy } from "@angular/core";
import { finalize, Subject, take } from "rxjs";
import { HttpErrorResponse } from "@angular/common/http";
import { ApiService } from "../../../../shared/services/api/services/api.service";

@Component({
	selector: 'page-sales',
	templateUrl: './sales.page.html',
	styleUrls: ['./sales.page.scss'],
})

export class SalesPage implements OnDestroy {

  public onDestroy: Subject<void> = new Subject();
  public isLoading: boolean = true;
  public errors: string[] = [];
  public startDate: string = '';
  public endDate: string = '';
  public salesData: Array<{ date: string; items: number; total_sale: number; }> = [];
  public categoryQuantityData: any[] = []
  public categoryRevenueData: any[] = []

  constructor(
    protected apiService: ApiService,
  ) {}

  public ngOnDestroy(): void {
    this.onDestroy.next();
  }

  public getSalesMetrics() {
    this.isLoading = true;

    this.apiService.getSalesMetrics({
      body: {
        start_date: this.startDate,
        end_date: this.endDate,
      }
    }).pipe(
      take(1),
      finalize(() => this.isLoading = false),
    ).subscribe({
        next: response => {
          if(!response.data) return;

          this.salesData = this.mapSalesData(response.data.all_sales);
          this.categoryQuantityData = this.mapSalesPerElementData(response.data.sales_per_category, 'quantity', 'category_name');
          this.categoryRevenueData = this.mapSalesPerElementData(response.data.sales_per_category, 'total_sale', 'category_name');
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
    this.getSalesMetrics();
  }

  public mapSalesData(sales: any[]): any[] {
    const revenueSeries = {
      name: 'Revenue',
      series: sales.map(sale => ({
        name: sale.date,
        value: sale.total_sale
      }))
    };

    const itemsSeries = {
      name: 'Items',
      series: sales.map(sale => ({
        name: sale.date,
        value: sale.items
      }))
    };

    return [revenueSeries, itemsSeries];
  }

  public mapSalesPerElementData(data: any[], valueKey: string, nameKey: string): any[] {
    const groupedData: {
      [key: string]: {
        name: string;
        series: {
          name: string;
          value: number;
        }[];
      }
    } = {};

    data.forEach(item => {
      const elementName = item[nameKey];
      const date = item.date;

      if (!groupedData[elementName]) {
        groupedData[elementName] = {name: elementName, series: []};
      }

      groupedData[elementName].series.push({name: date, value: item[valueKey]});
    });

    for (const key in groupedData) {
      groupedData[key].series.sort((a, b) => new Date(a.name).getTime() - new Date(b.name).getTime());
    }

    return Object.values(groupedData);
  }
}
